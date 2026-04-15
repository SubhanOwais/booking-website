<?php

namespace App\Http\Controllers\Company;

use App\Http\Controllers\Controller;
use App\Models\TicketingSeat;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

class CompanyTicketingController extends Controller
{
    /**
     * Display a listing of tickets for the logged-in company.
     */
    public function index(Request $request)
    {
        $user = Auth::user();
        $companyId = $user->Company_Id;

        // Only allow company users (CompanyOwner / CompanyUser)
        if (!in_array($user->User_Type, ['CompanyOwner', 'CompanyUser'])) {
            abort(403, 'Unauthorized access.');
        }

        $query = TicketingSeat::with(['fromCity', 'toCity', 'user'])
            ->where('Company_Id', $companyId)
            ->orderBy('created_at', 'desc');

        // Apply filters...
        if ($request->filled('status')) {
            $query->where('Status', $request->status);
        }
        if ($request->filled('date_from')) {
            $query->whereDate('Travel_Date', '>=', $request->date_from);
        }
        if ($request->filled('date_to')) {
            $query->whereDate('Travel_Date', '<=', $request->date_to);
        }
        if ($request->filled('booked_from')) {
            $query->whereDate('created_at', '>=', $request->booked_from);
        }
        if ($request->filled('booked_to')) {
            $query->whereDate('created_at', '<=', $request->booked_to);
        }
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('PNR_No', 'like', "%{$search}%")
                  ->orWhere('Passenger_Name', 'like', "%{$search}%")
                  ->orWhere('Contact_No', 'like', "%{$search}%");
            });
        }
        if ($request->filled('company')) {
            $query->where('Company_Name', $request->company);
        }

        $tickets = $query->paginate(50)->withQueryString();

        // ✅ Replace city names with company‑specific names
        $tickets->getCollection()->transform(function ($ticket) use ($companyId) {
            // Use setAttribute instead of setRelation — plain attributes always serialize to Inertia
            $ticket->setAttribute('fromCity', ['City_Name' => $this->getCompanyCityName($companyId, (int) $ticket->Source_ID)]);
            $ticket->setAttribute('toCity',   ['City_Name' => $this->getCompanyCityName($companyId, (int) $ticket->Destination_ID)]);
            return $ticket;
        });

        // Statistics (only for this company)
        $stats = [
            'total'     => TicketingSeat::where('Company_Id', $companyId)->count(),
            'pending'   => TicketingSeat::where('Company_Id', $companyId)->where('Status', 'Pending')->count(),
            'confirmed' => TicketingSeat::where('Company_Id', $companyId)->where('Status', 'Confirmed')->count(),
            'cancelled' => TicketingSeat::where('Company_Id', $companyId)->where('Status', 'Cancelled')->count(),
        ];

        $companies = TicketingSeat::where('Company_Id', $companyId)
            ->distinct()
            ->whereNotNull('Company_Name')
            ->pluck('Company_Name')
            ->filter()
            ->values();

        return Inertia::render('Company/Ticketing/Index', [
            'tickets'   => $tickets,
            'stats'     => $stats,
            'companies' => $companies,
            'filters'   => [
                'status'      => $request->status ?? '',
                'date_from'   => $request->date_from ?? '',
                'date_to'     => $request->date_to ?? '',
                'booked_from' => $request->booked_from ?? '',
                'booked_to'   => $request->booked_to ?? '',
                'search'      => $request->search ?? '',
                'company'     => $request->company ?? '',
            ],
        ]);
    }

    /**
     * Display the specified ticket details.
     */
    public function show($id)
    {
        $user      = Auth::user();
        $companyId = $user->Company_Id;

        $ticket = TicketingSeat::with(['user'])   // ← removed fromCity/toCity (they return null anyway)
            ->where('Company_Id', $companyId)
            ->findOrFail($id);

        // Convert to plain array FIRST — then inject city names
        // This avoids Eloquent null-relation overwriting setAttribute values
        $ticketData = $ticket->toArray();

        $ticketData['from_city'] = [
            'City_Name' => $this->getCompanyCityName($companyId, (int) $ticket->Source_ID)
        ];
        $ticketData['to_city'] = [
            'City_Name' => $this->getCompanyCityName($companyId, (int) $ticket->Destination_ID)
        ];

        if (request()->expectsJson()) {
            return response()->json(['ticket' => $ticketData]);
        }

        return Inertia::render('Company/Ticketing/Show', [
            'ticket' => $ticketData,
        ]);
    }

    // ═══════════════════════════════════════════════════════════════════════════
    //  Drop these methods into CompanyTicketingController
    //  Replace the existing export() method with exportToExcel() below,
    //  then update your route:
    //      Route::get('ticketing/export', [CompanyTicketingController::class, 'exportToExcel'])
    //           ->name('company.ticketing.export');
    //  And the Vue button:
    //      window.location.href = route("company.ticketing.export") + ...
    // ═══════════════════════════════════════════════════════════════════════════

    // ─────────────────────────────────────────────────────────────────────────
    //  MAIN EXPORT METHOD
    // ─────────────────────────────────────────────────────────────────────────

    public function exportToExcel(Request $request)
    {
        $user      = Auth::user();
        $companyId = $user->Company_Id;

        if (!in_array($user->User_Type, ['CompanyOwner', 'CompanyUser'])) {
            abort(403);
        }

        // ── Build the same filtered query as index() ──────────────────────
        $query = TicketingSeat::where('Company_Id', $companyId)
            ->orderBy('created_at', 'desc');

        if ($request->filled('status')) {
            $query->where('Status', $request->status);
        }
        if ($request->filled('date_from')) {
            $query->whereDate('Travel_Date', '>=', $request->date_from);
        }
        if ($request->filled('date_to')) {
            $query->whereDate('Travel_Date', '<=', $request->date_to);
        }
        if ($request->filled('booked_from')) {
            $query->whereDate('created_at', '>=', $request->booked_from);
        }
        if ($request->filled('booked_to')) {
            $query->whereDate('created_at', '<=', $request->booked_to);
        }
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('PNR_No',         'like', "%{$search}%")
                ->orWhere('Passenger_Name','like', "%{$search}%")
                ->orWhere('Contact_No',    'like', "%{$search}%");
            });
        }
        if ($request->filled('company')) {
            $query->where('Company_Name', $request->company);
        }

        $tickets = $query->get();

        // ── Fallback to CSV if PhpSpreadsheet is not installed ────────────
        if (!class_exists('\PhpOffice\PhpSpreadsheet\Spreadsheet')) {
            return $this->exportToCsvFallback($tickets, $companyId, $request);
        }

        // ── Statistics ────────────────────────────────────────────────────
        $stats = [
            'total'     => $tickets->count(),
            'confirmed' => $tickets->where('Status', 'Confirmed')->count(),
            'pending'   => $tickets->where('Status', 'Pending')->count(),
            'cancelled' => $tickets->where('Status', 'Cancelled')->count(),
            'refund'    => $tickets->where('Status', 'Pending Refund')->count(),
        ];

        // ── Spreadsheet bootstrap ─────────────────────────────────────────
        $spreadsheet = new \PhpOffice\PhpSpreadsheet\Spreadsheet();
        $sheet       = $spreadsheet->getActiveSheet();
        $sheet->setTitle('Ticketing History');

        // ── Reusable style constants ──────────────────────────────────────
        $centerAlign = [
            'alignment' => [
                'horizontal' => \PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER,
                'vertical'   => \PhpOffice\PhpSpreadsheet\Style\Alignment::VERTICAL_CENTER,
            ],
        ];
        $thinBorder = [
            'borders' => [
                'allBorders' => [
                    'borderStyle' => \PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN,
                    'color'       => ['argb' => 'FFBDBDBD'],
                ],
            ],
        ];

        $lastCol   = 'L';   // A–L  (12 columns)
        $headerRow = 5;

        // ────────────────────────────────────────────────────────────────
        //  ROW 1 — Title
        // ────────────────────────────────────────────────────────────────
        $companyName = $tickets->first()?->Company_Name ?? 'Company';
        $sheet->setCellValue('A1', strtoupper($companyName) . ' — TICKETING HISTORY REPORT');
        $sheet->mergeCells("A1:{$lastCol}1");
        $sheet->getStyle("A1:{$lastCol}1")->applyFromArray(array_merge($centerAlign, [
            'font' => ['bold' => true, 'size' => 16, 'color' => ['argb' => 'FFFFFFFF']],
            'fill' => [
                'fillType'   => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID,
                'startColor' => ['argb' => 'FF1B5E20'],
            ],
        ]));
        $sheet->getRowDimension(1)->setRowHeight(28);

        // ────────────────────────────────────────────────────────────────
        //  ROW 2 — Generated on
        // ────────────────────────────────────────────────────────────────
        $sheet->setCellValue('A2', 'Generated on: ' . now()->format('d M Y — H:i:s'));
        $sheet->mergeCells("A2:{$lastCol}2");
        $sheet->getStyle("A2:{$lastCol}2")->applyFromArray(array_merge($centerAlign, [
            'font' => ['italic' => true, 'size' => 10],
            'fill' => [
                'fillType'   => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID,
                'startColor' => ['argb' => 'FFE8F5E9'],
            ],
        ]));

        // ────────────────────────────────────────────────────────────────
        //  ROW 3 — Filter summary
        // ────────────────────────────────────────────────────────────────
        $sheet->setCellValue('A3', 'Filters: ' . $this->getExcelFilterSummary($request));
        $sheet->mergeCells("A3:{$lastCol}3");
        $sheet->getStyle("A3:{$lastCol}3")->applyFromArray([
            'font'      => ['bold' => true, 'size' => 10],
            'fill'      => [
                'fillType'   => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID,
                'startColor' => ['argb' => 'FFE0E0E0'],
            ],
            'alignment' => [
                'horizontal' => \PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_LEFT,
                'wrapText'   => true,
            ],
        ]);
        $sheet->getRowDimension(3)->setRowHeight(20);

        // ROW 4 — spacer
        $sheet->getRowDimension(4)->setRowHeight(6);

        // ────────────────────────────────────────────────────────────────
        //  ROW 5 — Column headers
        // ────────────────────────────────────────────────────────────────
        $headers = [
            'A' => 'PNR No',
            'B' => 'Passenger',
            'C' => 'Contact',
            'D' => 'From',
            'E' => 'To',
            'F' => 'Travel Date',
            'G' => 'Travel Time',
            'H' => 'Seat No',
            'I' => 'Fare (PKR)',
            'J' => 'Discount (PKR)',
            'K' => 'Booking Date',
            'L' => 'Status',
        ];

        foreach ($headers as $col => $label) {
            $sheet->setCellValue($col . $headerRow, $label);
        }

        $sheet->getStyle("A{$headerRow}:{$lastCol}{$headerRow}")->applyFromArray(array_merge($centerAlign, [
            'font'    => ['bold' => true, 'size' => 10, 'color' => ['argb' => 'FFFFFFFF']],
            'fill'    => [
                'fillType'   => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID,
                'startColor' => ['argb' => 'FF4CAF50'],
            ],
            'borders' => [
                'bottom' => ['borderStyle' => \PhpOffice\PhpSpreadsheet\Style\Border::BORDER_MEDIUM],
            ],
        ]));
        $sheet->getRowDimension($headerRow)->setRowHeight(20);

        // ────────────────────────────────────────────────────────────────
        //  DATA ROWS
        // ────────────────────────────────────────────────────────────────
        $row           = $headerRow + 1;
        $totalFare     = 0;
        $totalDiscount = 0;

        // Bucket row numbers by status for batch colouring
        $confirmedRows    = [];
        $pendingRows      = [];
        $cancelledRows    = [];
        $refundRows       = [];

        foreach ($tickets as $ticket) {
            $fromCityName = $this->getCompanyCityName($companyId, (int) $ticket->Source_ID);
            $toCityName   = $this->getCompanyCityName($companyId, (int) $ticket->Destination_ID);

            $fare     = (float) ($ticket->Fare     ?? 0);
            $discount = (float) ($ticket->Discount ?? 0);

            $sheet->setCellValue('A' . $row, $ticket->PNR_No);
            $sheet->setCellValue('B' . $row, $ticket->Passenger_Name);
            $sheet->setCellValue('C' . $row, $ticket->Contact_No);
            $sheet->setCellValue('D' . $row, $fromCityName);
            $sheet->setCellValue('E' . $row, $toCityName);
            $sheet->setCellValue('F' . $row,
                $ticket->Travel_Date
                    ? \Carbon\Carbon::parse($ticket->Travel_Date)->format('d-m-Y')
                    : '');
            $sheet->setCellValue('G' . $row, $ticket->Travel_Time ?? '');
            $sheet->setCellValue('H' . $row, $ticket->Seat_No);
            $sheet->setCellValue('I' . $row, $fare);
            $sheet->setCellValue('J' . $row, $discount);
            $sheet->setCellValue('K' . $row,
                $ticket->created_at
                    ? \Carbon\Carbon::parse($ticket->created_at)->format('d-m-Y H:i')
                    : '');
            $sheet->setCellValue('L' . $row, $ticket->Status);

            // Bucket for batch styling
            match ($ticket->Status) {
                'Confirmed'      => $confirmedRows[] = $row,
                'Pending'        => $pendingRows[]   = $row,
                'Cancelled'      => $cancelledRows[] = $row,
                default          => $refundRows[]    = $row,   // Pending Refund / others
            };

            $totalFare     += $fare;
            $totalDiscount += $discount;
            $row++;
        }

        $lastDataRow = $row - 1;

        if ($lastDataRow >= $headerRow + 1) {
            // Number format for fare / discount columns
            $sheet->getStyle("I" . ($headerRow + 1) . ":J{$lastDataRow}")
                ->getNumberFormat()->setFormatCode('#,##0.00');

            // Borders for the entire data table
            $sheet->getStyle("A{$headerRow}:{$lastCol}{$lastDataRow}")
                ->applyFromArray($thinBorder);

            // Status badge colours — batch by contiguous ranges
            $statusStyles = [
                'confirmed' => ['rows' => $confirmedRows, 'argb' => 'FFE8F5E9'],  // light green
                'pending'   => ['rows' => $pendingRows,   'argb' => 'FFFFF8E1'],  // light yellow
                'cancelled' => ['rows' => $cancelledRows, 'argb' => 'FFFCE4EC'],  // light red
                'refund'    => ['rows' => $refundRows,    'argb' => 'FFFFF3E0'],  // light orange
            ];

            foreach ($statusStyles as $style) {
                foreach ($this->buildExcelRanges($style['rows']) as $range) {
                    // Convert "6:8" → "L6:L8", or "6" → "L6"
                    if (str_contains($range, ':')) {
                        [$start, $end] = explode(':', $range);
                        $cellRange = "L{$start}:L{$end}";
                    } else {
                        $cellRange = "L{$range}";
                    }

                    $sheet->getStyle($cellRange)->applyFromArray([
                        'fill' => [
                            'fillType'   => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID,
                            'startColor' => ['argb' => $style['argb']],
                        ],
                    ]);
                }
            }
        }

        // ────────────────────────────────────────────────────────────────
        //  SUMMARY SECTION
        // ────────────────────────────────────────────────────────────────
        $row++; // spacer

        $sheet->setCellValue('I' . $row, 'REPORT SUMMARY');
        $sheet->mergeCells("I{$row}:{$lastCol}{$row}");
        $sheet->getStyle("I{$row}:{$lastCol}{$row}")->applyFromArray(array_merge($centerAlign, [
            'font' => ['bold' => true, 'size' => 11, 'color' => ['argb' => 'FFFFFFFF']],
            'fill' => [
                'fillType'   => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID,
                'startColor' => ['argb' => 'FF1B5E20'],
            ],
        ]));
        $row++;

        $netAmount   = $totalFare - $totalDiscount;
        $summaryRows = [
            // [ label,                   value,                  bold,  labelBg,    valueBg,    numFmt ]
            ['Total Tickets:',          $stats['total'],         false, 'FFF1F8E9', 'FFE8F5E9', ''],
            ['Confirmed:',              $stats['confirmed'],     false, 'FFF1F8E9', 'FFE8F5E9', ''],
            ['Pending:',                $stats['pending'],       false, 'FFFFF8E1', 'FFFFF3CD', ''],
            ['Cancelled:',              $stats['cancelled'],     false, 'FFFCE4EC', 'FFFDE8EF', ''],
            ['Total Fare (PKR):',       $totalFare,              false, 'FFF1F8E9', 'FFE8F5E9', '#,##0.00'],
            ['Total Discount (PKR):',   $totalDiscount,          false, 'FFFFF8E1', 'FFFFF3CD', '#,##0.00'],
            ['Net Amount (PKR):',       $netAmount,              true,  'FF212121', 'FF212121', '#,##0.00'],
        ];

        foreach ($summaryRows as [$label, $value, $bold, $labelBg, $valueBg, $numFmt]) {
            $isNetRow  = ($bold && $labelBg === 'FF212121');
            $fontColor = $isNetRow ? ['color' => ['argb' => 'FFFFFFFF']] : [];

            $sheet->setCellValue('I' . $row, $label);
            $sheet->setCellValue('J' . $row, $value);
            $sheet->mergeCells("J{$row}:{$lastCol}{$row}");

            $sheet->getStyle('I' . $row)->applyFromArray(array_merge($thinBorder, [
                'font' => array_merge(['bold' => $bold], $fontColor),
                'fill' => ['fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID, 'startColor' => ['argb' => $labelBg]],
            ]));
            $sheet->getStyle("J{$row}:{$lastCol}{$row}")->applyFromArray(array_merge($thinBorder, [
                'font' => array_merge(['bold' => $bold], $fontColor),
                'fill' => ['fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID, 'startColor' => ['argb' => $valueBg]],
            ]));

            if ($numFmt) {
                $sheet->getStyle('J' . $row)->getNumberFormat()->setFormatCode($numFmt);
            }

            $row++;
        }

        // ────────────────────────────────────────────────────────────────
        //  FOOTER
        // ────────────────────────────────────────────────────────────────
        $row += 2;
        $sheet->setCellValue('A' . $row,
            'Report generated by ' . $companyName . ' via Royal Movers System  •  ' . now()->format('d M Y H:i'));
        $sheet->mergeCells("A{$row}:{$lastCol}{$row}");
        $sheet->getStyle("A{$row}:{$lastCol}{$row}")->applyFromArray(array_merge($centerAlign, [
            'font' => ['italic' => true, 'size' => 9],
            'fill' => [
                'fillType'   => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID,
                'startColor' => ['argb' => 'FFE8F5E9'],
            ],
        ]));

        // ────────────────────────────────────────────────────────────────
        //  COLUMN WIDTHS
        // ────────────────────────────────────────────────────────────────
        $widths = [
            'A' => 22,  // PNR
            'B' => 22,  // Passenger
            'C' => 16,  // Contact
            'D' => 16,  // From
            'E' => 16,  // To
            'F' => 14,  // Travel Date
            'G' => 12,  // Travel Time
            'H' => 9,   // Seat
            'I' => 16,  // Fare
            'J' => 16,  // Discount
            'K' => 20,  // Booking Date
            'L' => 16,  // Status
        ];
        foreach ($widths as $col => $width) {
            $sheet->getColumnDimension($col)->setWidth($width);
        }

        // Freeze header row, default font
        $sheet->freezePane('A' . ($headerRow + 1));
        $spreadsheet->getDefaultStyle()->getFont()->setName('Arial')->setSize(10);

        // ────────────────────────────────────────────────────────────────
        //  STREAM
        // ────────────────────────────────────────────────────────────────
        $filename = 'Tickets_' . now()->format('Ymd_His') . '.xlsx';
        $writer   = new \PhpOffice\PhpSpreadsheet\Writer\Xlsx($spreadsheet);
        $writer->setPreCalculateFormulas(false);

        return response()->stream(
            function () use ($writer) { $writer->save('php://output'); },
            200,
            [
                'Content-Type'        => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
                'Content-Disposition' => 'attachment; filename="' . $filename . '"',
                'Cache-Control'       => 'max-age=0',
                'Pragma'              => 'public',
            ]
        );
    }

    // ─────────────────────────────────────────────────────────────────────────
    //  FILTER SUMMARY  (human-readable string for row 3 of the Excel)
    // ─────────────────────────────────────────────────────────────────────────

    protected function getExcelFilterSummary(Request $request): string
    {
        $summary = [];

        if ($request->filled('status')) {
            $summary[] = 'Status: ' . $request->status;
        }

        if ($request->filled('date_from') || $request->filled('date_to')) {
            $from = $request->date_from
                ? \Carbon\Carbon::parse($request->date_from)->format('d/m/Y')
                : 'Any';
            $to   = $request->date_to
                ? \Carbon\Carbon::parse($request->date_to)->format('d/m/Y')
                : 'Any';
            $summary[] = 'Travel Date: ' . $from . ' to ' . $to;
        }

        if ($request->filled('booked_from') || $request->filled('booked_to')) {
            $from = $request->booked_from
                ? \Carbon\Carbon::parse($request->booked_from)->format('d/m/Y')
                : 'Any';
            $to   = $request->booked_to
                ? \Carbon\Carbon::parse($request->booked_to)->format('d/m/Y')
                : 'Any';
            $summary[] = 'Booking Date: ' . $from . ' to ' . $to;
        }

        if ($request->filled('search')) {
            $summary[] = 'Search: "' . $request->search . '"';
        }

        if ($request->filled('company')) {
            $summary[] = 'Company: ' . $request->company;
        }

        return !empty($summary) ? implode(' | ', $summary) : 'All Tickets';
    }

    // ─────────────────────────────────────────────────────────────────────────
    //  HELPER — collapse [6,7,8,10,11] → ["6:8", "10:11"]  for batch styling
    // ─────────────────────────────────────────────────────────────────────────

    private function buildExcelRanges(array $rows): array
    {
        if (empty($rows)) {
            return [];
        }

        sort($rows);
        $ranges = [];
        $start  = $end = $rows[0];

        for ($i = 1; $i < count($rows); $i++) {
            if ($rows[$i] === $end + 1) {
                $end = $rows[$i];
            } else {
                $ranges[] = $start === $end ? (string) $start : "{$start}:{$end}";
                $start = $end = $rows[$i];
            }
        }
        $ranges[] = $start === $end ? (string) $start : "{$start}:{$end}";

        return $ranges;
    }

    // ─────────────────────────────────────────────────────────────────────────
    //  CSV FALLBACK  (if PhpSpreadsheet is not installed)
    // ─────────────────────────────────────────────────────────────────────────

    private function exportToCsvFallback($tickets, int $companyId, Request $request)
    {
        $filename = 'Tickets_' . now()->format('Ymd_His') . '.csv';

        return response()->stream(function () use ($tickets, $companyId) {
            $file = fopen('php://output', 'w');

            fputcsv($file, [
                'PNR No', 'Passenger', 'Contact',
                'From', 'To', 'Travel Date', 'Travel Time',
                'Seat No', 'Fare', 'Discount', 'Booking Date', 'Status',
            ]);

            foreach ($tickets as $ticket) {
                fputcsv($file, [
                    $ticket->PNR_No,
                    $ticket->Passenger_Name,
                    $ticket->Contact_No,
                    $this->getCompanyCityName($companyId, (int) $ticket->Source_ID),
                    $this->getCompanyCityName($companyId, (int) $ticket->Destination_ID),
                    $ticket->Travel_Date?->format('d-m-Y'),
                    $ticket->Travel_Time,
                    $ticket->Seat_No,
                    $ticket->Fare,
                    $ticket->Discount ?? 0,
                    $ticket->created_at?->format('d-m-Y H:i'),
                    $ticket->Status,
                ]);
            }

            fclose($file);
        }, 200, [
            'Content-Type'        => 'text/csv',
            'Content-Disposition' => 'attachment; filename="' . $filename . '"',
        ]);
    }

    /**
     * Get company-specific city name using CompanyCity mapping
     *
     * @param int $operatorId  Company's ID (matches Company_Id in tickets)
     * @param int $globalCityId Global city ID (cities.id)
     * @return string
     */
    private function getCompanyCityName(int $operatorId, int $globalCityId): string
    {
        try {
            $mapping = \App\Models\CompanyCity::where('company_id', $operatorId)
                ->where('city_id', $globalCityId)
                ->where('active', true)
                ->first();

            Log::info('getCompanyCityName', [           // ← add this temporarily
                'operator_id'    => $operatorId,
                'global_city_id' => $globalCityId,
                'mapping_found'  => (bool) $mapping,
                'key_id'         => $mapping?->key_id,
            ]);

            if ($mapping && $mapping->key_id) {
                $cityName = \App\Models\City::where('id', $mapping->key_id)->value('City_Name');
                if ($cityName) return $cityName;
            }
        } catch (\Exception $e) {
            Log::warning('getCompanyCityName failed', [
                'operator_id'    => $operatorId,
                'global_city_id' => $globalCityId,
                'error'          => $e->getMessage(),
            ]);
        }

        // Fallback — direct city lookup by the raw Source_ID/Destination_ID
        return \App\Models\City::where('id', $globalCityId)->value('City_Name') ?? 'Unknown';
    }
}
