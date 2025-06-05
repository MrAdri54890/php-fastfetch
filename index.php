<?php 
/*     _                  __           _    __      _       _
 _ __ | |__  _ __        / _| __ _ ___| |_ / _| ___| |_ ___| |__
| '_ \| '_ \| '_ \ _____| |_ / _` / __| __| |_ / _ \ __/ __| '_ \
| |_) | | | | |_) |_____|  _| (_| \__ \ |_|  _|  __/ || (__| | | |
| .__/|_| |_| .__/      |_|  \__,_|___/\__|_|  \___|\__\___|_| |_|
|_|         |_|
*/
function getOS() {
    if (is_readable('/etc/os-release')) {
        $lines = file('/etc/os-release', FILE_IGNORE_NEW_LINES);
        foreach ($lines as $line) {
            if (strpos($line, 'PRETTY_NAME=') === 0) {
                return trim(explode('=', $line, 2)[1], '"');
            }
        }
    }
    return php_uname('s') . ' ' . php_uname('r');
}

function getUptime() {
    if (!file_exists('/proc/uptime')) return 'N/A';
    $uptime = (int)floatval(file_get_contents('/proc/uptime'));
    $hours = floor($uptime / 3600);
    $mins = floor(($uptime % 3600) / 60);
    return "{$hours} hours, {$mins} mins";
}

function getCPU() {
    $info = file_get_contents('/proc/cpuinfo');
    preg_match('/model name\s+:\s+(.+)/', $info, $model);
    preg_match_all('/^processor\s+:/m', $info, $cores);
    $count = count($cores[0]);
    return "$count x " . trim($model[1] ?? 'Unknown');
}

function getMemory() {
    $meminfo = file_get_contents('/proc/meminfo');
    preg_match('/MemTotal:\s+(\d+)/', $meminfo, $total);
    preg_match('/MemAvailable:\s+(\d+)/', $meminfo, $avail);
    $totalGiB = $total[1] / 1024 / 1024;
    $usedGiB = ($total[1] - $avail[1]) / 1024 / 1024;
    $percent = ($usedGiB / $totalGiB) * 100;
    return sprintf("%.2f GiB / %.2f GiB (%.0f%%)", $usedGiB, $totalGiB, $percent);
}

function getSwap() {
    $meminfo = file_get_contents('/proc/meminfo');
    preg_match('/SwapTotal:\s+(\d+)/', $meminfo, $total);
    preg_match('/SwapFree:\s+(\d+)/', $meminfo, $free);
    $totalMiB = $total[1] / 1024;
    $usedMiB = ($total[1] - $free[1]) / 1024;
    $percent = $totalMiB > 0 ? ($usedMiB / $totalMiB) * 100 : 0;
    return sprintf("%.2f MiB / %.2f GiB (%.0f%%)", $usedMiB, $totalMiB / 1024, $percent);
}

function getDisk() {
    $free = disk_free_space('/');
    $total = disk_total_space('/');
    $used = $total - $free;
    $usedGiB = $used / 1024 / 1024 / 1024;
    $totalGiB = $total / 1024 / 1024 / 1024;
    $percent = ($usedGiB / $totalGiB) * 100;
    return sprintf("%.2f GiB / %.2f GiB (%.0f%%) - ext4", $usedGiB, $totalGiB, $percent);
}

$logo = <<<LOGO
       _                  __           _    __      _       _
 _ __ | |__  _ __        / _| __ _ ___| |_ / _| ___| |_ ___| |__
| '_ \| '_ \| '_ \ _____| |_ / _` / __| __| |_ / _ \ __/ __| '_ \
| |_) | | | | |_) |_____|  _| (_| \__ \ |_|  _|  __/ || (__| | | |
| .__/|_| |_| .__/      |_|  \__,_|___/\__|_|  \___|\__\___|_| |_|
|_|         |_|
 
LOGO;

$os = getOS();
$host = gethostname();
$kernel = php_uname('r');
$uptime = getUptime();
$cpu = getCPU();
$memory = getMemory();
$swap = getSwap();
$disk = getDisk();

echo <<<HTML
<!DOCTYPE html>
<html lang="fr">
<head>
<meta charset="UTF-8" />
<title>php-fastfetch</title>
<style>
  body {
    background-color: #000;
    color: #FFFFFF;
    font-family: "Courier New", Courier, monospace;
    padding: 20px;
  }
  pre {
    font-size: 16px;
    line-height: 1.2;
  }
</style>
</head>
<body>
<pre>$logo

OS:          $os x86_64
Host:        $host
Kernel:      $kernel
Uptime:      $uptime
CPU:         $cpu
Memory:      $memory
Swap:        $swap
Disk (/):    $disk
</pre>
</body>
</html>
HTML;
?>
