<?php

namespace App\Jobs;

use App\Models\Download;
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

            // Создаем уникальную временную директорию для каждого процесса
            $tempDir = storage_path('app/temp/yt-dlp_' . $this->download->id . '_' . time());
            if (!file_exists($tempDir)) {
                if (!mkdir($tempDir, 0777, true)) {
                    throw new \Exception("Не удалось создать временную директорию: $tempDir");
                }
            }

            // Генерируем безопасное имя файла
            $safeTitle = $this->cleanFileName($this->download->title ?? 'video');
            $outputTemplate = $downloadPath . '/' . $safeTitle . '.%(ext)s';

            // Устанавливаем переменные окружения для процесса
            $env = array_merge(getenv(), [
                'TEMP' => $tempDir,
                'TMP' => $tempDir,
                'TMPDIR' => $tempDir,
                'PYTHONHASHSEED' => '0'
            ]);

            $command = [
                'C:\\yt-dlp\\yt-dlp.exe',
                '--extract-audio',
                '--audio-format', 'mp3',
                '--audio-quality', '0',
                '--ffmpeg-location', 'C:\\ffmpeg\\bin',
                '--output', $outputTemplate,
                '--no-check-certificates',
                '--verbose', // Добавляем подробный вывод
                $this->download->url
            ];

            $process = new Process($command);
            $process->setTimeout(3600);
            $process->setEnv($env); // Устанавливаем переменные окружения
            $process->run();

            // Логируем вывод для отладки
            \Log::info('yt-dlp command: ' . implode(' ', $command));
            \Log::info('yt-dlp output: ' . $process->getOutput());
            \Log::info('yt-dlp error output: ' . $process->getErrorOutput());

            if (!$process->isSuccessful()) {
                throw new ProcessFailedException($process);
            }

            // Ищем созданный MP3 файл
            $files = glob($downloadPath . '/*.mp3');
            if (!empty($files)) {
                usort($files, function($a, $b) {
                    return filemtime($b) - filemtime($a);
                });

                $filePath = $files[0];
                $fileName = basename($filePath);

                // Обновляем информацию о загрузке
                $this->download->update([
                    'status' => 'completed',
                    'file_path' => $filePath,
                    'title' => $fileName
                ]);
            } else {
                throw new \Exception('Не удалось найти скачанный MP3 файл');
            }

            // Очищаем временную директорию после успешного выполнения
            $this->deleteDirectory($tempDir);

        } catch (\Exception $e) {
            \Log::error('Download error: ' . $e->getMessage());
            $this->download->update([
                'status' => 'failed',
                'error_message' => substr($e->getMessage(), 0, 255)
            ]);

            // Пытаемся очистить временную директорию даже в случае ошибки
            if (isset($tempDir) && file_exists($tempDir)) {
                $this->deleteDirectory($tempDir);
            }
        }
    }

// Добавляем метод для рекурсивного удаления директории
    private function deleteDirectory($dir)
    {
        if (!file_exists($dir)) {
            return true;
        }

        if (!is_dir($dir)) {
            return unlink($dir);
        }

        foreach (scandir($dir) as $item) {
            if ($item == '.' || $item == '..') {
                continue;
            }

            if (!$this->deleteDirectory($dir . DIRECTORY_SEPARATOR . $item)) {
                return false;
            }
        }

        return rmdir($dir);
    }

// Метод для очистки имени файла
    private function cleanFileName($filename)
    {
        // Удаляем эмодзи и другие специальные символы
        $cleaned = preg_replace('/[^\x{0000}-\x{007F}]/u', '', $filename);

        // Заменяем пробелы и другие проблемные символы
        $cleaned = preg_replace('/[\s\/\\\\:\*\?"<>\|]/', '_', $cleaned);

        // Удаляем множественные подчеркивания
        $cleaned = preg_replace('/_+/', '_', $cleaned);

        // Обрезаем длину имени файла
        if (strlen($cleaned) > 200) {
            $cleaned = substr($cleaned, 0, 200);
        }

        return $cleaned ?: 'video';
    }
}
