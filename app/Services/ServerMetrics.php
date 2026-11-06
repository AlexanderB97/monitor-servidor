<?php

namespace App\Services;

class ServerMetrics
{
    public function snapshot(): array
    {
        $diskTotal = disk_total_space(base_path());
        $diskFree = disk_free_space(base_path());
        $diskTotal = $diskTotal === false ? null : $diskTotal;
        $diskFree = $diskFree === false ? null : $diskFree;
        $windows = PHP_OS_FAMILY === 'Windows' ? $this->readWindows() : [];

        $memory = null;
        if (($windows['mem_total_kb'] ?? null) > 0 && is_numeric($windows['mem_free_kb'] ?? null)) {
            $memoryTotal = (float) $windows['mem_total_kb'];
            $memoryFree = (float) $windows['mem_free_kb'];

            $memory = [
                'total_mb' => round($memoryTotal / 1024, 2),
                'available_mb' => round($memoryFree / 1024, 2),
                'used_pct' => round((($memoryTotal - $memoryFree) / $memoryTotal) * 100, 1),
            ];
        }

        return [
            'hostname' => gethostname() ?: null,
            'os' => php_uname('s').' '.php_uname('r'),
            'php' => PHP_VERSION,
            'disk' => [
                'total_gb' => $diskTotal === null ? null : round($diskTotal / 1024 ** 3, 2),
                'free_gb' => $diskFree === null ? null : round($diskFree / 1024 ** 3, 2),
                'used_pct' => $diskTotal && $diskFree !== null
                    ? round((($diskTotal - $diskFree) / $diskTotal) * 100, 1)
                    : null,
            ],
            'memory' => $memory,
            'cpu_pct' => $windows['cpu_pct'] ?? null,
            'uptime_s' => $windows['uptime_s'] ?? null,
            'cpu_temp_c' => $windows['temp_c'] ?? null,
            'at' => now()->toIso8601String(),
        ];
    }

    private function readWindows(): array
    {
        $unavailable = [
            'mem_total_kb' => null,
            'mem_free_kb' => null,
            'cpu_pct' => null,
            'uptime_s' => null,
            'temp_c' => null,
        ];

        if (! function_exists('shell_exec') || ! function_exists('mb_convert_encoding')) {
            return $unavailable;
        }

        $script = <<<'PS'
$ErrorActionPreference = 'Stop'
$os = Get-CimInstance Win32_OperatingSystem
$processor = Get-CimInstance Win32_Processor | Measure-Object -Property LoadPercentage -Average
$uptime = [int64]((Get-Date) - $os.LastBootUpTime).TotalSeconds
$temp = $null
try {
    $thermal = Get-CimInstance -Namespace root/wmi -ClassName MSAcpi_ThermalZoneTemperature -ErrorAction Stop | Select-Object -First 1
    if ($null -ne $thermal.CurrentTemperature) {
        $temp = [math]::Round(($thermal.CurrentTemperature / 10) - 273.15, 1)
    }
} catch {}
[pscustomobject]@{
    mem_total_kb = $os.TotalVisibleMemorySize
    mem_free_kb = $os.FreePhysicalMemory
    cpu_pct = $processor.Average
    uptime_s = $uptime
    temp_c = $temp
} | ConvertTo-Json -Compress
PS;

        $encoded = base64_encode(mb_convert_encoding($script, 'UTF-16LE', 'UTF-8'));
        $output = shell_exec("powershell -NoProfile -NonInteractive -EncodedCommand $encoded");
        $metrics = json_decode($output ?? '', true);

        if (! is_array($metrics)) {
            return $unavailable;
        }

        $number = static fn (string $key): ?float => isset($metrics[$key]) && is_numeric($metrics[$key])
            ? (float) $metrics[$key]
            : null;

        return [
            'mem_total_kb' => $number('mem_total_kb'),
            'mem_free_kb' => $number('mem_free_kb'),
            'cpu_pct' => $number('cpu_pct'),
            'uptime_s' => $number('uptime_s') === null ? null : (int) $number('uptime_s'),
            'temp_c' => $number('temp_c'),
        ];
    }
}
