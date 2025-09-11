<?php

namespace App\Jobs;

use App\Models\Download;
use Barryvdh\Debugbar\Facades\Debugbar;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Symfony\Component\Process\Exception\ProcessFailedException;
use Symfony\Component\Process\Process;

class ProcessRutubeDownload implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $download;

    /**
     * Create a new job instance.
     */
    public function __construct(Download $download)
    {
        $this->download = $download;
    }

    public function handle(): void
    {
        $this->download->update(['status' => 'processing']);

        try {
            $downloadPath = storage_path('app/downloads');
            if (!file_exists($downloadPath)) {
                mkdir($downloadPath, 0777, true);
            }

            // Создаем временную директорию для yt-dlp
            $tempDir = storage_path('app/temp');
            if (!file_exists($tempDir)) {
                mkdir($tempDir, 0777, true);
            }

            // Определяем ОС
            $isWindows = strtoupper(substr(PHP_OS, 0, 3)) === 'WIN';

            // Получаем информацию о видео для извлечения оригинального названия
            $videoInfo = $this->getVideoInfo($this->download->url, $isWindows);
            $originalTitle = $videoInfo['title'] ?? $this->extractVideoId($this->download->url);

            // Генерируем безопасное имя файла на основе оригинального названия
            $safeTitle = $this->cleanFileName($originalTitle);

            // Формируем путь для выходного файла
            $outputFile = $downloadPath . '/' . $safeTitle . '.mp3';

            // Формируем шаблон для yt-dlp (без расширения, так как мы явно указываем mp3)
            $outputTemplate = $downloadPath . '/' . $safeTitle;

            // Устанавливаем переменные окружения для процесса
            $env = array_merge(getenv(), [
                'TEMP' => $tempDir,
                'TMP' => $tempDir,
                'TMPDIR' => $tempDir,
                'PYTHONHASHSEED' => '0'
            ]);

            // Формируем команду в зависимости от ОС
            $ytdlpPath = $isWindows ? 'C:\\yt-dlp\\yt-dlp.exe' : 'yt-dlp';
            $ffmpegPath = $isWindows ? 'C:\\ffmpeg\\bin' : '/usr/bin';

            $command = [
                $ytdlpPath,
                '--extract-audio',
                '--audio-format', 'mp3',
                '--audio-quality', '0',
                '--ffmpeg-location', $ffmpegPath,
                '--output', $outputTemplate, // Без расширения, так как yt-dlp добавит .mp3
                '--no-check-certificates',
                '--no-overwrites',
                '-f', 'worst',
                '--print', 'after_move:filepath', // Получаем путь к конечному файлу
                $this->download->url
            ];

            $process = new Process($command);
            $process->setTimeout(3600);
            $process->setEnv($env);
            $process->run();

            // Логируем вывод для отладки
            \Log::info('yt-dlp command: ' . implode(' ', $command));
            \Log::info('yt-dlp output: ' . $process->getOutput());
            \Log::info('yt-dlp error output: ' . $process->getErrorOutput());

            if (!$process->isSuccessful()) {
                throw new ProcessFailedException($process);
            }

            // Получаем путь к созданному файлу из вывода yt-dlp
            $output = trim($process->getOutput());
            $filePath = $output ?: $outputFile;

            // Проверяем, что файл действительно существует
            if (!file_exists($filePath)) {
                // Если файл не найден по пути из вывода, ищем по ожидаемому пути
                if (file_exists($outputFile)) {
                    $filePath = $outputFile;
                } else {
                    // Ищем любой MP3 файл в директории
                    $files = glob($downloadPath . '/*.mp3');
                    if (!empty($files)) {
                        usort($files, function($a, $b) {
                            return filemtime($b) - filemtime($a);
                        });
                        $filePath = $files[0];
                    } else {
                        throw new \Exception('Не удалось найти скачанный MP3 файл');
                    }
                }
            }

            // Обновляем информацию о загрузке
            $this->download->update([
                'status' => 'completed',
                'file_path' => $filePath,
                'title' => $originalTitle // Сохраняем оригинальное название
            ]);

        } catch (\Exception $e) {
            \Log::error('Download error: ' . $e->getMessage());
            $this->download->update([
                'status' => 'failed',
                'error_message' => substr($e->getMessage(), 0, 255)
            ]);
        }
    }

// Метод для получения информации о видео
    private function getVideoInfo($url, $isWindows)
    {
        $ytdlpPath = $isWindows ? 'C:\\yt-dlp\\yt-dlp.exe' : 'yt-dlp';

        $command = [
            $ytdlpPath,
            '--dump-json',
            '--no-check-certificates',
            '--no-playlist',
            $url
        ];

        $process = new Process($command);
        $process->setTimeout(30);
        $process->run();

        if ($process->isSuccessful()) {
            $output = $process->getOutput();
            $info = json_decode($output, true);

            if (json_last_error() === JSON_ERROR_NONE && isset($info['title'])) {
                return $info;
            } else {
                \Log::warning('Не удалось распарсить информацию о видео: ' . $output);
            }
        } else {
            \Log::warning('Не удалось получить информацию о видео: ' . $process->getErrorOutput());
        }

        // Если не удалось получить информацию, возвращаем заглушку
        return ['title' => $this->extractVideoId($url)];
    }

// Метод для извлечения ID видео из URL
    private function extractVideoId($url)
    {
        $parsedUrl = parse_url($url);
        if (!isset($parsedUrl['path'])) {
            return 'video';
        }

        $pathParts = explode('/', trim($parsedUrl['path'], '/'));
        $videoId = end($pathParts);

        // Удаляем возможные параметры из ID
        if (strpos($videoId, '?') !== false) {
            $videoId = substr($videoId, 0, strpos($videoId, '?'));
        }

        return $videoId ?: 'video';
    }

// Метод для очистки имени файла
    private function cleanFileName($filename)
    {
        // Удаляем эмодзи и другие специальные символы
//        $cleaned = preg_replace('/[^\x{0000}-\x{007F}]/u', '', $filename);

        // Заменяем пробелы и другие проблемные символы
        $cleaned = preg_replace('/[\s\/\\\\:\*\?"<>\|]/', '_', $filename);

        // Удаляем множественные подчеркивания
        $cleaned = preg_replace('/_+/', '_', $cleaned);
        $cleaned = \Str::slug($cleaned);

        // Обрезаем длину имени файла
        if (strlen($cleaned) > 200) {
            $cleaned = substr($cleaned, 0, 200);
        }

        return $cleaned ?: 'video';
    }
}
