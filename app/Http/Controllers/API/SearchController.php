<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\Company;
use App\Models\CompanyCity;
use App\Models\Discount;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Exception;

class SearchController extends Controller
{
    private $companies = [];

    public function __construct()
    {
        $this->companies = array_values(config('companies.bus', []));
    }

    private function getActiveCompanies(): array
    {
        try {
            $dbCompanies = Company::where('company_type', 'bus')
                ->where('is_active', true)
                ->get(['id', 'company_id', 'company_name', 'company_logo'])
                ->keyBy('company_id');

            $active = [];
            foreach ($this->companies as $config) {
                $externalId = (int) $config['operator_id'];
                $dbMatch = $dbCompanies->get($externalId);
                if (!$dbMatch) {
                    Log::warning('No matching company in DB for operator_id', ['operator_id' => $externalId]);
                    continue;
                }

                $logoUrl = $dbMatch->company_logo ? asset('storage/' . $dbMatch->company_logo) : null;

                $config['name']          = $dbMatch->company_name;
                $config['logo']          = $logoUrl;
                $config['company_db_id'] = $dbMatch->id;

                $active[] = $config;
            }
            return $active;

        } catch (Exception $e) {
            Log::error('getActiveCompanies failed', ['error' => $e->getMessage()]);
            return [];
        }
    }

    private function resolveCompanyCityId(int $operatorId, int $globalCityId): ?int
    {
        try {
            $mapping = CompanyCity::where('company_id', $operatorId)
                ->where('key_id', $globalCityId)
                ->where('active', true)
                ->first();

            if ($mapping) {
                return (int) $mapping->city_id;
            }

            Log::info('No CompanyCity mapping found, skipping operator', [
                'operator_id'    => $operatorId,
                'global_city_id' => $globalCityId,
            ]);
            return null;

        } catch (Exception $e) {
            Log::warning('resolveCompanyCityId failed', [
                'operator_id'    => $operatorId,
                'global_city_id' => $globalCityId,
                'error'          => $e->getMessage(),
            ]);
            return null;
        }
    }

    public function search(Request $request)
    {
        try {
            $validated = $request->validate([
                'fromId' => 'required|integer',
                'toId'   => 'required|integer',
                'date'   => 'required|date',
            ]);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors'  => $e->errors(),
            ], 422);
        }

        $fromId    = (int) $validated['fromId'];
        $toId      = (int) $validated['toId'];
        $date      = $validated['date'];
        $companies = $this->getActiveCompanies();

        return response()->stream(function () use ($companies, $fromId, $toId, $date) {
            while (ob_get_level() > 0) ob_end_clean();

            @ini_set('zlib.output_compression', 0);
            @ini_set('output_buffering', 0);
            @ini_set('implicit_flush', 1);
            ob_implicit_flush(true);

            try {
                $this->sseEmit('start', [
                    'total_companies' => count($companies),
                    'message'         => 'Search started',
                ]);

                if (empty($companies)) {
                    $this->sseEmit('error', ['message' => 'No active bus companies found.']);
                    $this->sseEmit('done', ['total_trips' => 0, 'message' => 'No results']);
                    return;
                }

                $allTrips = [];

                foreach ($companies as $index => $company) {
                    $operatorId  = $company['operator_id'];
                    $companyName = $company['name'];

                    $companyFromId = $this->resolveCompanyCityId((int) $operatorId, $fromId);
                    $companyToId   = $this->resolveCompanyCityId((int) $operatorId, $toId);

                    if ($companyFromId === null || $companyToId === null) {
                        Log::info("Skipping operator — city mapping missing", [
                            'operator_id' => $operatorId,
                            'company'     => $companyName,
                            'fromId'      => $fromId,
                            'toId'        => $toId,
                        ]);
                        continue;
                    }

                    $this->sseEmit('fetching', [
                        'operator_id' => $operatorId,
                        'company'     => $companyName,
                        'index'       => $index + 1,
                    ]);

                    try {
                        $scheduleResponse = Http::timeout(10)
                            ->retry(2, 100)
                            ->withOptions(['connect_timeout' => 5])
                            ->get($company['api_url'], [
                                'from'     => $companyFromId,
                                'to'       => $companyToId,
                                'date'     => $date,
                                'username' => $company['username'],
                                'password' => $company['password'],
                            ]);

                        if (!$scheduleResponse->successful()) {
                            $this->sseEmit('company_failed', [
                                'operator_id' => $operatorId,
                                'company'     => $companyName,
                                'reason'      => 'Failed to fetch schedule',
                            ]);
                            continue;
                        }

                        $companyData = $scheduleResponse->json();
                        if (!is_array($companyData) || empty($companyData)) {
                            $this->sseEmit('company_empty', [
                                'operator_id' => $operatorId,
                                'company'     => $companyName,
                            ]);
                            continue;
                        }

                        // Group trips
                        $grouped = [];
                        foreach ($companyData as $trip) {
                            $serviceTypeId = $trip['MaskRouteCode'] ?? $trip['ServiceType'] ?? 'unknown';
                            if (!isset($grouped[$serviceTypeId])) {
                                $grouped[$serviceTypeId] = ['trips' => [], 'departureTimes' => []];
                            }
                            $grouped[$serviceTypeId]['trips'][] = $trip;
                            $depTime = $trip['departureTime'] ?? '00:00';
                            if (!in_array($depTime, $grouped[$serviceTypeId]['departureTimes'])) {
                                $grouped[$serviceTypeId]['departureTimes'][] = $depTime;
                            }
                        }

                        // Fare pool
                        $farePoolRequests = [];
                        foreach ($grouped as $serviceTypeId => $serviceGroup) {
                            foreach (array_slice($serviceGroup['departureTimes'], 0, 2) as $depTime) {
                                $params = [
                                    'Source_id'      => $companyFromId,
                                    'Destination_id' => $companyToId,
                                    'ServiceType_Id' => $serviceTypeId,
                                    'username'       => $company['username'],
                                    'password'       => $company['password'],
                                ];
                                $farePoolRequests["{$serviceTypeId}|{$depTime}|seat20"] = [
                                    'url'    => $company['fare_api_url'],
                                    'params' => array_merge($params, ['Seat_No' => 20]),
                                ];
                                $farePoolRequests["{$serviceTypeId}|{$depTime}|seat4"] = [
                                    'url'    => $company['fare_api_url'],
                                    'params' => array_merge($params, ['Seat_No' => 4]),
                                ];
                            }
                        }

                        $fareResponses = [];
                        if (!empty($farePoolRequests)) {
                            try {
                                $fareResponses = Http::pool(function ($pool) use ($farePoolRequests) {
                                    foreach ($farePoolRequests as $key => $req) {
                                        $pool->as($key)
                                            ->timeout(8)
                                            ->withOptions(['connect_timeout' => 3])
                                            ->get($req['url'], $req['params']);
                                    }
                                });
                            } catch (Exception $e) {
                                Log::warning("Fare pool failed for {$companyName}", ['error' => $e->getMessage()]);
                            }
                        }

                        // ---------- DISCOUNT FETCH (PHP FILTERING) ----------
                        $discount = null;
                        try {
                            $allActiveDiscounts = Discount::active()->get();
                            foreach ($allActiveDiscounts as $d) {
                                // City match: main_city_id OR mapped_city_ids contains $toId
                                $cityMatches = ($d->main_city_id == $toId);
                                if (!$cityMatches && is_array($d->mapped_city_ids)) {
                                    $cityMatches = in_array($toId, $d->mapped_city_ids) ||
                                                   in_array((string)$toId, $d->mapped_city_ids);
                                }
                                if (!$cityMatches) continue;

                                // Company match: null/empty means all companies
                                $companyMatches = is_null($d->company_ids) || empty($d->company_ids);
                                if (!$companyMatches && is_array($d->company_ids)) {
                                    $companyMatches = in_array($operatorId, $d->company_ids) ||
                                                      in_array((string)$operatorId, $d->company_ids) ||
                                                      in_array((int)$operatorId, $d->company_ids);
                                }
                                if ($companyMatches) {
                                    $discount = $d;
                                    break;
                                }
                            }

                            if ($discount) {
                                Log::info('Discount applied (PHP filtered)', [
                                    'company'    => $companyName,
                                    'percentage' => $discount->discount_percentage,
                                    'city_id'    => $toId,
                                    'operator_id'=> $operatorId,
                                ]);
                            } else {
                                Log::debug('No discount found after PHP filtering', [
                                    'company'    => $companyName,
                                    'toId'       => $toId,
                                    'operator_id'=> $operatorId,
                                ]);
                            }
                        } catch (Exception $e) {
                            Log::error("Discount fetch failed for {$companyName}", ['error' => $e->getMessage()]);
                        }
                        // ----------------------------------------------------

                        // Build trips
                        $companyTrips = [];
                        foreach ($grouped as $serviceTypeId => $serviceGroup) {
                            try {
                                usort($serviceGroup['departureTimes'], fn($a, $b) => strcmp($a, $b));
                                usort($serviceGroup['trips'], fn($a, $b) =>
                                    strcmp($a['departureTime'] ?? '23:59', $b['departureTime'] ?? '23:59')
                                );

                                $baseTrip     = $serviceGroup['trips'][0];
                                $firstDepTime = $serviceGroup['departureTimes'][0] ?? '00:00';
                                $baseFare     = (float) ($baseTrip['fare'] ?? 0);
                                $seat20Fare   = $baseFare;
                                $seat4Fare    = $baseFare;

                                $key20 = "{$serviceTypeId}|{$firstDepTime}|seat20";
                                $key4  = "{$serviceTypeId}|{$firstDepTime}|seat4";

                                if (isset($fareResponses[$key20]) && !($fareResponses[$key20] instanceof \Throwable) && $fareResponses[$key20]->successful()) {
                                    $f = (float) ($fareResponses[$key20]->json()[0]['fare'] ?? 0);
                                    if ($f > 0) $seat20Fare = $f;
                                }
                                if (isset($fareResponses[$key4]) && !($fareResponses[$key4] instanceof \Throwable) && $fareResponses[$key4]->successful()) {
                                    $f = (float) ($fareResponses[$key4]->json()[0]['fare'] ?? 0);
                                    if ($f > 0) $seat4Fare = $f;
                                }

                                $extraFare        = max(0, $seat4Fare - $seat20Fare);
                                $totalSeats       = 0;
                                $totalSeatsLeft   = 0;
                                $departureDetails = [];

                                foreach ($serviceGroup['trips'] as $trip) {
                                    $totalSeats     += (int) ($trip['Seats']    ?? 40);
                                    $totalSeatsLeft += (int) ($trip['SeatsLeft'] ?? $trip['Seats'] ?? 0);
                                    $departureDetails[] = [
                                        'time'           => $trip['departureTime'] ?? '00:00',
                                        'arrivalTime'    => $trip['arrivalTime']   ?? '00:00',
                                        'scheduleId'     => $trip['Schedule_Id']   ?? null,
                                        'seatsAvailable' => (int) ($trip['SeatsLeft'] ?? 0),
                                        'seatsLeft'      => (int) ($trip['SeatsLeft'] ?? 0),
                                        'totalSeats'     => (int) ($trip['Seats']    ?? 40),
                                        'price'          => $seat20Fare,
                                        'fare'           => $seat20Fare,
                                        'base_fare'      => $seat20Fare,
                                        'seat_4_fare'    => $seat4Fare,
                                        'extra_fare'     => $extraFare,
                                    ];
                                }

                                usort($departureDetails, fn($a, $b) => strcmp($a['time'], $b['time']));

                                $builtTrip = [
                                    'id'                  => $baseTrip['Schedule_Id'] ?? uniqid('trip_'),
                                    'serviceTypeId'       => $serviceTypeId,
                                    'scheduleId'          => $baseTrip['Schedule_Id'] ?? null,
                                    'busService'          => $company['name'],
                                    'busType'             => $baseTrip['busType']      ?? 'Standard',
                                    'from'                => $baseTrip['fromCityName'] ?? 'From City',
                                    'to'                  => $baseTrip['toCityName']   ?? 'To City',
                                    'fromCityId'          => $fromId,
                                    'toCityId'            => $toId,
                                    'companyFromCityId'   => $companyFromId,
                                    'companyToCityId'     => $companyToId,
                                    'departureTime'       => $firstDepTime,
                                    'arrivalTime'         => $baseTrip['arrivalTime']  ?? '00:00',
                                    'duration'            => $this->calculateDuration($baseTrip['departureTime'] ?? '00:00', $baseTrip['arrivalTime'] ?? '00:00'),
                                    'price'               => $seat20Fare,
                                    'originalPrice'       => $seat20Fare,
                                    'seat_20_fare'        => $seat20Fare,
                                    'seat_4_fare'         => $seat4Fare,
                                    'extra_fare'          => $extraFare,
                                    'total_fare'          => $seat20Fare + $extraFare,
                                    'seatsLeft'           => $totalSeatsLeft,
                                    'totalSeats'          => $totalSeats,
                                    'departureTimes'      => $serviceGroup['departureTimes'],
                                    'departureDetails'    => $departureDetails,
                                    'amenities'           => $this->extractAmenities($baseTrip['Amenities'] ?? ''),
                                    'stops'               => (int) ($baseTrip['stops']           ?? 0),
                                    'status'              => $baseTrip['status']                  ?? '0',
                                    'company'             => $company['name'],
                                    'operator_id'         => $operatorId,
                                    'maskRouteCode'       => $baseTrip['MaskRouteCode']           ?? $serviceTypeId,
                                    'cancellationPolicy'  => $baseTrip['cancellationpolicy']      ?? '',
                                    'droppingPoints'      => $baseTrip['DropingPoints']           ?? '',
                                    'logo'                => $company['logo']                     ?? null,
                                    'has_discount'        => false,
                                    'discount_percentage' => 0,
                                ];

                                if ($discount) {
                                    $builtTrip = $this->applyDiscountToTrip($builtTrip, $discount->discount_percentage);
                                }

                                $companyTrips[] = $builtTrip;
                                $allTrips[]     = $builtTrip;

                            } catch (Exception $e) {
                                Log::warning("Trip build failed for serviceType {$serviceTypeId} in {$companyName}", [
                                    'error' => $e->getMessage(),
                                ]);
                                continue;
                            }
                        }

                        $this->sseEmit('company_results', [
                            'operator_id' => $operatorId,
                            'company'     => $companyName,
                            'index'       => $index + 1,
                            'trips'       => $companyTrips,
                            'count'       => count($companyTrips),
                        ]);

                    } catch (Exception $e) {
                        Log::error("Company {$companyName} failed", [
                            'operator_id' => $operatorId,
                            'error'       => $e->getMessage(),
                        ]);
                        $this->sseEmit('company_failed', [
                            'operator_id' => $operatorId,
                            'company'     => $companyName,
                            'reason'      => 'Service temporarily unavailable',
                        ]);
                        continue;
                    }
                }

                $this->sseEmit('done', [
                    'total_trips' => count($allTrips),
                    'message'     => count($allTrips) > 0
                        ? 'Found ' . count($allTrips) . ' trip(s)'
                        : 'No trips found for this route and date.',
                ]);

            } catch (Exception $e) {
                Log::error('SSE stream fatal error', ['error' => $e->getMessage()]);
                $this->sseEmit('error', ['message' => 'Search failed. Please try again.']);
                $this->sseEmit('done',  ['total_trips' => 0, 'message' => 'Search failed']);
            }

        }, 200, [
            'Content-Type'                 => 'text/event-stream',
            'Cache-Control'                => 'no-cache, no-store, must-revalidate',
            'X-Accel-Buffering'            => 'no',
            'Connection'                   => 'keep-alive',
            'Access-Control-Allow-Origin'  => config('app.frontend_url', '*'),
            'Access-Control-Allow-Methods' => 'GET, OPTIONS',
            'Access-Control-Allow-Headers' => 'Content-Type, Accept, X-Requested-With',
        ]);
    }

    public function searchOptions()
    {
        return response('', 204, [
            'Access-Control-Allow-Origin'  => config('app.frontend_url', '*'),
            'Access-Control-Allow-Methods' => 'GET, OPTIONS',
            'Access-Control-Allow-Headers' => 'Content-Type, Accept, X-Requested-With',
            'Access-Control-Max-Age'       => '86400',
        ]);
    }

    private function sseEmit(string $event, array $data): void
    {
        try {
            echo "event: {$event}\n";
            echo 'data: ' . json_encode($data) . "\n\n";
            for ($i = ob_get_level(); $i > 0; $i--) ob_flush();
            flush();
        } catch (Exception $e) {
            Log::error('sseEmit failed', ['event' => $event, 'error' => $e->getMessage()]);
        }
    }

    private function applyDiscountToTrip(array $trip, float $pct): array
    {
        try {
            $trip['discount_percentage'] = $pct;
            $trip['has_discount']        = true;
            $trip['original_price']      = $trip['price'];
            $trip['discounted_price']    = round($trip['price'] - ($trip['price'] * $pct / 100), 2);

            if (isset($trip['seat_4_fare'])) {
                $trip['seat_4_fare'] = round($trip['seat_4_fare'] - ($trip['seat_4_fare'] * $pct / 100), 2);
            }
            if (isset($trip['seat_20_fare'])) {
                $trip['seat_20_fare'] = round($trip['seat_20_fare'] - ($trip['seat_20_fare'] * $pct / 100), 2);
            }

            if (isset($trip['departureDetails'])) {
                $trip['departureDetails'] = array_map(function ($detail) use ($pct) {
                    $detail['original_fare']       = $detail['fare'];
                    $detail['discounted_fare']     = round($detail['fare'] - ($detail['fare'] * $pct / 100), 2);
                    $detail['discount_percentage'] = $pct;
                    if (isset($detail['seat_4_fare'])) {
                        $detail['seat_4_fare'] = round($detail['seat_4_fare'] - ($detail['seat_4_fare'] * $pct / 100), 2);
                    }
                    return $detail;
                }, $trip['departureDetails']);
            }

            return $trip;
        } catch (Exception $e) {
            Log::warning('applyDiscountToTrip failed', ['error' => $e->getMessage()]);
            return $trip;
        }
    }

    private function calculateDuration(string $departure, string $arrival): string
    {
        try {
            $dep = \Carbon\Carbon::createFromFormat('H:i', $departure);
            $arr = \Carbon\Carbon::createFromFormat('H:i', $arrival);
            if ($arr->lt($dep)) $arr->addDay();
            $diff = $dep->diff($arr);
            return $diff->h . 'h ' . $diff->i . 'm';
        } catch (Exception $e) {
            Log::warning('calculateDuration failed', ['error' => $e->getMessage()]);
            return 'N/A';
        }
    }

    private function extractAmenities(string $amenitiesStr): array
    {
        try {
            if (empty($amenitiesStr)) return [];
            return array_values(array_filter(array_map('trim', explode(',', $amenitiesStr))));
        } catch (Exception $e) {
            Log::warning('extractAmenities failed', ['error' => $e->getMessage()]);
            return [];
        }
    }
}
