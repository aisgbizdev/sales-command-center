<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\File;

class WhatsAppWebJsController extends Controller
{
    public function index()
    {
        $this->ensureWebJsServerRunning();

        return view('whatsapp-webjs.index', [
            'baseUrl' => '/wa-webjs-api',
        ]);
    }

    public function proxy(Request $request, string $path = '')
    {
        $this->ensureWebJsServerRunning();

        $baseUrl = rtrim((string) env('WA_WEBJS_BASE_URL', 'http://127.0.0.1:3001'), '/');
        $target = $baseUrl.'/'.ltrim($path, '/');

        $method = strtoupper($request->method());

        try {
            $response = $this->sendToWebJs($method, $target, $request);
        } catch (\Throwable $e) {
            Log::warning('WA_WEBJS_PROXY_UNAVAILABLE', [
                'target' => $target,
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'message' => 'WA WebJS service is starting, retry in a few seconds.',
            ], 503);
        }

        return response($response->body(), $response->status())
            ->header('Content-Type', $response->header('Content-Type', 'application/json'));
    }

    private function sendToWebJs(string $method, string $target, Request $request)
    {
        $attempts = 0;
        $maxAttempts = 4; // up to ~4 seconds warm-up for autostart
        $lastError = null;

        while ($attempts < $maxAttempts) {
            try {
                return Http::withOptions([
                    'http_errors' => false,
                    'timeout' => 30,
                    'connect_timeout' => 3,
                ])->withHeaders([
                    'X-SGB-User-Id' => (string) (auth()->id() ?? 0),
                ])->send($method, $target, [
                    'query' => $request->query(),
                    'json' => $request->all(),
                ]);
            } catch (\Throwable $e) {
                $lastError = $e;
                $attempts++;
                usleep(1_000_000);
            }
        }

        throw $lastError ?? new \RuntimeException('WA WebJS service unavailable.');
    }

    private function ensureWebJsServerRunning(): void
    {
        if (!filter_var(env('WA_WEBJS_AUTOSTART', true), FILTER_VALIDATE_BOOL)) {
            return;
        }

        $baseUrl = rtrim((string) env('WA_WEBJS_BASE_URL', 'http://127.0.0.1:3001'), '/');
        $healthUrl = $baseUrl.'/health';
        $lockFile = storage_path('app/wa-webjs-autostart.lock');
        $cooldownSeconds = (int) env('WA_WEBJS_AUTOSTART_COOLDOWN', 20);

        try {
            $health = Http::timeout(2)->get($healthUrl);
            if ($health->ok()) {
                if (File::exists($lockFile)) {
                    File::delete($lockFile);
                }
                return;
            }
        } catch (\Throwable $e) {
            // Continue to spawn process when health check is unreachable.
        }

        if (File::exists($lockFile)) {
            $lastRunAt = (int) File::get($lockFile);
            if ((time() - $lastRunAt) < $cooldownSeconds) {
                return;
            }
        }

        File::ensureDirectoryExists(dirname($lockFile));
        File::put($lockFile, (string) time());

        $projectRoot = base_path();
        $scriptPath = base_path('scripts/wa-webjs-server.cjs');
        $nodeBinary = (string) env('WA_WEBJS_NODE_BINARY', 'node');

        // Windows + Unix detached background start.
        if (PHP_OS_FAMILY === 'Windows') {
            $escapedProjectRoot = str_replace("'", "''", $projectRoot);
            $escapedNodeBinary = str_replace("'", "''", $nodeBinary);
            $command = 'powershell -NoProfile -ExecutionPolicy Bypass -Command "'
                .'$existing = Get-CimInstance Win32_Process | Where-Object { $_.Name -eq \'node.exe\' -and $_.CommandLine -like \'*wa-webjs-server.cjs*\' }; '
                .'if (-not $existing) { '
                .'Start-Process -FilePath \''.$escapedNodeBinary.'\' -ArgumentList \'scripts/wa-webjs-server.cjs\' -WorkingDirectory \''.$escapedProjectRoot.'\' -WindowStyle Hidden '
                .'}"';
            @pclose(@popen($command, 'r'));
        } else {
            $command = 'cd '.escapeshellarg($projectRoot).' && nohup '.escapeshellarg($nodeBinary).' '.escapeshellarg($scriptPath).' > /dev/null 2>&1 &';
            @exec($command);
        }

        Log::info('WA_WEBJS_AUTOSTART_TRIGGERED', [
            'health_url' => $healthUrl,
            'script' => $scriptPath,
            'command_family' => PHP_OS_FAMILY,
        ]);
    }
}
