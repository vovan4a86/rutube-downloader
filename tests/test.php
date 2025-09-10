<?php

require_once __DIR__ . '/../vendor/autoload.php';

use Symfony\Component\Process\Process;

// Проверяем доступность yt-dlp
$process = new Process(['C:\\yt-dlp\\yt-dlp.exe', '--version']);
$process->run();
echo "yt-dlp version: " . ($process->isSuccessful() ? $process->getOutput() : "ERROR: " . $process->getErrorOutput()) . "\n";

// Проверяем доступность FFmpeg
$process = new Process(['C:\\ffmpeg\\bin\\ffmpeg.exe', '-version']);
$process->run();
echo "FFmpeg version: " . ($process->isSuccessful() ? substr($process->getOutput(), 0, 50) . "..." : "ERROR: " . $process->getErrorOutput()) . "\n";

// Проверяем права доступа к временной директории
$tempDir = sys_get_temp_dir() . '/test_ytdlp';
if (!file_exists($tempDir)) {
    mkdir($tempDir, 0777, true);
}

$testFile = $tempDir . '/test.txt';
if (file_put_contents($testFile, 'test')) {
    echo "Права на запись в $tempDir: OK\n";
    unlink($testFile);
} else {
    echo "Права на запись в $tempDir: ERROR\n";
}

// Проверяем переменные окружения
echo "TEMP: " . (getenv('TEMP') ?: 'не установлена') . "\n";
echo "TMP: " . (getenv('TMP') ?: 'не установлена') . "\n";

// Проверяем доступность Rutube
$process = new Process([
    'C:\\yt-dlp\\yt-dlp.exe',
    '--simulate',
    '--verbose',
    'https://rutube.ru/video/6a52ca5cfb345bdb00e38a751ce709fc/'
]);
$process->setTimeout(30);
$process->run();

echo "Доступность Rutube: " . ($process->isSuccessful() ? "OK" : "ERROR: " . $process->getErrorOutput()) . "\n";

// Очищаем
if (file_exists($tempDir)) {
    array_map('unlink', glob("$tempDir/*"));
    rmdir($tempDir);
}
