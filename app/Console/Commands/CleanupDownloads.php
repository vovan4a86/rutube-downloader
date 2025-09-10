<?php

namespace App\Console\Commands;

use App\Models\Download;
use Illuminate\Console\Command;

class CleanupDownloads extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'downloads:cleanup {--days=7 : Удалять файлы старше указанного количества дней}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Очистка старых загрузок и файлов';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $days = $this->option('days');
        $threshold = now()->subDays($days);

        // Находим записи для удаления
        $downloads = Download::where('created_at', '<', $threshold)->get();

        foreach ($downloads as $download) {
            // Удаляем файл, если он существует
            if ($download->file_path && file_exists($download->file_path)) {
                unlink($download->file_path);
            }

            // Удаляем запись из базы данных
            $download->delete();

            $this->info("Удален файл: {$download->title}");
        }

        $this->info("Очистка завершена. Удалено записей: " . $downloads->count());
    }
}
