<?php
function getOS(): string {
    if (PHP_OS_FAMILY === 'Linux' && is_readable('/etc/os-release')) {
        foreach (file('/etc/os-release') as $line) {
            if (str_starts_with($line, 'PRETTY_NAME=')) {
                return trim(explode('=', $line, 2)[1], "\"\n\r");
            }
        }
    }
    return php_uname('s') . ' ' . php_uname('r');
}

function getUptime(): string {
    if (PHP_OS_FAMILY === 'Linux' && is_readable('/proc/uptime')) {
        $seconds = (int)floatval(file_get_contents('/proc/uptime'));
        $days    = intdiv($seconds, 86400);
        $hours   = intdiv($seconds % 86400, 3600);
        $minutes = intdiv($seconds % 3600, 60);
        return "{$days}d {$hours}h {$minutes}m";
    }
    return 'N/A';
}

function getCPU(): string {
    if (PHP_OS_FAMILY === 'Linux' && is_readable('/proc/cpuinfo')) {
        $info = file_get_contents('/proc/cpuinfo');
        preg_match('/model name\s+:\s+(.+)/', $info, $model);
        preg_match_all('/^processor\s+:/m', $info, $cores);
        $count = count($cores[0]);
        return "$count x " . trim($model[1] ?? 'Unknown');
    }
    return php_uname('m');
}

function getMemory(): string {
    if (PHP_OS_FAMILY === 'Linux' && is_readable('/proc/meminfo')) {
        $meminfo = file_get_contents('/proc/meminfo');
        preg_match('/MemTotal:\s+(\d+)/', $meminfo, $total);
        preg_match('/MemAvailable:\s+(\d+)/', $meminfo, $avail);
        $totalGiB = $total[1] / 1024 / 1024;
        $usedGiB  = ($total[1] - $avail[1]) / 1024 / 1024;
        $percent  = ($usedGiB / $totalGiB) * 100;
        return sprintf("%.2f GiB / %.2f GiB (%.0f%%)", $usedGiB, $totalGiB, $percent);
    }
    return 'N/A';
}

function getSwap(): string {
    if (PHP_OS_FAMILY === 'Linux' && is_readable('/proc/meminfo')) {
        $meminfo = file_get_contents('/proc/meminfo');
        preg_match('/SwapTotal:\s+(\d+)/', $meminfo, $total);
        preg_match('/SwapFree:\s+(\d+)/',  $meminfo, $free);
        $totalMiB = ($total[1] ?? 0) / 1024;
        $usedMiB  = $totalMiB - (($free[1] ?? 0) / 1024);
        $percent  = $totalMiB > 0 ? ($usedMiB / $totalMiB) * 100 : 0;
        return sprintf("%.2f MiB / %.2f GiB (%.0f%%)", $usedMiB, $totalMiB / 1024, $percent);
    }
    return 'N/A';
}

function getDisk(): string {
    $mount = PHP_OS_FAMILY === 'Windows' ? getenv('SystemDrive') . '\\' : '/';
    $total = @disk_total_space($mount);
    $free  = @disk_free_space($mount);

    if ($total === false || $free === false) return 'N/A';

    $used     = $total - $free;
    $usedGiB  = $used / (1024 ** 3);
    $totalGiB = $total / (1024 ** 3);
    $percent  = ($usedGiB / $totalGiB) * 100;
    $fs       = detectFilesystem($mount);

    return sprintf("%.2f GiB / %.2f GiB (%.0f%%) - %s", $usedGiB, $totalGiB, $percent, $fs);
}

function detectFilesystem(string $mount): string {
    if (PHP_OS_FAMILY === 'Linux' && is_readable('/proc/mounts')) {
        foreach (file('/proc/mounts') as $line) {
            [$device, $point, $type] = explode(' ', $line);
            if ($point === $mount) return $type;
        }
    }

    if (PHP_OS_FAMILY === 'Windows') {
        return 'NTFS/FAT';
    }

    if (PHP_OS_FAMILY === 'Darwin') {
        return 'APFS';
    }

    return 'unknown';
}

$logo = <<<LOGO
       _                  __           _    __      _       _
 _ __ | |__  _ __        / _| __ _ ___| |_ / _| ___| |_ ___| |__
| '_ \| '_ \| '_ \ _____| |_ / _` / __| __| |_ / _ \ __/ __| '_ \\
| |_) | | | | |_) |_____|  _| (_| \__ \ |_|  _|  __/ || (__| | | |
| .__/|_| |_| .__/      |_|  \__,_|___/\__|_|  \___|\__\___|_| |_|
|_|         |_|
LOGO;

$os     = getOS();
$host   = gethostname();
$kernel = php_uname('r');
$uptime = getUptime();
$cpu    = getCPU();
$memory = getMemory();
$swap   = getSwap();
$disk   = getDisk();

if (PHP_SAPI === 'cli') {
    echo $logo . "\n\n";
    echo "OS:          $os\n";
    echo "Host:        $host\n";
    echo "Kernel:      $kernel\n";
    echo "Uptime:      $uptime\n";
    echo "CPU:         $cpu\n";
    echo "Memory:      $memory\n";
    echo "Swap:        $swap\n";
    echo "Disk (/):    $disk\n";
    exit;
}

?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>php-fastfetch</title>
  <style>
    body {
      background-color: #000;
      color: #0f0;
      font-family: "Courier New", monospace;
      padding: 20px;
    }
    pre {
      font-size: 16px;
      line-height: 1.3;
    }
  </style>
</head>
<body>
<pre><?= htmlspecialchars($logo) ?>


OS:          <?= htmlspecialchars($os) ?>

Host:        <?= htmlspecialchars($host) ?>

Kernel:      <?= htmlspecialchars($kernel) ?>

Uptime:      <?= htmlspecialchars($uptime) ?>

CPU:         <?= htmlspecialchars($cpu) ?>

Memory:      <?= htmlspecialchars($memory) ?>

Swap:        <?= htmlspecialchars($swap) ?>

Disk (/):    <?= htmlspecialchars($disk) ?>

</pre>
</body>
</html>
