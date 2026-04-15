<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\Discount;
use Illuminate\Http\Request;

class DiscountController extends Controller
{
    public function getDiscountByRoute($fromCityId, $toCityId, Request $request)
    {
        $companyName = $request->query('company');

        $query = Discount::where('is_active', true)
            ->where('main_city_id', $fromCityId)
            ->where(function($query) use ($toCityId) {
                $query->whereJsonContains('mapped_city_ids', (int)$toCityId)
                    ->orWhereJsonContains('mapped_city_ids', (string)$toCityId);
            })
            ->where(function($q) {
                $q->whereNull('start_date')
                ->orWhere('start_date', '<=', now());
            })
            ->where(function($q) {
                $q->whereNull('end_date')
                ->orWhere('end_date', '>=', now());
            });

        // Filter by company - REQUIRED now
        if ($companyName) {
            $query->whereJsonContains('company_names', $companyName);
        }

        $discount = $query->first();

        return response()->json([
            'success' => true,
            'discount' => $discount ? [
                'discount_percentage' => $discount->discount_percentage,
                'name' => $discount->name,
                'company_names' => $discount->company_names,
                'start_date' => $discount->start_date,
                'end_date' => $discount->end_date,
            ] : null
        ]);
    }
}
