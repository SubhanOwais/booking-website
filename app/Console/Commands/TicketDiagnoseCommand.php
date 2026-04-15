<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Services\TicketImageService;

class TicketDiagnoseCommand extends Command
{
    protected $signature   = 'ticket:diagnose';
    protected $description = 'Diagnose ticket image generation issues';

    public function handle(TicketImageService $service)
    {
        $isWin = (PHP_OS_FAMILY === 'Windows');

        $this->info('');
        $this->info('╔══════════════════════════════════════════════╗');
        $this->info('║     TICKET GENERATOR DIAGNOSTIC TOOL         ║');
        $this->info('╚══════════════════════════════════════════════╝');
        $this->info('  OS: ' . PHP_OS_FAMILY . ' (' . PHP_OS . ')');
        $this->info('');

        // ── 1. wkhtmltoimage ─────────────────────────────────────────────────
        $this->comment('► Checking wkhtmltoimage...');
        $wkFound = $this->findWkhtmltoimage($isWin);

        if ($wkFound) {
            $ver = trim(shell_exec(escapeshellarg($wkFound) . ($isWin ? ' --version 2>nul' : ' --version 2>&1')) ?? '');
            $this->info("  ✅ Found : {$wkFound}");
            $this->info("  ✅ Version: {$ver}");
        } else {
            $this->error('  ❌ NOT FOUND');
            if ($isWin) {
                $this->warn('  FIX: Download from https://wkhtmltopdf.org/downloads.html');
                $this->warn('       Choose: Windows 64-bit installer');
                $this->warn('       Install to default path (C:\\Program Files\\wkhtmltopdf)');
            } else {
                $this->warn('  FIX: sudo apt-get install -y wkhtmltopdf');
            }
        }

        $this->info('');

        // ── 2. Node.js ────────────────────────────────────────────────────────
        $this->comment('► Checking Node.js...');
        $node = trim(shell_exec('node --version' . ($isWin ? ' 2>nul' : ' 2>&1')) ?? '');
        $npm  = trim(shell_exec('npm --version'  . ($isWin ? ' 2>nul' : ' 2>&1')) ?? '');
        $this->line('  ' . (str_starts_with($node, 'v') ? '✅' : '❌') . ' Node.js : ' . ($node ?: 'NOT FOUND'));
        $this->line('  ' . ($npm ? '✅' : '❌') . ' npm     : ' . ($npm ?: 'NOT FOUND'));

        $this->info('');

        // ── 3. Puppeteer / Chrome ─────────────────────────────────────────────
        $this->comment('► Checking Puppeteer / Chrome...');
        $puppeteerPath = base_path('node_modules/puppeteer');

        if (is_dir($puppeteerPath)) {
            $this->info('  ✅ Puppeteer node_module: EXISTS');
        } else {
            $this->error('  ❌ Puppeteer NOT FOUND');
            $this->warn('  FIX: cd ' . base_path() . ' && npm install puppeteer');
        }

        // Find Chrome in Puppeteer cache
        $chromeExe = $this->findPuppeteerChrome($isWin);

        if ($chromeExe) {
            $this->info('  ✅ Chrome binary found:');
            $this->info('     ' . $chromeExe);
        } else {
            $this->error('  ❌ Chrome NOT found in Puppeteer cache');
            $this->warn('  ⚡ FIX (run this now):');
            $this->warn('     cd ' . base_path());
            $this->warn('     npx puppeteer browsers install chrome');
        }

        // List installed browsers via npx
        $listCmd  = 'cd "' . base_path() . '" && npx puppeteer browsers list' . ($isWin ? ' 2>nul' : ' 2>/dev/null');
        $browsers = trim(shell_exec($listCmd) ?? '');
        if ($browsers) {
            $this->line('  ℹ  Installed browsers: ' . $browsers);
        }

        $this->info('');

        // ── 4. PHP extensions ─────────────────────────────────────────────────
        $this->comment('► Checking PHP extensions...');
        $this->line('  ' . (extension_loaded('gd')      ? '✅' : '❌') . ' GD      : ' . (extension_loaded('gd') ? 'loaded' : 'MISSING'));
        $this->line('  ' . (extension_loaded('imagick') ? '✅' : '⚪') . ' Imagick : ' . (extension_loaded('imagick') ? 'loaded' : 'not loaded (optional)'));
        $this->line('  ' . (extension_loaded('curl')    ? '✅' : '❌') . ' cURL    : ' . (extension_loaded('curl') ? 'loaded' : 'MISSING'));

        $this->info('');

        // ── 5. Permissions ────────────────────────────────────────────────────
        $this->comment('► Checking directories...');
        $ticketDir = storage_path('app/public/tickets');
        if (!is_dir($ticketDir)) {
            $this->warn('  ⚠  Ticket directory does not exist — will be auto-created');
        } elseif (!is_writable($ticketDir)) {
            $this->error('  ❌ Ticket directory NOT writable: ' . $ticketDir);
        } else {
            $this->info('  ✅ Ticket directory writable: ' . $ticketDir);
        }

        $this->info('');

        // ── 6. Live Browsershot test ──────────────────────────────────────────
        $this->comment('► Running live Browsershot test...');
        $testPath = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'ticket_test_' . time() . '.jpg';
        $testHtml = '<html><body style="background:#fff;padding:20px;font-family:Arial"><h1 style="color:#5e0009">Ticket Test OK</h1></body></html>';

        $browsershotOk = false;
        try {
            \Spatie\Browsershot\Browsershot::html($testHtml)
                ->windowSize(400, 200)
                ->setScreenshotType('jpeg', 80)
                ->dismissDialogs()
                ->timeout(15)
                ->setOption('args', ['--no-sandbox', '--disable-setuid-sandbox', '--headless', '--disable-gpu'])
                ->save($testPath);

            if (file_exists($testPath) && filesize($testPath) > 500) {
                $this->info('  ✅ Browsershot live test: PASSED (' . filesize($testPath) . ' bytes)');
                $browsershotOk = true;
                @unlink($testPath);
            } else {
                $this->error('  ❌ Browsershot produced empty output');
            }
        } catch (\Exception $e) {
            $this->error('  ❌ Browsershot test FAILED: ' . $e->getMessage());
            if (str_contains($e->getMessage(), 'Could not find Chrome')) {
                $this->warn('  FIX: npx puppeteer browsers install chrome');
            }
        }

        $this->info('');

        // ── Summary ───────────────────────────────────────────────────────────
        $this->info('╔══════════════════════════════════════════════╗');
        $this->info('║                  SUMMARY                    ║');
        $this->info('╚══════════════════════════════════════════════╝');

        if ($wkFound) {
            $this->info('  🎉 wkhtmltoimage is installed — most stable renderer active.');
            $this->info('     Tickets will work even if Puppeteer/Chrome breaks.');
        } elseif ($browsershotOk) {
            $this->info('  ✅ Browsershot is working — tickets will generate correctly.');
            $this->warn('  ⚠  But consider installing wkhtmltoimage as a permanent backup.');
            if ($isWin) {
                $this->warn('     https://wkhtmltopdf.org/downloads.html (Windows 64-bit)');
            }
        } elseif ($chromeExe) {
            $this->warn('  ⚠  Chrome is installed but Browsershot test failed.');
            $this->warn('     Try running: php artisan ticket:test-render');
        } else {
            $this->error('  ❌ NO renderer is working right now!');
            $this->error('');
            if ($isWin) {
                $this->error('  Step 1 — Fix Puppeteer (quick):');
                $this->error('    cd ' . base_path());
                $this->error('    npx puppeteer browsers install chrome');
                $this->error('');
                $this->error('  Step 2 — Install wkhtmltoimage (permanent):');
                $this->error('    https://wkhtmltopdf.org/downloads.html');
            } else {
                $this->error('  sudo apt-get install -y wkhtmltopdf');
            }
        }

        $this->info('');
        $this->info('  Check logs: ' . storage_path('logs/laravel.log'));
        $this->info('');

        return 0;
    }

    private function findWkhtmltoimage(bool $isWin): ?string
    {
        if ($isWin) {
            $candidates = [
                env('WKHTMLTOIMAGE_PATH'),
                'C:\\Program Files\\wkhtmltopdf\\bin\\wkhtmltoimage.exe',
                'C:\\Program Files (x86)\\wkhtmltopdf\\bin\\wkhtmltoimage.exe',
                'C:\\wkhtmltopdf\\bin\\wkhtmltoimage.exe',
            ];
            $where = trim(shell_exec('where wkhtmltoimage 2>nul') ?? '');
            if ($where) array_unshift($candidates, strtok($where, "\n"));
        } else {
            $candidates = [
                env('WKHTMLTOIMAGE_PATH'),
                '/usr/bin/wkhtmltoimage',
                '/usr/local/bin/wkhtmltoimage',
                '/opt/homebrew/bin/wkhtmltoimage',
                '/snap/bin/wkhtmltoimage',
            ];
            $which = trim(shell_exec('which wkhtmltoimage 2>/dev/null') ?? '');
            if ($which) array_unshift($candidates, $which);
        }

        foreach ($candidates as $p) {
            if (!empty($p) && file_exists($p)) return $p;
        }
        return null;
    }

    private function findPuppeteerChrome(bool $isWin): ?string
    {
        // Check Puppeteer cache directory
        $cacheDir = $isWin
            ? (getenv('USERPROFILE') ?: 'C:\\Users\\Gaming PC') . '\\.cache\\puppeteer'
            : (getenv('HOME') ?: '/root') . '/.cache/puppeteer';

        if (!is_dir($cacheDir)) return null;

        // Search for chrome executable recursively
        if ($isWin) {
            $result = shell_exec('where /r "' . $cacheDir . '" chrome.exe 2>nul') ?? '';
        } else {
            $result = shell_exec('find "' . $cacheDir . '" -name "chrome" -type f 2>/dev/null') ?? '';
        }

        $first = trim(strtok($result, "\n") ?? '');
        return ($first && file_exists($first)) ? $first : null;
    }
}
