<?php

namespace App\Services;

use App\Models\TicketingSeat;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Spatie\Browsershot\Browsershot;
use Carbon\Carbon;

class TicketImageService
{
    // ─────────────────────────────────────────────────────────────────────────
    // ENTRY POINT
    // ─────────────────────────────────────────────────────────────────────────

    public function generateTicketImage($customerData)
    {
        $filename  = 'ticket_' . $customerData['pnr_no'] . '_' . $customerData['Customer_Id'] . '_' . time() . '.jpg';
        $ticketUrl = asset('storage/tickets/' . $filename);
        $path      = storage_path('app/public/tickets/' . $filename);

        // Ensure directory exists
        if (!file_exists(storage_path('app/public/tickets'))) {
            mkdir(storage_path('app/public/tickets'), 0755, true);
        }

        $this->removeOldTickets($customerData['pnr_no'], $customerData['Customer_Id']);

        $html         = $this->generateTicketHtml($customerData, $ticketUrl);
        $seatsCount   = !empty($customerData['seats']) ? count($customerData['seats']) : 1;
        $dynamicHeight = max(680, 680 + (($seatsCount - 1) * 26));

        // ── Strategy 1: wkhtmltoimage (most stable — system binary) ──────────
        if ($this->tryWkhtmltoimage($html, $path, $dynamicHeight)) {
            Log::info('Ticket generated via wkhtmltoimage', ['pnr' => $customerData['pnr_no']]);
            return $this->result($path, $ticketUrl, $filename, 'wkhtmltoimage');
        }

        // ── Strategy 2: Browsershot high quality ─────────────────────────────
        if ($this->tryBrowsershot($html, $path, $dynamicHeight, true)) {
            Log::info('Ticket generated via Browsershot HQ', ['pnr' => $customerData['pnr_no']]);
            return $this->result($path, $ticketUrl, $filename, 'browsershot-hq');
        }

        // ── Strategy 3: Browsershot regular quality ───────────────────────────
        if ($this->tryBrowsershot($html, $path, $dynamicHeight, false)) {
            Log::info('Ticket generated via Browsershot regular', ['pnr' => $customerData['pnr_no']]);
            return $this->result($path, $ticketUrl, $filename, 'browsershot-regular');
        }

        // ── Strategy 4: GD fallback — alert admin immediately ─────────────────
        Log::critical('ALL HTML renderers failed — using GD fallback!', [
            'pnr'         => $customerData['pnr_no'],
            'wkhtmltoimage' => $this->getWkhtmltoimagePath(),
            'node'        => shell_exec('node --version 2>&1'),
            'chromium'    => shell_exec('which chromium-browser 2>&1 || which chromium 2>&1 || which google-chrome 2>&1'),
        ]);

        // Send alert email so YOU know immediately (not after customer complains)
        $this->alertAdminAboutFallback($customerData['pnr_no']);

        $this->generateFallbackTicketImage($customerData, $path);

        return $this->result($path, $ticketUrl, $filename, 'fallback-gd');
    }

    // ─────────────────────────────────────────────────────────────────────────
    // STRATEGY 1 — wkhtmltoimage (MOST STABLE — recommended primary)
    // Install once:  sudo apt-get install wkhtmltopdf
    // ─────────────────────────────────────────────────────────────────────────

    private function tryWkhtmltoimage(string $html, string $outputPath, int $height): bool
    {
        try {
            $wkPath = $this->getWkhtmltoimagePath();
            if (!$wkPath) {
                return false; // binary not installed
            }

            // Write HTML to temp file (safer than stdin for large templates)
            $tmpHtml = sys_get_temp_dir() . '/ticket_' . uniqid() . '.html';
            file_put_contents($tmpHtml, $html);

            $cmd = escapeshellarg($wkPath)
                . ' --format jpg'
                . ' --quality 95'
                . ' --width 1200'
                . ' --disable-javascript'
                . ' --images'
                . ' --enable-local-file-access'
                . ' ' . escapeshellarg($tmpHtml)
                . ' ' . escapeshellarg($outputPath)
                . ' 2>&1';

            $output   = shell_exec($cmd);
            $success  = file_exists($outputPath) && filesize($outputPath) > 5000;

            @unlink($tmpHtml);

            if (!$success) {
                Log::warning('wkhtmltoimage produced no/empty output', ['cmd_output' => $output]);
            }

            return $success;

        } catch (\Exception $e) {
            Log::warning('wkhtmltoimage exception: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Locate wkhtmltoimage binary — checks multiple common install paths.
     * Returns null if not found anywhere.
     */
    private function getWkhtmltoimagePath(): ?string
    {
        $candidates = [
            '/usr/bin/wkhtmltoimage',
            '/usr/local/bin/wkhtmltoimage',
            '/opt/homebrew/bin/wkhtmltoimage',  // macOS Homebrew
            '/snap/bin/wkhtmltoimage',
        ];

        // Also try PATH lookup
        $which = trim(shell_exec('which wkhtmltoimage 2>/dev/null') ?? '');
        if ($which) {
            array_unshift($candidates, $which);
        }

        foreach ($candidates as $path) {
            if (!empty($path) && file_exists($path) && is_executable($path)) {
                return $path;
            }
        }

        return null;
    }

    // ─────────────────────────────────────────────────────────────────────────
    // STRATEGY 2 & 3 — Browsershot (Puppeteer)
    // ─────────────────────────────────────────────────────────────────────────

    private function tryBrowsershot(string $html, string $outputPath, int $height, bool $highQuality): bool
    {
        try {
            $width  = $highQuality ? 1200 : 800;
            $scale  = $highQuality ? 2 : 1;
            $quality = $highQuality ? 100 : 90;
            $timeout = $highQuality ? 15 : 8;

            Browsershot::html($html)
                ->windowSize($width, (int) ($height * ($highQuality ? 1.5 : 1)))
                ->deviceScaleFactor($scale)
                ->setScreenshotType('jpeg', $quality)
                ->dismissDialogs()
                ->timeout($timeout)
                ->setOption('args', [
                    '--no-sandbox',
                    '--disable-setuid-sandbox',
                    '--disable-dev-shm-usage',
                    '--disable-gpu',
                    '--headless',
                    '--disable-software-rasterizer',
                    '--hide-scrollbars',
                    '--disable-web-security',
                ])
                ->save($outputPath);

            $success = file_exists($outputPath) && filesize($outputPath) > 5000;

            if ($success && $highQuality && extension_loaded('imagick')) {
                try {
                    $imagick = new \Imagick($outputPath);
                    $imagick->setImageResolution(300, 300);
                    $imagick->setImageCompressionQuality(100);
                    $imagick->writeImage($outputPath);
                    $imagick->clear();
                    $imagick->destroy();
                } catch (\Exception $e) {
                    // Imagick enhancement failed — image still valid, continue
                    Log::warning('Imagick enhancement skipped: ' . $e->getMessage());
                }
            }

            return $success;

        } catch (\Exception $e) {
            Log::warning('Browsershot attempt failed (' . ($highQuality ? 'HQ' : 'regular') . '): ' . $e->getMessage());
            return false;
        }
    }

    // ─────────────────────────────────────────────────────────────────────────
    // ADMIN ALERT — fires when ALL renderers fail
    // ─────────────────────────────────────────────────────────────────────────

    private function alertAdminAboutFallback(string $pnrNo): void
    {
        try {
            // Only alert once per hour to avoid spam
            $cacheKey = 'ticket_fallback_alert_sent';
            if (Cache::has($cacheKey)) {
                return;
            }
            Cache::put($cacheKey, true, now()->addHour());

            $adminEmail = config('mail.ticket_admin_email', config('mail.from.address'));
            if (!$adminEmail) {
                return;
            }

            $diagnostics = $this->runDiagnostics();

            Mail::raw(
                "⚠️ TICKET GENERATOR FALLBACK ALERT\n\n"
                . "PNR: {$pnrNo}\n"
                . "Time: " . now()->format('Y-m-d H:i:s') . "\n\n"
                . "Customers are receiving plain-text tickets!\n\n"
                . "=== DIAGNOSTICS ===\n"
                . $diagnostics
                . "\n\n=== HOW TO FIX ===\n"
                . "Run on your server:\n"
                . "  php artisan ticket:diagnose\n\n"
                . "To install wkhtmltoimage (permanent fix):\n"
                . "  sudo apt-get install -y wkhtmltopdf\n\n"
                . "To fix Browsershot/Puppeteer:\n"
                . "  cd " . base_path() . "\n"
                . "  node_modules/.bin/puppeteer browsers install chrome\n"
                . "  # OR\n"
                . "  npm install puppeteer --prefix node_modules\n",
                function ($message) use ($adminEmail, $pnrNo) {
                    $message->to($adminEmail)
                        ->subject("🚨 Ticket Generator Broken — PNR {$pnrNo}");
                }
            );

        } catch (\Exception $e) {
            Log::error('Could not send admin alert email: ' . $e->getMessage());
        }
    }

    // ─────────────────────────────────────────────────────────────────────────
    // DIAGNOSTICS (used by artisan command + alert email)
    // ─────────────────────────────────────────────────────────────────────────

    public function runDiagnostics(): string
    {
        $lines = [];

        // wkhtmltoimage
        $wkPath = $this->getWkhtmltoimagePath();
        $lines[] = 'wkhtmltoimage binary : ' . ($wkPath ?? 'NOT FOUND — run: sudo apt-get install wkhtmltopdf');
        if ($wkPath) {
            $ver = trim(shell_exec($wkPath . ' --version 2>&1') ?? '');
            $lines[] = 'wkhtmltoimage version: ' . ($ver ?: 'unknown');
        }

        $lines[] = '';

        // Node / npm
        $node = trim(shell_exec('node --version 2>&1') ?? '');
        $npm  = trim(shell_exec('npm --version 2>&1') ?? '');
        $lines[] = 'Node.js : ' . ($node ?: 'NOT FOUND');
        $lines[] = 'npm     : ' . ($npm ?: 'NOT FOUND');

        // Chromium
        $chromium = trim(shell_exec('which chromium-browser 2>/dev/null || which chromium 2>/dev/null || which google-chrome 2>/dev/null') ?? '');
        $lines[] = 'Chromium: ' . ($chromium ?: 'NOT FOUND in PATH');

        // Puppeteer
        $puppeteerPath = base_path('node_modules/puppeteer');
        $lines[] = 'Puppeteer node_module: ' . (is_dir($puppeteerPath) ? 'EXISTS at ' . $puppeteerPath : 'MISSING — run: npm install puppeteer');

        // PHP extensions
        $lines[] = '';
        $lines[] = 'PHP imagick ext : ' . (extension_loaded('imagick') ? 'LOADED' : 'not loaded (optional)');
        $lines[] = 'PHP gd ext      : ' . (extension_loaded('gd') ? 'LOADED' : 'NOT LOADED');

        // Disk / permissions
        $ticketDir = storage_path('app/public/tickets');
        $lines[] = '';
        $lines[] = 'Ticket dir exists   : ' . (is_dir($ticketDir) ? 'YES' : 'NO');
        $lines[] = 'Ticket dir writable : ' . (is_writable($ticketDir) ? 'YES' : 'NO — fix: chmod 755 ' . $ticketDir);

        return implode("\n", $lines);
    }

    // ─────────────────────────────────────────────────────────────────────────
    // HELPERS
    // ─────────────────────────────────────────────────────────────────────────

    private function result(string $path, string $url, string $filename, string $method): array
    {
        return compact('path', 'url', 'filename', 'method');
    }

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

        $defaultLogo = public_path('images/logo.png');
        if (file_exists($defaultLogo)) {
            $imageData = base64_encode(file_get_contents($defaultLogo));
            return 'data:image/png;base64,' . $imageData;
        }

        return null;
    }

    private function generateQrCode($url)
    {
        try {
            $qrCodeApiUrl = 'https://api.qrserver.com/v1/create-qr-code/?size=100x100&data=' . urlencode($url);
            $ch = curl_init();
            curl_setopt_array($ch, [
                CURLOPT_URL            => $qrCodeApiUrl,
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_FOLLOWLOCATION => true,
                CURLOPT_TIMEOUT        => 2,
                CURLOPT_CONNECTTIMEOUT => 1,
                CURLOPT_SSL_VERIFYPEER => false,
            ]);
            $imageData = curl_exec($ch);
            $httpCode  = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            curl_close($ch);

            if ($httpCode === 200 && $imageData && strlen($imageData) > 100) {
                return 'data:image/png;base64,' . base64_encode($imageData);
            }
        } catch (\Exception $e) {
            // fall through
        }

        return $this->generatePlaceholderQrCode($url);
    }

    private function generatePlaceholderQrCode($url)
    {
        try {
            $image = imagecreatetruecolor(100, 100);
            $white = imagecolorallocate($image, 255, 255, 255);
            $black = imagecolorallocate($image, 0, 0, 0);
            $gray  = imagecolorallocate($image, 127, 17, 77);
            imagefill($image, 0, 0, $white);
            imagerectangle($image, 0, 0, 99, 99, $black);
            imagerectangle($image, 1, 1, 98, 98, $black);
            imagefilledrectangle($image, 5, 5, 25, 25, $black);
            imagefilledrectangle($image, 8, 8, 22, 22, $white);
            imagefilledrectangle($image, 11, 11, 19, 19, $black);
            imagefilledrectangle($image, 74, 5, 94, 25, $black);
            imagefilledrectangle($image, 77, 8, 91, 22, $white);
            imagefilledrectangle($image, 80, 11, 88, 19, $black);
            imagefilledrectangle($image, 5, 74, 25, 94, $black);
            imagefilledrectangle($image, 8, 77, 22, 91, $white);
            imagefilledrectangle($image, 11, 80, 19, 88, $black);
            for ($x = 30; $x < 70; $x += 3) {
                for ($y = 30; $y < 70; $y += 3) {
                    if (rand(0, 1)) {
                        imagefilledrectangle($image, $x, $y, $x + 2, $y + 2, $black);
                    }
                }
            }
            imagestring($image, 2, 30, 85, "SCAN ME", $gray);
            ob_start();
            imagepng($image);
            $data = ob_get_clean();
            imagedestroy($image);
            return 'data:image/png;base64,' . base64_encode($data);
        } catch (\Exception $e) {
            return null;
        }
    }

    private function removeOldTickets($pnrNo, $customerId)
    {
        try {
            $dir = storage_path('app/public/tickets/');
            if (!is_dir($dir)) return;

            $files = array_unique(array_merge(
                glob($dir . 'ticket_' . $pnrNo . '_' . $customerId . '_*.jpg') ?: [],
                glob($dir . 'ticket_' . $pnrNo . '_*.jpg') ?: [],
                glob($dir . $pnrNo . '.html') ?: [],
                glob($dir . 'temp_' . $pnrNo . '_*.html') ?: []
            ));

            foreach ($files as $file) {
                if (is_file($file)) {
                    unlink($file);
                }
            }
        } catch (\Exception $e) {
            Log::error('Error removing old tickets: ' . $e->getMessage());
        }
    }

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

    // ─────────────────────────────────────────────────────────────────────────
    // HTML GENERATION  (unchanged from your original)
    // ─────────────────────────────────────────────────────────────────────────

    private function generateTicketHtml($data, $ticketUrl)
    {
        $pnrNo          = htmlspecialchars($data['pnr_no']);
        $customerId     = htmlspecialchars($data['Customer_Id']);
        $customerName   = htmlspecialchars($data['primary_customer_name'] ?? $data['customer_name'] ?? 'N/A');
        $contactNo      = htmlspecialchars($data['primary_contact_no'] ?? $data['contact_no'] ?? 'N/A');
        $cnic           = htmlspecialchars($data['cnic'] ?? 'N/A');
        $gender         = htmlspecialchars($data['gender'] ?? 'N/A');
        $companyName    = htmlspecialchars($data['company_name']);
        $helpline       = htmlspecialchars($data['helpline'] ?? 'N/A');
        $sourceCity     = htmlspecialchars($data['source_city']);
        $destCity       = htmlspecialchars($data['dest_city']);
        $totalFare      = number_format($data['total_fare'] ?? 0, 2);
        $totalDiscount  = number_format($data['total_discount'] ?? 0, 2);
        $totalPaidFare  = number_format($data['total_paid_fare'] ?? 0, 2);
        $totalDiscountPercentage = $data['total_fare'] > 0
            ? number_format(($data['total_discount'] / $data['total_fare']) * 100, 1)
            : '0.0';

        $busType = isset($data['Bus_Service']) ? $this->getServiceTypeName($data['Bus_Service']) : 'Standard';

        $formattedDepartureTime = 'N/A';
        if (isset($data['travel_date'], $data['travel_time'])) {
            try {
                $dateTimeString     = $data['travel_date'] . ' ' . $data['travel_time'];
                $carbonDateTime     = $data['travel_date'] instanceof Carbon
                    ? $data['travel_date']->copy()->setTimeFromTimeString($data['travel_time'])
                    : Carbon::parse($dateTimeString);
                $formattedDepartureTime = $carbonDateTime->format('h:i A, l, d M Y');
            } catch (\Exception $e) {
                $formattedDepartureTime = now()->format('h:i A, l, d M Y');
            }
        } else {
            $formattedDepartureTime = now()->format('h:i A, l, d M Y');
        }

        $collection_point   = htmlspecialchars($data['collection_point']);
        $Boarding_Terminal  = htmlspecialchars($data['Boarding_Terminal'] ?? 'N/A');

        $bookingTime = 'N/A';
        try {
            $bookingTime = Carbon::parse($data['issue_date'])->format('h:i A, l, d M Y');
        } catch (\Exception $e) {
            $bookingTime = now()->format('h:i A, l, d M Y');
        }

        $companyLogoUrl = $this->getCompanyLogo($data['operator_id'] ?? $data['company_id'] ?? null);
        $qrCodeUrl      = $this->generateQrCode($ticketUrl);
        $seats          = $data['seats'] ?? [];

        $html = <<<HTML
<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Ticket - {$pnrNo}</title>
    <style>
    @page { margin: 0; padding: 0; }
    * { margin: 0; padding: 0; box-sizing: border-box; -webkit-font-smoothing: antialiased; text-rendering: optimizeLegibility; }
    body { font-family: 'Segoe UI', Arial, sans-serif; margin: 0; padding: 20px; font-size: 12px; line-height: 1.4; background: linear-gradient(to bottom, #ffffff, #f9f9f9); width: 1200px; color: #333; }
    .ticket-container { width: 1100px; margin: 0 auto; border-radius: 15px; padding: 20px; background: white; position: relative; overflow: hidden; }
    .customer-name { font-size: 28px; color: #5e0009; font-weight: 700; margin-bottom: 5px; text-shadow: 1px 1px 2px rgba(0,0,0,0.1); letter-spacing: 0.5px; }
    .customer-id { font-size: 12px; color: #555; margin-bottom: 3px; font-weight: 500; }
    .company-name { font-size: 22px; font-weight: 700; color: #5e0009; margin: 15px 0 10px 0; text-transform: uppercase; letter-spacing: 1px; }
    .route-section { margin: 15px 0; display: flex; gap: 40px; background: linear-gradient(to right, #f8f8f8, #ffffff); padding: 15px; border-radius: 8px; border: 1px solid #e0e0e0; }
    .route-label { font-weight: 700; font-size: 12px; margin-bottom: 5px; color: #5e0009; text-transform: uppercase; letter-spacing: 1px; }
    .city-name { font-size: 18px; font-weight: 700; color: #5e0009; margin-bottom: 0; text-transform: uppercase; letter-spacing: 1px; }
    .detail-row { margin-bottom: 8px; display: flex; align-items: center; }
    .detail-label { font-weight: 700; font-size: 12px; color: #5e0009; display: inline-block; width: 160px; min-width: 160px; }
    .detail-value { display: inline-block; font-size: 12px; font-weight: 600; color: #333; flex: 1; }
    table { width: 100%; border-collapse: separate; border-spacing: 0; border: 3px solid #5e0009; border-radius: 8px; overflow: hidden; margin: 0; box-shadow: 0 4px 6px rgba(0,0,0,0.05); }
    .passenger-table th, .payment-table th { background: linear-gradient(to bottom, #5e0009, #3d0006); color: white; padding: 12px 10px; text-align: left; font-weight: 700; font-size: 11px; text-transform: uppercase; letter-spacing: 0.5px; border-right: 1px solid rgba(255,255,255,0.1); }
    .passenger-table th:last-child, .payment-table th:last-child { border-right: none; }
    .passenger-table td, .payment-table td { padding: 10px; border-bottom: 1px solid #e8e8e8; font-size: 11px; font-weight: 500; }
    .main-note { font-weight: 600; margin: 15px 0; font-size: 11px; text-align: center; color: #5e0009; padding: 12px; background: linear-gradient(to right, #fff5f5, #fdecea); border-radius: 8px; border-left: 4px solid #5e0009 !important; border: 1px solid #5e0009; line-height: 1.5; box-shadow: 0 2px 4px rgba(94,0,9,0.1); }
    .Ticket-deail-box { border: 3px solid #5e0009; padding: 20px; border-radius: 10px; margin-bottom: 15px; background: linear-gradient(to bottom, #ffffff, #fafafa); position: relative; overflow: hidden; }
    .ticket-flex { display: flex; justify-content: space-between; gap: 20px; }
    .ticket-left { width: 70%; }
    .ticket-right { width: 30%; text-align: center; display: flex; flex-direction: column; align-items: center; justify-content: center; }
    .top-section { display: flex; justify-content: space-between; align-items: flex-start; margin-bottom: 20px; padding-bottom: 15px; border-bottom: 2px dashed #5e0009; }
    .top-left { width: 70%; }
    .top-right { width: 17%; text-align: center; display: flex; flex-direction: column; align-items: center; }
    .qr-box { border: 3px solid #5e0009; padding: 10px; width: 140px; height: 140px; display: flex; align-items: center; justify-content: center; background: white; border-radius: 8px; box-shadow: 0 4px 8px rgba(94,0,9,0.2); }
    .qr-code-img { width: 120px; height: 120px; object-fit: contain; }
    .qr-label { font-size: 10px; margin: 5px 0; color: #5e0009; font-weight: 700; text-transform: uppercase; letter-spacing: 0.5px; }
    .company-logo-img { max-width: 140px; max-height: 100px; object-fit: contain; margin-bottom: 10px; }
    .section-title { font-weight: 700; font-size: 18px; margin: 20px 0 10px 0; color: #5e0009; text-transform: uppercase; letter-spacing: 1px; }
    table tr td { font-size: 18px; }
    .total-row { background: linear-gradient(to right, #f5f5f5, #ffffff); font-weight: 700; }
    .total-row td { border-bottom: none; font-size: 18px; }
    .discount-highlight { color: #28a745; font-weight: 700; font-size: 11px; }
    .small-percentage { font-size: 9px; color: #6c757d; display: block; line-height: 1.3; margin-top: 2px; }
    .discount-cell { color: #28a745 !important; font-weight: 600; display: flex; align-items: center; gap: 3px; }
    .paid-cell { color: #5e0009; font-weight: 700; }
    .passenger-table th:nth-child(1), .passenger-table td:nth-child(1) { width: 5%; min-width: 40px; }
    .passenger-table th:nth-child(2), .passenger-table td:nth-child(2) { width: 18%; min-width: 150px; }
    .passenger-table th:nth-child(3), .passenger-table td:nth-child(3) { width: 15%; min-width: 130px; }
    .passenger-table th:nth-child(4), .passenger-table td:nth-child(4) { width: 7%; min-width: 60px; }
    .passenger-table th:nth-child(5), .passenger-table td:nth-child(5) { width: 12%; min-width: 100px; }
    .passenger-table th:nth-child(6), .passenger-table td:nth-child(6) { width: 7%; min-width: 60px; }
    .passenger-table th:nth-child(7), .passenger-table td:nth-child(7) { width: 12%; min-width: 100px; }
    .passenger-table th:nth-child(8), .passenger-table td:nth-child(8) { width: 14%; min-width: 120px; }
    .passenger-table th:nth-child(9), .passenger-table td:nth-child(9) { width: 10%; min-width: 90px; }
    </style>
</head>
<body>
    <div class="ticket-container">
        <div class="top-section">
            <div class="top-left">
                <div class="customer-name">{$customerName}</div>
                <div style="display:flex;gap:10px;">
                    <div class="customer-id"><span style="font-weight:800;">ID #</span> {$customerId}</div>
                    <div class="customer-id"><span style="font-weight:800;">Mobile #</span> {$contactNo}</div>
                </div>
                <div class="company-name">{$companyName}<br>
                    <div style="font-weight:bold;color:#7F114D;font-size:11px;">Help Line: {$helpline}</div>
                </div>
                <div class="route-section">
                    <div><div class="route-label">ORIGIN</div><div class="city-name">{$sourceCity}</div></div>
                    <div><div class="route-label">DESTINATION</div><div class="city-name">{$destCity}</div></div>
                </div>
            </div>
            <div class="top-right">
                <div class="qr-box">
HTML;

        if ($qrCodeUrl) {
            $html .= '<img src="' . $qrCodeUrl . '" alt="QR Code" class="qr-code-img">';
        } else {
            $html .= '<div style="text-align:center;"><div style="font-size:24px;margin-bottom:5px;">📋</div><div style="font-size:10px;font-weight:bold;color:#7F114D;">PNR: ' . $pnrNo . '</div></div>';
        }

        $html .= <<<HTML
                </div>
                <div class="qr-label">SCAN FOR TICKET</div>
                <div class="qr-label">PNR: {$pnrNo}</div>
            </div>
        </div>
        <div class="Ticket-deail-box">
            <div class="ticket-flex">
                <div class="ticket-left">
                    <div class="detail-row"><span class="detail-label">Bus Type:</span><span class="detail-value">{$busType}</span></div>
                    <div class="detail-row"><span class="detail-label">Departure Time:</span><span class="detail-value">{$formattedDepartureTime}</span></div>
                    <div class="detail-row"><span class="detail-label">Status:</span><span class="detail-value">{$collection_point}</span></div>
                    <div class="detail-row"><span class="detail-label">Boarding Terminal:</span><span class="detail-value">{$Boarding_Terminal}</span></div>
                    <div class="detail-row"><span class="detail-label">Booking Time:</span><span class="detail-value">{$bookingTime}</span></div>
                </div>
                <div class="ticket-right">
                    <div style="text-align:center;">
HTML;

        if ($companyLogoUrl) {
            $html .= '<img src="' . $companyLogoUrl . '" alt="' . $companyName . '" class="company-logo-img">';
        } else {
            $html .= '<div style="font-size:36px;color:#7F114D;margin-bottom:10px;">🚌</div>';
        }

        $html .= <<<HTML
                    </div>
                </div>
            </div>
        </div>
        <div class="section-title">PASSENGER DETAILS</div>
        <table class="passenger-table">
            <thead>
                <tr>
                    <th>SR#</th><th>PASSENGER NAME</th><th>CNIC/PASSPORT</th>
                    <th>GENDER</th><th>CONTACT</th><th>SEAT#</th>
                    <th>FARE</th><th>DISCOUNT</th><th>PAID</th>
                </tr>
            </thead>
            <tbody>
HTML;

        $counter         = 1;
        $totalFareSum    = 0;
        $totalDiscountSum = 0;

        if (!empty($seats)) {
            foreach ($seats as $seat) {
                $seatNo           = htmlspecialchars($seat['seat_no'] ?? 'N/A');
                $passengerName    = htmlspecialchars($seat['passenger_name'] ?? $customerName);
                $passengerCnic    = htmlspecialchars($seat['cnic'] ?? $cnic);
                $passengerGender  = htmlspecialchars($seat['gender'] ?? $gender);
                $passengerContact = htmlspecialchars($seat['contact_no'] ?? $contactNo);
                $fare             = number_format(($seat['fare'] ?? 0) + ($seat['discount'] ?? 0), 2);
                $discount         = number_format($seat['discount'] ?? 0, 2);
                $paidFare         = number_format(($seat['fare'] ?? 0), 2);
                $seatDiscountPct  = ($seat['fare'] ?? 0) > 0
                    ? number_format(($seat['discount'] / $seat['fare']) * 100, 1)
                    : '0.0';
                $totalFareSum    += ($seat['fare'] ?? 0) + ($seat['discount'] ?? 0);
                $totalDiscountSum += $seat['discount'] ?? 0;

                $discountDisplay = ($seat['discount'] ?? 0) > 0
                    ? "<span class='discount-highlight'>{$discount}</span><span class='small-percentage'>({$seatDiscountPct}%)</span>"
                    : "PKR 0.00<span class='small-percentage'>(0.0%)</span>";

                $html .= "<tr>
                    <td>{$counter}</td><td>{$passengerName}</td><td>{$passengerCnic}</td>
                    <td>{$passengerGender}</td><td>{$passengerContact}</td><td>{$seatNo}</td>
                    <td>PKR {$fare}</td>
                    <td class='discount-cell'>{$discountDisplay}</td>
                    <td class='paid-cell'>PKR {$paidFare}</td>
                </tr>";
                $counter++;
            }
        } else {
            $tfmt   = number_format($data['total_fare'] ?? 0, 2);
            $dfmt   = number_format($data['total_discount'] ?? 0, 2);
            $pfmt   = number_format(($data['total_fare'] ?? 0) - ($data['total_discount'] ?? 0), 2);
            $html .= "<tr>
                <td>1</td><td>{$customerName}</td><td>{$cnic}</td>
                <td>{$gender}</td><td>{$contactNo}</td><td>N/A</td>
                <td>PKR {$tfmt}</td>
                <td class='discount-cell'><span class='discount-highlight'>PKR {$dfmt}</span></td>
                <td class='paid-cell'>PKR {$pfmt}</td>
            </tr>";
        }

        $html .= <<<HTML
                <tr class="total-row">
                    <td colspan="6" style="font-weight:bold;"></td>
                    <td style="font-weight:bold;">TOTAL:</td>
                    <td style="font-weight:bold;">PKR {$totalFare}</td>
                </tr>
            </tbody>
        </table>
        <div class="section-title">PAYMENT DETAILS</div>
        <table class="payment-table">
            <thead>
                <tr><th>Payment Method</th><th>Refunded Amount</th><th>Service Charges</th><th>Paid Amount</th></tr>
            </thead>
            <tbody>
                <tr>
                    <td>Online Payment</td>
                    <td>PKR {$totalFare}</td>
                    <td>PKR 0</td>
                    <td style="font-weight:bold;">PKR {$totalFare}</td>
                </tr>
            </tbody>
        </table>
        <div class="main-note">
            📢 IMPORTANT: Please arrive 30 minutes before departure time.<br>
            📋 Bring your original CNIC/Passport and this ticket.<br>
            🔒 All discounted tickets are non-refundable and non-transferable.
        </div>
        <div style="text-align:center;margin-top:12px;font-size:9px;color:#666;line-height:1.3;">
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

    // ─────────────────────────────────────────────────────────────────────────
    // GD FALLBACK (last resort only)
    // ─────────────────────────────────────────────────────────────────────────

    private function generateFallbackTicketImage($data, $path)
    {
        try {
            $seatsCount   = !empty($data['seats']) ? count($data['seats']) : 1;
            $dynamicHeight = 800 + ($seatsCount * 30);
            $image = imagecreate(800, $dynamicHeight);
            $bgColor   = imagecolorallocate($image, 255, 255, 255);
            $textColor = imagecolorallocate($image, 127, 17, 77);
            $headerColor = imagecolorallocate($image, 192, 0, 0);
            imagefill($image, 0, 0, $bgColor);
            imagestring($image, 5, 20, 20, "TICKET - " . $data['pnr_no'], $headerColor);
            imagestring($image, 3, 20, 50, "Passenger: " . ($data['customer_name'] ?? 'N/A'), $textColor);
            imagestring($image, 3, 20, 80, "PNR: " . $data['pnr_no'], $textColor);
            imagestring($image, 3, 20, 110, "From: " . $data['source_city'], $textColor);
            imagestring($image, 3, 20, 140, "To: " . $data['dest_city'], $textColor);
            imagestring($image, 3, 20, 170, "Total Fare: PKR " . $data['total_fare'], $textColor);
            imagestring($image, 3, 20, 200, "Company: " . $data['company_name'], $textColor);
            $y = 240;
            if (!empty($data['seats'])) {
                imagestring($image, 3, 20, $y, "Seats:", $textColor);
                $y += 25;
                foreach ($data['seats'] as $i => $seat) {
                    imagestring($image, 2, 40, $y, "Seat " . ($i + 1) . ": " . ($seat['seat_no'] ?? 'N/A') . " - PKR " . number_format($seat['fare'] ?? 0, 2), $textColor);
                    $y += 20;
                }
            }
            imagestring($image, 2, 20, $y + 20, "⚠ Temporary ticket - please contact support for full ticket.", $headerColor);
            imagestring($image, 2, 20, $y + 40, "Generated: " . date('Y-m-d H:i:s'), $textColor);
            imagejpeg($image, $path, 90);
            imagedestroy($image);
        } catch (\Exception $e) {
            Log::critical('GD fallback also failed: ' . $e->getMessage());
            file_put_contents($path, "TICKET: " . $data['pnr_no']);
        }
    }
}
