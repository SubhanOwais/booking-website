<?php

namespace App\Http\Controllers\Company;

use App\Http\Controllers\Controller;
use App\Models\TicketingSeat;
use App\Models\City;
use App\Models\CompanyCity;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;

class RefundReportController extends Controller
{
    /**
     * Display the refund report page.
     */
    public function index(Request $request)
    {
        $user = Auth::user();
        $companyId = $user->Company_Id;

        if (!in_array($user->User_Type, ['CompanyOwner', 'CompanyUser'])) {
            abort(403, 'Unauthorized.');
        }

        $query = TicketingSeat::query()
            ->with('refundedBy')
            ->where('Company_Id', $companyId)
            ->where('Status', 'Cancelled')
            ->whereNotNull('Refund_Amount')
            ->orderBy('Refund_Date', 'desc');

        // Apply filters
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('PNR_No', 'like', "%{$search}%")
                  ->orWhere('Passenger_Name', 'like', "%{$search}%")
                  ->orWhere('Contact_No', 'like', "%{$search}%");
            });
        }

        if ($request->filled('refund_from')) {
            $query->whereDate('Refund_Date', '>=', $request->refund_from);
        }
        if ($request->filled('refund_to')) {
            $query->whereDate('Refund_Date', '<=', $request->refund_to);
        }
        if ($request->filled('travel_from')) {
            $query->whereDate('Travel_Date', '>=', $request->travel_from);
        }
        if ($request->filled('travel_to')) {
            $query->whereDate('Travel_Date', '<=', $request->travel_to);
        }
        if ($request->filled('refund_percent_range')) {
            $range = $request->refund_percent_range;
            switch ($range) {
                case '90-100':
                    $query->whereBetween('Refund_Percentage', [90, 100]);
                    break;
                case '75-89':
                    $query->whereBetween('Refund_Percentage', [75, 89]);
                    break;
                case '50-74':
                    $query->whereBetween('Refund_Percentage', [50, 74]);
                    break;
                case '1-49':
                    $query->whereBetween('Refund_Percentage', [1, 49]);
                    break;
                case '0':
                    $query->where('Refund_Percentage', 0);
                    break;
            }
        }

        $tickets = $query->paginate(25)->withQueryString();

        // Transform each ticket
        $tickets->getCollection()->transform(function ($ticket) use ($companyId) {
            $fromCity = $this->getCompanyCityName($companyId, (int) $ticket->Source_ID);
            $toCity   = $this->getCompanyCityName($companyId, (int) $ticket->Destination_ID);

            $fare = (float) $ticket->Fare;
            $refundAmount = (float) $ticket->Refund_Amount;
            $calculatedPercent = $fare > 0 ? round(($refundAmount / $fare) * 100, 1) : 0;

            return [
                'id'                  => $ticket->id,
                'PNR_No'              => $ticket->PNR_No,
                'Passenger_Name'      => $ticket->Passenger_Name,
                'Contact_No'          => $ticket->Contact_No,
                'from_city_name'      => $fromCity,
                'to_city_name'        => $toCity,
                'Travel_Date'         => $ticket->Travel_Date,
                'Travel_Time'         => $ticket->Travel_Time,
                'Seat_No'             => $ticket->Seat_No,
                'Fare'                => $ticket->Fare,
                'Refund_Amount'       => $ticket->Refund_Amount,
                'calculated_percent'  => $calculatedPercent,
                'Refund_Percentage'   => $ticket->Refund_Percentage,
                'Company_Name'        => $ticket->Company_Name,
                'Refund_Date'         => $ticket->Refund_Date,
                'Refunded_By'         => optional($ticket->refundedBy)->Full_Name ?? 'N/A',
                'Refund_Reason'       => $ticket->Refund_Reason,
                'created_at'          => $ticket->created_at,
            ];
        });

        $companyName = TicketingSeat::where('Company_Id', $companyId)->value('Company_Name');

        return Inertia::render('Company/RefundReport/Index', [
            'tickets'      => $tickets,
            'filters'      => [
                'search'               => $request->search ?? '',
                'refund_from'          => $request->refund_from ?? '',
                'refund_to'            => $request->refund_to ?? '',
                'travel_from'          => $request->travel_from ?? '',
                'travel_to'            => $request->travel_to ?? '',
                'refund_percent_range' => $request->refund_percent_range ?? '',
            ],
            'companyName'  => $companyName,
        ]);
    }

    /**
     * Export refund records to Excel (XLSX) or CSV fallback.
     */
    public function export(Request $request)
    {
        $user = Auth::user();
        $companyId = $user->Company_Id;

        if (!in_array($user->User_Type, ['CompanyOwner', 'CompanyUser'])) {
            abort(403);
        }

        // Build the same filtered query as index()
        $query = TicketingSeat::query()
            ->with('refundedBy')
            ->where('Company_Id', $companyId)
            ->where('Status', 'Cancelled')
            ->whereNotNull('Refund_Amount')
            ->orderBy('Refund_Date', 'desc');

        // Apply filters
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('PNR_No', 'like', "%{$search}%")
                  ->orWhere('Passenger_Name', 'like', "%{$search}%")
                  ->orWhere('Contact_No', 'like', "%{$search}%");
            });
        }

        if ($request->filled('refund_from')) {
            $query->whereDate('Refund_Date', '>=', $request->refund_from);
        }
        if ($request->filled('refund_to')) {
            $query->whereDate('Refund_Date', '<=', $request->refund_to);
        }
        if ($request->filled('travel_from')) {
            $query->whereDate('Travel_Date', '>=', $request->travel_from);
        }
        if ($request->filled('travel_to')) {
            $query->whereDate('Travel_Date', '<=', $request->travel_to);
        }
        if ($request->filled('refund_percent_range')) {
            $range = $request->refund_percent_range;
            switch ($range) {
                case '90-100':
                    $query->whereBetween('Refund_Percentage', [90, 100]);
                    break;
                case '75-89':
                    $query->whereBetween('Refund_Percentage', [75, 89]);
                    break;
                case '50-74':
                    $query->whereBetween('Refund_Percentage', [50, 74]);
                    break;
                case '1-49':
                    $query->whereBetween('Refund_Percentage', [1, 49]);
                    break;
                case '0':
                    $query->where('Refund_Percentage', 0);
                    break;
            }
        }

        $tickets = $query->get();

        // Fallback to CSV if PhpSpreadsheet is not installed
        if (!class_exists('\PhpOffice\PhpSpreadsheet\Spreadsheet')) {
            return $this->exportToCsvFallback($tickets, $companyId);
        }

        // Statistics for summary
        $stats = [
            'total'     => $tickets->count(),
            'total_refund' => $tickets->sum('Refund_Amount'),
            'avg_percent' => $tickets->avg(function ($t) {
                $fare = (float) $t->Fare;
                return $fare > 0 ? round(($t->Refund_Amount / $fare) * 100, 1) : 0;
            }),
        ];

        $spreadsheet = new \PhpOffice\PhpSpreadsheet\Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('Refund Report');

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

        $lastCol = 'O'; // A–O (15 columns)
        $headerRow = 5;

        // Row 1 – Title
        $companyName = $tickets->first()?->Company_Name ?? 'Company';
        $sheet->setCellValue('A1', strtoupper($companyName) . ' — REFUND REPORT');
        $sheet->mergeCells("A1:{$lastCol}1");
        $sheet->getStyle("A1:{$lastCol}1")->applyFromArray(array_merge($centerAlign, [
            'font' => ['bold' => true, 'size' => 16, 'color' => ['argb' => 'FFFFFFFF']],
            'fill' => ['fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID, 'startColor' => ['argb' => 'FFC62828']],
        ]));
        $sheet->getRowDimension(1)->setRowHeight(28);

        // Row 2 – Generated on
        $sheet->setCellValue('A2', 'Generated on: ' . now()->format('d M Y — H:i:s'));
        $sheet->mergeCells("A2:{$lastCol}2");
        $sheet->getStyle("A2:{$lastCol}2")->applyFromArray(array_merge($centerAlign, [
            'font' => ['italic' => true, 'size' => 10],
            'fill' => ['fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID, 'startColor' => ['argb' => 'FFFCE4EC']],
        ]));

        // Row 3 – Filter summary
        $sheet->setCellValue('A3', 'Filters: ' . $this->getExcelFilterSummary($request));
        $sheet->mergeCells("A3:{$lastCol}3");
        $sheet->getStyle("A3:{$lastCol}3")->applyFromArray([
            'font' => ['bold' => true, 'size' => 10],
            'fill' => ['fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID, 'startColor' => ['argb' => 'FFE0E0E0']],
            'alignment' => ['horizontal' => \PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_LEFT, 'wrapText' => true],
        ]);
        $sheet->getRowDimension(3)->setRowHeight(20);

        // Row 4 – spacer
        $sheet->getRowDimension(4)->setRowHeight(6);

        // Row 5 – Column headers
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
            'J' => 'Refund Amount (PKR)',
            'K' => 'Refund %',
            'L' => 'Refund Date',
            'M' => 'Refunded By',
            'N' => 'Refund Reason',
            'O' => 'Status',
        ];

        foreach ($headers as $col => $label) {
            $sheet->setCellValue($col . $headerRow, $label);
        }

        $sheet->getStyle("A{$headerRow}:{$lastCol}{$headerRow}")->applyFromArray(array_merge($centerAlign, [
            'font' => ['bold' => true, 'size' => 10, 'color' => ['argb' => 'FFFFFFFF']],
            'fill' => ['fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID, 'startColor' => ['argb' => 'FFE53935']],
            'borders' => ['bottom' => ['borderStyle' => \PhpOffice\PhpSpreadsheet\Style\Border::BORDER_MEDIUM]],
        ]));
        $sheet->getRowDimension($headerRow)->setRowHeight(20);

        // Data rows
        $row = $headerRow + 1;
        $totalFare = 0;
        $totalRefund = 0;

        foreach ($tickets as $ticket) {
            $fromCity = $this->getCompanyCityName($companyId, (int) $ticket->Source_ID);
            $toCity   = $this->getCompanyCityName($companyId, (int) $ticket->Destination_ID);
            $fare = (float) $ticket->Fare;
            $refundAmount = (float) $ticket->Refund_Amount;
            $calculatedPercent = $fare > 0 ? round(($refundAmount / $fare) * 100, 1) : 0;

            // Format seats
            $seats = $ticket->Seat_No;
            try {
                $seatsArr = json_decode($seats, true);
                $seats = is_array($seatsArr) ? implode(', ', $seatsArr) : $seats;
            } catch (\Exception $e) {}

            $sheet->setCellValue('A' . $row, $ticket->PNR_No);
            $sheet->setCellValue('B' . $row, $ticket->Passenger_Name);
            $sheet->setCellValue('C' . $row, $ticket->Contact_No);
            $sheet->setCellValue('D' . $row, $fromCity);
            $sheet->setCellValue('E' . $row, $toCity);
            $sheet->setCellValue('F' . $row, $ticket->Travel_Date ? \Carbon\Carbon::parse($ticket->Travel_Date)->format('d-m-Y') : '');
            $sheet->setCellValue('G' . $row, $ticket->Travel_Time ?? '');
            $sheet->setCellValue('H' . $row, $seats);
            $sheet->setCellValue('I' . $row, $fare);
            $sheet->setCellValue('J' . $row, $refundAmount);
            $sheet->setCellValue('K' . $row, $calculatedPercent . '%');
            $sheet->setCellValue('L' . $row, $ticket->Refund_Date ? \Carbon\Carbon::parse($ticket->Refund_Date)->format('d-m-Y H:i') : '');
            $sheet->setCellValue('M' . $row, optional($ticket->refundedBy)->Full_Name ?? 'N/A');
            $sheet->setCellValue('N' . $row, $ticket->Refund_Reason ?? '');
            $sheet->setCellValue('O' . $row, 'Cancelled');

            $totalFare += $fare;
            $totalRefund += $refundAmount;
            $row++;
        }

        $lastDataRow = $row - 1;
        if ($lastDataRow >= $headerRow + 1) {
            $sheet->getStyle("I" . ($headerRow + 1) . ":J{$lastDataRow}")->getNumberFormat()->setFormatCode('#,##0.00');
            $sheet->getStyle("A{$headerRow}:{$lastCol}{$lastDataRow}")->applyFromArray($thinBorder);
        }

        // Summary section
        $row++;
        $sheet->setCellValue('I' . $row, 'REPORT SUMMARY');
        $sheet->mergeCells("I{$row}:{$lastCol}{$row}");
        $sheet->getStyle("I{$row}:{$lastCol}{$row}")->applyFromArray(array_merge($centerAlign, [
            'font' => ['bold' => true, 'size' => 11, 'color' => ['argb' => 'FFFFFFFF']],
            'fill' => ['fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID, 'startColor' => ['argb' => 'FFC62828']],
        ]));
        $row++;

        $netDeduction = $totalFare - $totalRefund;
        $summaryRows = [
            ['Total Refunded Tickets:', $stats['total'], false, 'FFFCE4EC', 'FFFDE8EF', ''],
            ['Total Fare (PKR):', $totalFare, false, 'FFF1F8E9', 'FFE8F5E9', '#,##0.00'],
            ['Total Refund Amount (PKR):', $totalRefund, false, 'FFFCE4EC', 'FFFDE8EF', '#,##0.00'],
            ['Net Deduction (PKR):', $netDeduction, true, 'FF212121', 'FF212121', '#,##0.00'],
            ['Average Refund %:', round($stats['avg_percent'], 1) . '%', false, 'FFF1F8E9', 'FFE8F5E9', ''],
        ];

        foreach ($summaryRows as [$label, $value, $bold, $labelBg, $valueBg, $numFmt]) {
            $isNetRow = ($bold && $labelBg === 'FF212121');
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

        // Footer
        $row += 2;
        $sheet->setCellValue('A' . $row, 'Report generated by ' . $companyName . ' via Royal Movers System  •  ' . now()->format('d M Y H:i'));
        $sheet->mergeCells("A{$row}:{$lastCol}{$row}");
        $sheet->getStyle("A{$row}:{$lastCol}{$row}")->applyFromArray(array_merge($centerAlign, [
            'font' => ['italic' => true, 'size' => 9],
            'fill' => ['fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID, 'startColor' => ['argb' => 'FFFCE4EC']],
        ]));

        // Column widths
        $widths = [
            'A' => 18, 'B' => 22, 'C' => 16, 'D' => 16, 'E' => 16,
            'F' => 14, 'G' => 12, 'H' => 12, 'I' => 16, 'J' => 18,
            'K' => 12, 'L' => 20, 'M' => 18, 'N' => 30, 'O' => 12,
        ];
        foreach ($widths as $col => $width) {
            $sheet->getColumnDimension($col)->setWidth($width);
        }

        $sheet->freezePane('A' . ($headerRow + 1));
        $spreadsheet->getDefaultStyle()->getFont()->setName('Arial')->setSize(10);

        $filename = 'Refund_Report_' . now()->format('Ymd_His') . '.xlsx';
        $writer = new \PhpOffice\PhpSpreadsheet\Writer\Xlsx($spreadsheet);
        $writer->setPreCalculateFormulas(false);

        return response()->stream(
            function () use ($writer) { $writer->save('php://output'); },
            200,
            [
                'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
                'Content-Disposition' => 'attachment; filename="' . $filename . '"',
                'Cache-Control' => 'max-age=0',
            ]
        );
    }

    /**
     * Get company‑specific city name using CompanyCity mapping.
     */
    private function getCompanyCityName(int $operatorId, int $globalCityId): string
    {
        try {
            $mapping = CompanyCity::where('company_id', $operatorId)
                ->where('city_id', $globalCityId)
                ->where('active', true)
                ->first();

            if ($mapping && $mapping->key_id) {
                $cityName = City::where('id', $mapping->key_id)->value('City_Name');
                if ($cityName) return $cityName;
            }
        } catch (\Exception $e) {
            \Log::warning('getCompanyCityName failed in RefundReport', [
                'operator_id' => $operatorId,
                'global_city_id' => $globalCityId,
            ]);
        }
        return City::where('id', $globalCityId)->value('City_Name') ?? 'Unknown';
    }

    /**
     * Generate human-readable filter summary for Excel.
     */
    protected function getExcelFilterSummary(Request $request): string
    {
        $summary = [];

        if ($request->filled('search')) {
            $summary[] = 'Search: "' . $request->search . '"';
        }
        if ($request->filled('refund_from') || $request->filled('refund_to')) {
            $from = $request->refund_from ? \Carbon\Carbon::parse($request->refund_from)->format('d/m/Y') : 'Any';
            $to   = $request->refund_to   ? \Carbon\Carbon::parse($request->refund_to)->format('d/m/Y')   : 'Any';
            $summary[] = 'Refund Date: ' . $from . ' to ' . $to;
        }
        if ($request->filled('travel_from') || $request->filled('travel_to')) {
            $from = $request->travel_from ? \Carbon\Carbon::parse($request->travel_from)->format('d/m/Y') : 'Any';
            $to   = $request->travel_to   ? \Carbon\Carbon::parse($request->travel_to)->format('d/m/Y')   : 'Any';
            $summary[] = 'Travel Date: ' . $from . ' to ' . $to;
        }
        if ($request->filled('refund_percent_range')) {
            $summary[] = 'Refund %: ' . $request->refund_percent_range;
        }

        return !empty($summary) ? implode(' | ', $summary) : 'All Refunds';
    }

    /**
     * Build Excel range strings from an array of row numbers.
     */
    private function buildExcelRanges(array $rows): array
    {
        if (empty($rows)) return [];
        sort($rows);
        $ranges = [];
        $start = $end = $rows[0];
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

    /**
     * Fallback CSV export if PhpSpreadsheet is not available.
     */
    private function exportToCsvFallback($tickets, int $companyId)
    {
        $filename = 'Refund_Report_' . now()->format('Ymd_His') . '.csv';

        return response()->stream(function () use ($tickets, $companyId) {
            $file = fopen('php://output', 'w');
            fputcsv($file, [
                'PNR No', 'Passenger', 'Contact', 'From', 'To', 'Travel Date', 'Travel Time',
                'Seat No', 'Fare', 'Refund Amount', 'Refund %', 'Refund Date', 'Refunded By', 'Refund Reason', 'Status'
            ]);

            foreach ($tickets as $ticket) {
                $fromCity = $this->getCompanyCityName($companyId, (int) $ticket->Source_ID);
                $toCity   = $this->getCompanyCityName($companyId, (int) $ticket->Destination_ID);
                $fare = (float) $ticket->Fare;
                $refundAmount = (float) $ticket->Refund_Amount;
                $calculatedPercent = $fare > 0 ? round(($refundAmount / $fare) * 100, 1) : 0;

                fputcsv($file, [
                    $ticket->PNR_No,
                    $ticket->Passenger_Name,
                    $ticket->Contact_No,
                    $fromCity,
                    $toCity,
                    $ticket->Travel_Date ? \Carbon\Carbon::parse($ticket->Travel_Date)->format('d-m-Y') : '',
                    $ticket->Travel_Time,
                    $ticket->Seat_No,
                    $fare,
                    $refundAmount,
                    $calculatedPercent . '%',
                    $ticket->Refund_Date ? \Carbon\Carbon::parse($ticket->Refund_Date)->format('d-m-Y H:i') : '',
                    optional($ticket->refundedBy)->Full_Name ?? 'N/A',
                    $ticket->Refund_Reason ?? '',
                    'Cancelled',
                ]);
            }
            fclose($file);
        }, 200, [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => 'attachment; filename="' . $filename . '"',
        ]);
    }
}
