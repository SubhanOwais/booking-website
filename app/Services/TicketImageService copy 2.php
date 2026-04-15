<?php

namespace App\Services;

use App\Models\TicketingSeat;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Spatie\Browsershot\Browsershot;
use Carbon\Carbon;

class TicketImageService
{
    private function getCompanyLogo($companyId)
    {
        try {
            $company = \App\Models\Company::where('company_id', (int) $companyId)
                ->where('is_active', true)
                ->first(['company_logo']);

            if ($company && $company->company_logo) {
                $logoPath = storage_path('app/public/' . $company->company_logo);

                if (file_exists($logoPath)) {
                    $imageData = base64_encode(file_get_contents($logoPath));
                    $ext       = strtolower(pathinfo($logoPath, PATHINFO_EXTENSION));
                    $mimeType  = $ext === 'png' ? 'image/png' : 'image/jpeg';
                    return "data:{$mimeType};base64,{$imageData}";
                }
            }
        } catch (\Exception $e) {
            Log::warning('getCompanyLogo failed', ['company_id' => $companyId, 'error' => $e->getMessage()]);
        }

        // Fallback to default logo
        $defaultLogo = public_path('images/logo.png');
        if (file_exists($defaultLogo)) {
            $imageData = base64_encode(file_get_contents($defaultLogo));
            return 'data:image/png;base64,' . $imageData;
        }

        return null;
    }

    /**
     * Check if Browsershot is available and properly configured
     */
    private function checkBrowsershotAvailability()
    {
        try {
            // Check if Node.js is available
            $nodeCheck = shell_exec('node --version 2>&1');
            $npmCheck = shell_exec('npm --version 2>&1');

            $diagnostics = [
                'node_available' => !empty($nodeCheck),
                'node_version' => trim($nodeCheck ?? 'Not found'),
                'npm_available' => !empty($npmCheck),
                'npm_version' => trim($npmCheck ?? 'Not found'),
            ];

            Log::info('Browsershot diagnostics:', $diagnostics);

            return $diagnostics['node_available'] && $diagnostics['npm_available'];

        } catch (\Exception $e) {
            Log::error('Browsershot availability check failed: ' . $e->getMessage());
            return false;
        }
    }


    /**
     * Generate ticket image from booking data
     */
    public function generateTicketImage($customerData)
    {
        try {
            // Create unique filename
            $filename = 'ticket_' . $customerData['pnr_no'] . '_' . $customerData['Customer_Id'] . '_' . time() . '.jpg';
            $ticketUrl = asset('storage/tickets/' . $filename);
            $path = storage_path('app/public/tickets/' . $filename);

            // Ensure directory exists
            if (!file_exists(storage_path('app/public/tickets'))) {
                mkdir(storage_path('app/public/tickets'), 0755, true);
            }

            // Remove old tickets
            $this->removeOldTickets($customerData['pnr_no'], $customerData['Customer_Id']);

            // Generate HTML content with QR code
            $html = $this->generateTicketHtml($customerData, $ticketUrl);

            // Calculate dynamic height
            $seatsCount = !empty($customerData['seats']) ? count($customerData['seats']) : 1;
            $baseHeight = 680; // Increased base height for better quality
            $heightPerSeat = 26;
            $dynamicHeight = max($baseHeight, $baseHeight + (($seatsCount - 1) * $heightPerSeat));

            // Try Browsershot with HIGH QUALITY settings
            try {
                Browsershot::html($html)
                    ->windowSize(1200, $dynamicHeight * 1.5) // Higher resolution
                    ->deviceScaleFactor(2) // Retina/2x quality
                    ->setScreenshotType('jpeg', 100) // 100% quality
                    ->dismissDialogs()
                    ->timeout(10) // Increased timeout for high quality
                    ->setOption('args', [
                        '--no-sandbox',
                        '--disable-setuid-sandbox',
                        '--disable-dev-shm-usage',
                        '--disable-gpu',
                        '--headless',
                        '--disable-software-rasterizer',
                        '--hide-scrollbars',
                        '--mute-audio',
                        '--disable-web-security',
                        '--font-render-hinting=full', // Better font rendering
                        '--force-color-profile=srgb', // Better color accuracy
                    ])
                    ->save($path);

                // Optional: If you want even higher quality, you can use imagemagick to enhance
                if (extension_loaded('imagick')) {
                    $imagick = new \Imagick($path);
                    $imagick->setImageResolution(300, 300); // Set DPI
                    $imagick->setImageCompressionQuality(100);
                    $imagick->writeImage($path);
                    $imagick->clear();
                    $imagick->destroy();
                }

                return [
                    'path' => $path,
                    'url' => $ticketUrl,
                    'filename' => $filename,
                    'method' => 'browsershot-high-quality'
                ];

            } catch (\Exception $browsershotError) {
                // Fallback to regular quality if high quality fails
                try {
                    Log::warning('High quality generation failed, trying regular quality: ' . $browsershotError->getMessage());

                    Browsershot::html($html)
                        ->windowSize(800, $dynamicHeight)
                        ->setScreenshotType('jpeg', 90)
                        ->dismissDialogs()
                        ->timeout(5)
                        ->setOption('args', [
                            '--no-sandbox',
                            '--disable-setuid-sandbox',
                            '--disable-dev-shm-usage',
                            '--headless'
                        ])
                        ->save($path);

                    return [
                        'path' => $path,
                        'url' => $ticketUrl,
                        'filename' => $filename,
                        'method' => 'browsershot-regular'
                    ];

                } catch (\Exception $fallbackError) {
                    // Immediately use GD fallback - don't retry
                    $this->generateFallbackTicketImage($customerData, $path);

                    return [
                        'path' => $path,
                        'url' => $ticketUrl,
                        'filename' => $filename,
                        'method' => 'fallback'
                    ];
                }
            }

        } catch (\Exception $e) {
            $this->cleanupTempFiles($customerData['pnr_no']);
            throw new \Exception('Ticket generation failed: ' . $e->getMessage());
        }
    }

    /**
     * Clean up temporary HTML files
     */
    private function cleanupTempFiles($pnrNo)
    {
        try {
            $ticketDirectory = storage_path('app/public/tickets/');

            if (!is_dir($ticketDirectory)) {
                return;
            }

            // Remove temporary HTML files for this PNR
            $tempFiles = glob($ticketDirectory . 'temp_' . $pnrNo . '_*.html');
            foreach ($tempFiles as $file) {
                if (is_file($file)) {
                    unlink($file);
                }
            }

        } catch (\Exception $e) {
            Log::warning('Error cleaning up temp files: ' . $e->getMessage());
        }
    }

    /**
     * Generate QR code as base64 data URI - OPTIMIZED
     */
    private function generateQrCode($url)
    {
        try {
            // Use faster QR Server API with shorter timeout
            $qrCodeApiUrl = 'https://api.qrserver.com/v1/create-qr-code/?size=100x100&data=' . urlencode($url);

            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, $qrCodeApiUrl);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
            curl_setopt($ch, CURLOPT_TIMEOUT, 2); // Reduced from 10 to 2 seconds
            curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 1); // Added 1 second connection timeout
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);

            $imageData = curl_exec($ch);
            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            curl_close($ch);

            if ($httpCode === 200 && $imageData !== false && strlen($imageData) > 100) {
                $base64 = base64_encode($imageData);
                return 'data:image/png;base64,' . $base64;
            }

        } catch (\Exception $e) {
            // Skip to fallback immediately
        }

        // Skip Google Charts API - go straight to local generation
        return $this->generatePlaceholderQrCode($url);
    }

    /**
     * Generate a placeholder QR code image using GD library
     */
    private function generatePlaceholderQrCode($url)
    {
        try {
            // Create a 100x100 image
            $image = imagecreatetruecolor(100, 100);

            // Set colors
            $white = imagecolorallocate($image, 255, 255, 255);
            $black = imagecolorallocate($image, 0, 0, 0);
            $gray = imagecolorallocate($image, 127, 17, 77); // #7F114D

            // Fill background white
            imagefill($image, 0, 0, $white);

            // Draw border
            imagerectangle($image, 0, 0, 99, 99, $black);
            imagerectangle($image, 1, 1, 98, 98, $black);

            // Draw corner markers (like QR code position markers)
            // Top-left
            imagefilledrectangle($image, 5, 5, 25, 25, $black);
            imagefilledrectangle($image, 8, 8, 22, 22, $white);
            imagefilledrectangle($image, 11, 11, 19, 19, $black);

            // Top-right
            imagefilledrectangle($image, 74, 5, 94, 25, $black);
            imagefilledrectangle($image, 77, 8, 91, 22, $white);
            imagefilledrectangle($image, 80, 11, 88, 19, $black);

            // Bottom-left
            imagefilledrectangle($image, 5, 74, 25, 94, $black);
            imagefilledrectangle($image, 8, 77, 22, 91, $white);
            imagefilledrectangle($image, 11, 80, 19, 88, $black);

            // Draw some random QR-like pattern in the middle
            for ($x = 30; $x < 70; $x += 3) {
                for ($y = 30; $y < 70; $y += 3) {
                    if (rand(0, 1)) {
                        imagefilledrectangle($image, $x, $y, $x + 2, $y + 2, $black);
                    }
                }
            }

            // Add "SCAN ME" text at bottom
            imagestring($image, 2, 30, 85, "SCAN ME", $gray);

            // Capture image as PNG
            ob_start();
            imagepng($image);
            $imageData = ob_get_clean();
            imagedestroy($image);

            $base64 = base64_encode($imageData);
            Log::info('Generated placeholder QR code using GD');
            return 'data:image/png;base64,' . $base64;

        } catch (\Exception $e) {
            Log::error('Placeholder QR generation failed: ' . $e->getMessage());
            return null;
        }
    }

    /**
     * Remove old tickets for same PNR and User
     */
    private function removeOldTickets($pnrNo, $customerId)
    {
        try {
            $ticketDirectory = storage_path('app/public/tickets/');

            if (!is_dir($ticketDirectory)) {
                return;
            }

            // Get all files in the tickets directory
            $files = glob($ticketDirectory . 'ticket_' . $pnrNo . '_' . $customerId . '_*.jpg');

            // Also get files with old naming pattern (without customer ID)
            $oldPatternFiles = glob($ticketDirectory . 'ticket_' . $pnrNo . '_*.jpg');

            // Merge both file arrays
            $allFiles = array_merge($files, $oldPatternFiles);

            // Remove duplicates
            $allFiles = array_unique($allFiles);

            // Delete each file
            foreach ($allFiles as $file) {
                if (is_file($file)) {
                    unlink($file);
                    Log::info('Removed old ticket file: ' . basename($file));
                }
            }

            // Also remove old HTML files
            $htmlFiles = glob($ticketDirectory . $pnrNo . '.html');
            foreach ($htmlFiles as $htmlFile) {
                if (is_file($htmlFile)) {
                    unlink($htmlFile);
                }
            }

            // Clean up temporary HTML files
            $tempFiles = glob($ticketDirectory . 'temp_' . $pnrNo . '_*.html');
            foreach ($tempFiles as $tempFile) {
                if (is_file($tempFile)) {
                    unlink($tempFile);
                }
            }

        } catch (\Exception $e) {
            Log::error('Error removing old tickets: ' . $e->getMessage());
        }
    }

    /**
     * Get service type name
     */
    private function getServiceTypeName($serviceTypeId)
    {
        $services = [
            1  => 'LUXURY',
            2  => 'Daewoo',
            3  => 'NONAC 65',
            4  => 'HINO',
            5  => 'HIGH ROOF 14',
            6  => 'HIGH ROOF 18',
            7  => 'LOCAL DAEWOO',
            8  => 'SUPER LUXURY',
            13 => 'Executive',
            14 => 'Executive Plus',
            15 => 'AC SLEEPER',
            16 => 'Executive Plus 41',
            17 => 'Premium Business',
            18 => 'Premium Business 12X28',
            19 => 'Premium Business 9X32',
        ];

        return $services[$serviceTypeId] ?? 'N/A';
    }

    /**
     * Generate HTML for ticket
     */
    private function generateTicketHtml($data, $ticketUrl)
    {
        // [Same as your original - keeping HTML generation identical]
        // Escape all data
        $pnrNo = htmlspecialchars($data['pnr_no']);
        $customerId = htmlspecialchars($data['Customer_Id']);
        $customerName = htmlspecialchars($data['primary_customer_name'] ?? $data['customer_name'] ?? 'N/A');
        $contactNo = htmlspecialchars($data['primary_contact_no'] ?? $data['contact_no'] ?? 'N/A');
        $cnic = htmlspecialchars($data['cnic'] ?? 'N/A');
        $gender = htmlspecialchars($data['gender'] ?? 'N/A');
        $companyName = htmlspecialchars($data['company_name']);
        $helpline = htmlspecialchars($data['helpline'] ?? 'N/A');
        $sourceCity = htmlspecialchars($data['source_city']);
        $destCity = htmlspecialchars($data['dest_city']);
        $totalFare = number_format($data['total_fare'] ?? 0, 2);
        $totalSeats = $data['total_seats'] ?? 1; // ADD THIS LINE

        // Calculate discount amounts for display
        $totalDiscount = number_format($data['total_discount'] ?? 0, 2);
        $totalPaidFare = number_format($data['total_paid_fare'] ?? 0, 2);
        $totalDiscountPercentage = $data['total_fare'] > 0
            ? number_format(($data['total_discount'] / $data['total_fare']) * 100, 1)
            : '0.0';

        // Primary contact info
        $primaryCustomerName = htmlspecialchars($data['primary_customer_name'] ?? '');
        $primaryContactNo = htmlspecialchars($data['primary_contact_no'] ?? '');

        // Get bus type
        $busType = isset($data['Bus_Service']) ? $this->getServiceTypeName($data['Bus_Service']) : 'Standard';

        // Format departure date + time
        $formattedDepartureTime = 'N/A';
        if (isset($data['travel_date']) && isset($data['travel_time'])) {
            try {
                // Combine date and time into a single string
                $dateTimeString = $data['travel_date'] . ' ' . $data['travel_time'];

                // Parse using Carbon
                $carbonDateTime = $data['travel_date'] instanceof Carbon
                    ? $data['travel_date']->copy()->setTimeFromTimeString($data['travel_time'])
                    : Carbon::parse($dateTimeString);

                $formattedDepartureTime = $carbonDateTime->format('h:i A, l, d M Y');
            } catch (\Exception $e) {
                // Fallback to current date and time
                $formattedDepartureTime = now()->format('h:i A, l, d M Y');
            }
        } else {
            $formattedDepartureTime = now()->format('h:i A, l, d M Y');
        }

        $collection_point = htmlspecialchars($data['collection_point']);
        $Boarding_Terminal = htmlspecialchars($data['Boarding_Terminal'] ?? 'N/A');

        // Format booking time
        $bookingTime = 'N/A';
        try {
            $bookingTime = Carbon::parse($data['issue_date'])->format('h:i A, l, d M Y');
        } catch (\Exception $e) {
            $bookingTime = now()->format('h:i A, l, d M Y');
        }

        // Get company logo (base64)
        $companyLogoUrl = $this->getCompanyLogo($data['operator_id'] ?? $data['company_id'] ?? null);

        // Generate QR code (base64)
        $qrCodeUrl = $this->generateQrCode($ticketUrl);

        $seats = $data['seats'] ?? [];

        // Start HTML
        $html = <<<HTML
<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Ticket - {$pnrNo}</title>
    <style>
    @page { margin: 0; padding: 0; }
    * {
        margin: 0;
        padding: 0;
        box-sizing: border-box;
        -webkit-font-smoothing: antialiased;
        -moz-osx-font-smoothing: grayscale;
        text-rendering: optimizeLegibility;
    }
    body {
        font-family: 'Segoe UI', 'Arial', sans-serif;
        margin: 0;
        padding: 20px;
        font-size: 12px;
        line-height: 1.4;
        background: linear-gradient(to bottom, #ffffff, #f9f9f9);
        width: 1200px;
        color: #333;
    }
    .ticket-container {
        width: 1100px;
        margin: 0 auto;
        border-radius: 15px;
        padding: 20px;
        background: white;
        position: relative;
        overflow: hidden;
    }
    .customer-name {
        font-size: 28px;
        color: #5e0009;
        font-weight: 700;
        margin-bottom: 5px;
        text-shadow: 1px 1px 2px rgba(0,0,0,0.1);
        letter-spacing: 0.5px;
    }
    .customer-id {
        font-size: 12px;
        color: #555;
        margin-bottom: 3px;
        font-weight: 500;
    }
    .company-name {
        font-size: 22px;
        font-weight: 700;
        color: #5e0009;
        margin: 15px 0 10px 0;
        text-transform: uppercase;
        letter-spacing: 1px;
    }
    .route-section {
        margin: 15px 0;
        display: flex;
        gap: 40px;
        background: linear-gradient(to right, #f8f8f8, #ffffff);
        padding: 15px;
        border-radius: 8px;
        border: 1px solid #e0e0e0;
    }
    .route-label {
        font-weight: 700;
        font-size: 12px;
        margin-bottom: 5px;
        color: #5e0009;
        text-transform: uppercase;
        letter-spacing: 1px;
    }
    .city-name {
        font-size: 18px;
        font-weight: 700;
        color: #5e0009;
        margin-bottom: 0;
        text-transform: uppercase;
        letter-spacing: 1px;
    }
    .detail-row {
        margin-bottom: 8px;
        display: flex;
        align-items: center;
    }
    .detail-label {
        font-weight: 700;
        font-size: 12px;
        color: #5e0009;
        display: inline-block;
        width: 160px;
        min-width: 160px;
    }
    .detail-value {
        display: inline-block;
        font-size: 12px;
        font-weight: 600;
        color: #333;
        flex: 1;
    }
    table {
        width: 100%;
        border-collapse: separate;
        border-spacing: 0;
        border: 3px solid #5e0009;
        border-radius: 8px;
        overflow: hidden;
        margin: 0;
        box-shadow: 0 4px 6px rgba(0,0,0,0.05);
    }
    .passenger-table th, .payment-table th {
        background: linear-gradient(to bottom, #5e0009, #3d0006);
        color: white;
        padding: 12px 10px;
        text-align: left;
        font-weight: 700;
        font-size: 11px;
        text-transform: uppercase;
        letter-spacing: 0.5px;
        border-right: 1px solid rgba(255,255,255,0.1);
    }
    .passenger-table th:last-child, .payment-table th:last-child {
        border-right: none;
    }
    .passenger-table td, .payment-table td {
        padding: 10px;
        border-bottom: 1px solid #e8e8e8;
        font-size: 11px;
        font-weight: 500;
        transition: background-color 0.2s;
    }
    .passenger-table tr:hover td {
        background-color: #f9f9f9;
    }
    .main-note {
        font-weight: 600;
        margin: 15px 0;
        font-size: 11px;
        text-align: center;
        color: #5e0009;
        padding: 12px;
        background: linear-gradient(to right, #fff5f5, #fdecea);
        border-radius: 8px;
        border-left: 4px solid #5e0009 !important;
        border: 1px solid #5e0009;
        line-height: 1.5;
        box-shadow: 0 2px 4px rgba(94,0,9,0.1);
    }
    .Ticket-deail-box {
        border: 3px solid #5e0009;
        padding: 20px;
        border-radius: 10px;
        margin-bottom: 15px;
        background: linear-gradient(to bottom, #ffffff, #fafafa);
        position: relative;
        overflow: hidden;
    }
    .ticket-flex {
        display: flex;
        justify-content: space-between;
        gap: 20px;
    }
    .ticket-left {
        width: 70%;
    }
    .ticket-right {
        width: 30%;
        text-align: center;
        display: flex;
        flex-direction: column;
        align-items: center;
        justify-content: center;
    }
    .top-section {
        display: flex;
        justify-content: space-between;
        align-items: flex-start;
        margin-bottom: 20px;
        padding-bottom: 15px;
        border-bottom: 2px dashed #5e0009;
    }
    .top-left {
        width: 70%;
    }
    .top-right {
        width: 17%;
        text-align: center;
        display: flex;
        flex-direction: column;
        align-items: center;
    }
    .qr-box {
        border: 3px solid #5e0009;
        padding: 10px;
        width: 140px;
        height: 140px;
        display: flex;
        align-items: center;
        justify-content: center;
        background: white;
        border-radius: 8px;
        box-shadow: 0 4px 8px rgba(94,0,9,0.2);
    }
    .qr-code-img {
        width: 120px;
        height: 120px;
        object-fit: contain;
        filter: drop-shadow(0 2px 4px rgba(0,0,0,0.1));
    }
    .qr-label {
        font-size: 10px;
        margin: 5px 0;
        color: #5e0009;
        font-weight: 700;
        text-transform: uppercase;
        letter-spacing: 0.5px;
    }
    .company-logo-img {
        max-width: 140px;
        max-height: 100px;
        object-fit: contain;
        margin-bottom: 10px;
        filter: drop-shadow(0 2px 4px rgba(0,0,0,0.1));
    }
    .section-title {
        font-weight: 700;
        font-size: 18px;
        margin: 20px 0 10px 0;
        color: #5e0009;
        padding-bottom: 0px;
        text-transform: uppercase;
        letter-spacing: 1px;
        position: relative;
    }
    table tr td {
        font-size: 18px;
    }
    .total-row {
        background: linear-gradient(to right, #f5f5f5, #ffffff);
        font-weight: 700;
        font-size: 18px;
    }
    .total-row td {
        border-bottom: none;
        font-size: 18px;
    }
    .discount-highlight {
        color: #28a745;
        font-weight: 700;
        font-size: 11px;
    }
    .small-percentage {
        font-size: 9px;
        color: #6c757d;
        display: block;
        line-height: 1.3;
        margin-top: 2px;
    }
    .discount-cell {
        color: #28a745 !important;
        font-weight: 600;
        display: flex;
        align-items: center;
        gap: 3px;
    }
    .paid-cell {
        color: #5e0009;
        font-weight: 700;
    }
    .payment-summary-row {
        background: linear-gradient(to right, #f0fff4, #ffffff);
    }
    .payment-summary-row td {
        color: #28a745;
        font-weight: 600;
    }
    /* Column widths */
    .passenger-table th:nth-child(1), .passenger-table td:nth-child(1) { width: 5%;  min-width: 40px;  }
    .passenger-table th:nth-child(2), .passenger-table td:nth-child(2) { width: 18%; min-width: 150px; }
    .passenger-table th:nth-child(3), .passenger-table td:nth-child(3) { width: 15%; min-width: 130px; }
    .passenger-table th:nth-child(4), .passenger-table td:nth-child(4) { width: 7%;  min-width: 60px;  }
    .passenger-table th:nth-child(5), .passenger-table td:nth-child(5) { width: 12%; min-width: 100px; }
    .passenger-table th:nth-child(6), .passenger-table td:nth-child(6) { width: 7%;  min-width: 60px;  }
    .passenger-table th:nth-child(7), .passenger-table td:nth-child(7) { width: 12%; min-width: 100px; }
    .passenger-table th:nth-child(8), .passenger-table td:nth-child(8) { width: 14%; min-width: 120px; }
    .passenger-table th:nth-child(9), .passenger-table td:nth-child(9) { width: 10%; min-width: 90px;  }
    .footer-note {
        text-align: center;
        margin-top: 20px;
        font-size: 10px;
        color: #666;
        line-height: 1.4;
        padding-top: 15px;
        border-top: 1px dashed #b0956b;
    }
    /* Ensure content stays above watermark */
    .top-section, .Ticket-deail-box, .section-title, table, .main-note, .footer-note {
        position: relative;
        z-index: 1;
    }
</style>
</head>
<body>
    <div class="ticket-container">
        <!-- Top Section with QR Code -->
        <div class="top-section">
            <div class="top-left">
                <div class="customer-name">{$customerName}</div>
                <div style="display: flex; gap: 10px;">
                    <div class="customer-id">
                        <span style="font-weight: 800;">ID #</span> {$customerId}
                    </div>
                    <div class="customer-id">
                        <span style="font-weight: 800;">Mobile #</span> {$contactNo}
                    </div>
                </div>
                <div class="company-name">
                    {$companyName} <br>
                    <div style="font-weight: bold; color: #7F114D; font-size: 11px;">Help Line: {$helpline}</div>
                </div>
                <div class="route-section">
                    <div>
                        <div class="route-label">ORIGIN</div>
                        <div class="city-name">{$sourceCity}</div>
                    </div>
                    <div>
                        <div class="route-label">DESTINATION</div>
                        <div class="city-name">{$destCity}</div>
                    </div>
                </div>
            </div>
            <div class="top-right">
                <div class="qr-box">
HTML;

        // Add QR code or fallback
        if ($qrCodeUrl) {
            $html .= <<<HTML
                    <img src="{$qrCodeUrl}" alt="QR Code" class="qr-code-img">
HTML;
        } else {
            $html .= <<<HTML
                    <div style="text-align: center;">
                        <div style="font-size: 24px; margin-bottom: 5px;">📋</div>
                        <div style="font-size: 10px; font-weight: bold; color: #7F114D;">
                            PNR: {$pnrNo}
                        </div>
                    </div>
HTML;
        }

        $html .= <<<HTML
                </div>
                <div class="qr-label">SCAN FOR TICKET</div>
                <div class="qr-label">PNR: {$pnrNo}</div>
            </div>
        </div>

        <!-- Ticket Details Box -->
        <div class="Ticket-deail-box">
            <div class="ticket-flex">
                <div class="ticket-left">
                    <div class="detail-row">
                        <span class="detail-label">Bus Type:</span>
                        <span class="detail-value">{$busType}</span>
                    </div>
                    <div class="detail-row">
                        <span class="detail-label">Departure Time:</span>
                        <span class="detail-value">{$formattedDepartureTime}</span>
                    </div>
                    <div class="detail-row">
                        <span class="detail-label">Status:</span>
                        <span class="detail-value">{$collection_point}</span>
                    </div>
                    <div class="detail-row">
                        <span class="detail-label">Boarding Terminal:</span>
                        <span class="detail-value">{$Boarding_Terminal}</span>
                    </div>
                    <div class="detail-row">
                        <span class="detail-label">Booking Time:</span>
                        <span class="detail-value">{$bookingTime}</span>
                    </div>
                </div>
                <div class="ticket-right">
                    <div style="text-align: center;">
HTML;

        // Add company logo or fallback to bus emoji
        if ($companyLogoUrl) {
            $html .= <<<HTML
                        <img src="{$companyLogoUrl}" alt="{$companyName}" class="company-logo-img">
HTML;
        } else {
            $html .= <<<HTML
                        <div style="font-size: 36px; color: #7F114D; margin-bottom: 10px;">🚌</div>
HTML;
        }

        $html .= <<<HTML
                        <!-- <div style="font-weight: bold; color: #7F114D; font-size: 16px;">{$companyName}</div> -->
                    </div>
                </div>
            </div>
        </div>

        <!-- Passenger Details -->
        <div class="section-title">PASSENGER DETAILS</div>
        <table class="passenger-table">
            <thead>
                <tr>
                    <th>SR#</th>
                    <th>PASSENGER NAME</th>
                    <th>CNIC/PASSPORT</th>
                    <th>GENDER</th>
                    <th>CONTACT</th>
                    <th>SEAT#</th>
                    <th>FARE</th>
                    <th>DISCOUNT</th>
                    <th>PAID</th>
                </tr>
            </thead>
            <tbody>
HTML;
// Add passenger rows with individual details
$counter = 1;
$totalFareSum = 0;
$totalDiscountSum = 0;
$totalPaidFareSum = 0;

if (!empty($seats)) {
    foreach ($seats as $seat) {
        $seatNo = htmlspecialchars($seat['seat_no'] ?? 'N/A');
        $passengerName = htmlspecialchars($seat['passenger_name'] ?? $customerName);
        $passengerCnic = htmlspecialchars($seat['cnic'] ?? $cnic);
        $passengerGender = htmlspecialchars($seat['gender'] ?? $gender);
        $passengerContact = htmlspecialchars($seat['contact_no'] ?? $contactNo);
        $fare = number_format(($seat['fare'] ?? 0) + ($seat['discount'] ?? 0),2);
        $discount = number_format($seat['discount'] ?? 0, 2);
        $originalFare = ($seat['fare'] ?? 0) + ($seat['discount'] ?? 0);
        $paidFare = number_format($originalFare - ($seat['discount'] ?? 0), 2);

        // Calculate seat discount percentage
        $seatDiscountPercentage = $seat['fare'] > 0
            ? number_format(($seat['discount'] / $seat['fare']) * 100, 1)
            : '0.0';

        // Calculate totals
        $totalFareSum += $seat['fare'] ?? 0;
        $totalDiscountSum += $seat['discount'] ?? 0;
        $totalPaidFareSum += ($seat['fare'] ?? 0) - ($seat['discount'] ?? 0);

        // Show discount details
        $discountDisplay = $seat['discount'] > 0
            ? "<span class='discount-highlight'>{$discount}</span><span class='small-percentage'>({$seatDiscountPercentage}%)</span>"
            : "PKR 0.00<span class='small-percentage'>(0.0%)</span>";

        $html .= <<<HTML
                <tr>
                    <td>{$counter}</td>
                    <td>{$passengerName}</td>
                    <td>{$passengerCnic}</td>
                    <td>{$passengerGender}</td>
                    <td>{$passengerContact}</td>
                    <td>{$seatNo}</td>
                    <td>PKR {$fare}</td>
                    <td class="discount-cell">{$discountDisplay}</td>
                    <td class="paid-cell">PKR {$paidFare}</td>
                </tr>
HTML;
                $counter++;
            }
        }  else {
    // Fallback if no seats data - show primary passenger only
    $totalFareFormatted = number_format($data['total_fare'] ?? 0, 2);
    $totalDiscountFormatted = number_format($data['total_discount'] ?? 0, 2);
    $totalPaidFareFormatted = number_format(($data['total_fare'] ?? 0) - ($data['total_discount'] ?? 0), 2);
    $totalDiscountPercentage = ($data['total_fare'] ?? 0) > 0
        ? number_format(($data['total_discount'] ?? 0) / ($data['total_fare'] ?? 0) * 100, 1)
        : '0.0';

    $html .= <<<HTML
                <tr>
                    <td>1</td>
                    <td>{$customerName}</td>
                    <td>{$cnic}</td>
                    <td>{$gender}</td>
                    <td>{$contactNo}</td>
                    <td>N/A</td>
                    <td>PKR {$totalFareFormatted}</td>
                    <td class="discount-cell">
                        <span class='discount-highlight'>PKR {$totalDiscountFormatted}</span><br>
                        <!-- <span class='small-percentage'>({$totalDiscountPercentage}%)</span> -->
                    </td>
                    <td class="paid-cell">PKR {$totalPaidFareFormatted}</td>
                </tr>
HTML;
}

        // Add total row
        $html .= <<<HTML
                <tr class="total-row">
                    <td colspan="6" style="font-weight: bold;"></td>
                    <td style="font-weight: bold;">TOTAL:</td>
                    <td style="font-weight: bold;">PKR {$totalFare}</td>
                </tr>
            </tbody>
        </table>

        <!-- Payment Details -->
        <div class="section-title">PAYMENT DETAILS</div>
        <table class="payment-table">
            <thead>
                <tr>
                    <th>Payment Method</th>
                    <th>Refunded Amount</th>
                    <th>Service Charges</th>
                    <th>Paid Amount</th>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <td>Online Payment</td>
                    <td>PKR {$totalFare}</td>
                    <td>PKR 0</td>
                    <td style="font-weight: bold;">PKR {$totalFare}</td>
                </tr>
            </tbody>
        </table>

        <!-- Important Notes -->
        <div class="main-note">
            📢 IMPORTANT: Please arrive 30 minutes before departure time.<br>
            📋 Bring your original CNIC/Passport and this ticket.<br>
            🔒 All discounted tickets are non-refundable and non-transferable.
        </div>

        <!-- Footer -->
        <div style="text-align: center; margin-top: 12px; font-size: 9px; color: #666; line-height: 1.3;">
            <div>Ticket Generated: {$bookingTime}</div>
            <div>For assistance, contact: {$companyName} Customer Support</div>
            <div>© {$companyName} - All Rights Reserved</div>
        </div>
    </div>
</body>
</html>
HTML;

        return $html;
    }

    /**
     * Fallback method to generate ticket image using GD library
     */
    private function generateFallbackTicketImage($data, $path)
    {
        try {
            // Calculate dynamic height based on number of seats
            $seatsCount = !empty($data['seats']) ? count($data['seats']) : 1;
            $baseHeight = 800;
            $heightPerSeat = 30;
            $dynamicHeight = $baseHeight + ($seatsCount * $heightPerSeat);

            // Create a simple image with GD - Dynamic height
            $image = imagecreate(800, $dynamicHeight);

            // Set colors
            $backgroundColor = imagecolorallocate($image, 255, 255, 255); // White
            $textColor = imagecolorallocate($image, 127, 17, 77); // #7F114D
            $headerColor = imagecolorallocate($image, 192, 0, 0); // Red

            // Fill background
            imagefill($image, 0, 0, $backgroundColor);

            // Add header
            imagestring($image, 5, 20, 20, "TICKET - " . $data['pnr_no'], $headerColor);
            imagestring($image, 3, 20, 50, "Passenger: " . $data['customer_name'], $textColor);
            imagestring($image, 3, 20, 80, "PNR: " . $data['pnr_no'], $textColor);
            imagestring($image, 3, 20, 110, "From: " . $data['source_city'], $textColor);
            imagestring($image, 3, 20, 140, "To: " . $data['dest_city'], $textColor);
            imagestring($image, 3, 20, 170, "Total Fare: PKR " . $data['total_fare'], $textColor);
            imagestring($image, 3, 20, 200, "Company: " . $data['company_name'], $textColor);

            // Add seats info
            $yPosition = 230;
            if (!empty($data['seats'])) {
                imagestring($image, 3, 20, $yPosition, "Seats: ", $textColor);
                $yPosition += 20;
                foreach ($data['seats'] as $index => $seat) {
                    $seatInfo = "Seat " . ($index + 1) . ": " . ($seat['seat_no'] ?? 'N/A') . " - PKR " . number_format($seat['fare'] ?? 0, 2);
                    imagestring($image, 2, 40, $yPosition, $seatInfo, $textColor);
                    $yPosition += 20;
                }
            }

            // Add note
            imagestring($image, 2, 20, $yPosition + 20, "This is a digital ticket. Please show this at boarding.", $textColor);
            imagestring($image, 2, 20, $yPosition + 40, "Generated on: " . date('Y-m-d H:i:s'), $textColor);

            // Save image
            imagejpeg($image, $path, 90);
            imagedestroy($image);

            Log::warning('Ticket generated using GD fallback (Browsershot failed): ' . basename($path));

        } catch (\Exception $e) {
            Log::error('GD fallback also failed: ' . $e->getMessage());
            // Create a text file as last resort
            file_put_contents($path,
                "TICKET: " . $data['pnr_no'] . "\n" .
                "Passenger: " . $data['customer_name'] . "\n" .
                "From: " . $data['source_city'] . " To: " . $data['dest_city'] . "\n" .
                "Fare: PKR " . $data['total_fare'] . "\n" .
                "Company: " . $data['company_name'] . "\n" .
                "Generated: " . date('Y-m-d H:i:s')
            );
        }
    }
}
