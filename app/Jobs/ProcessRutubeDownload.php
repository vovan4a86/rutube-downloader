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
            // Создаем директорию для загрузок, если её нет
            $downloadPath = storage_path('app/downloads');
            if (!file_exists($downloadPath)) {
                mkdir($downloadPath, 0777, true);
            }

            // Определяем команду в зависимости от ОС
            $isWindows = strtoupper(substr(PHP_OS, 0, 3)) === 'WIN';

            if ($isWindows) {
                // Команда для Windows
                $process = new Process([
                    'yt-dlp.exe',
                    '-x',
                    '--audio-format', 'mp3',
                    '--audio-quality', '0',
                    '-o', $downloadPath . '/%(title)s.%(ext)s',
                    $this->download->url
                ]);
            } else {
                // Команда для Linux
                $process = new Process([
                    'yt-dlp',
                    '-x',
                    '--audio-format', 'mp3',
                    '--audio-quality', '0',
                    '-o', $downloadPath . '/%(title)s.%(ext)s',
                    $this->download->url
                ]);
            }

            $process->setTimeout(3600);
            $process->run();

            if (!$process->isSuccessful()) {
                throw new ProcessFailedException($process);
            }

            // Получаем вывод команды для поиска пути к файлу
            $output = $process->getOutput();
            preg_match('/\[ExtractAudio\] Destination: (.*\.mp3)/', $output, $matches);

            if (isset($matches[1])) {
                $filePath = $matches[1];

                // Обновляем информацию о загрузке
                $this->download->update([
                    'status' => 'completed',
                    'file_path' => $filePath,
                    'title' => basename($filePath)
                ]);
            } else {
                // Альтернативный способ определения пути к файлу
                $files = glob($downloadPath . '/*.mp3');
                if (!empty($files)) {
                    $filePath = $files[0];
                    $this->download->update([
                        'status' => 'completed',
                        'file_path' => $filePath,
                        'title' => basename($filePath)
                    ]);
                } else {
                    throw new \Exception('Не удалось определить путь к скачанному файлу');
                }
            }

        } catch (\Exception $e) {
            $this->download->update([
                'status' => 'failed',
                'error_message' => $e->getMessage()
            ]);
        }
    }
}
