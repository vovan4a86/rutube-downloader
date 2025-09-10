<?php

namespace App\Http\Controllers;

use App\Jobs\ProcessRutubeDownload;
use App\Models\Download;
use Illuminate\Http\Request;

class DownloadController extends Controller
{
    public function index()
    {
        $downloads = Download::orderBy('created_at', 'desc')->get();
        return view('dashboard', compact('downloads'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'url' => 'required|url|starts_with:https://rutube.ru'
        ]);

        // Извлекаем ID видео для идентификации
        $url = $request->input('url');
        $videoId = $this->extractVideoId($url);

        // Проверяем, не обрабатывается ли уже это видео
        $existingDownload = Download::where('video_id', $videoId)
            ->whereIn('status', ['pending', 'processing'])
            ->first();

        if ($existingDownload) {
            return redirect()->back()->with('error', 'Это видео уже в процессе загрузки');
        }

        // Создаем запись о загрузке
        $download = Download::create([
            'url' => $url,
            'video_id' => $videoId,
            'status' => 'pending'
        ]);

        // Запускаем job для обработки
        ProcessRutubeDownload::dispatch($download);

        return redirect()->back()->with('success', 'Видео добавлено в очередь на обработку');
    }

    public function download(Download $download)
    {
        if ($download->status !== 'completed' || !file_exists($download->file_path)) {
            return redirect()->back()->with('error', 'Файл не готов или отсутствует');
        }

        return response()->download($download->file_path);
    }

    public function destroy(Download $download)
    {
        try {
            // Удаляем файл с диска, если он существует
            if ($download->file_path && file_exists($download->file_path)) {
                unlink($download->file_path);
            }

            // Удаляем запись из базы данных
            $download->delete();

            return redirect()->route('downloads.index')->with('success', 'Файл успешно удален');
        } catch (\Exception $e) {
            \Log::error('Delete error: ' . $e->getMessage());
            return redirect()->route('downloads.index')->with('error', 'Ошибка при удалении файла');
        }
    }

    public function update(Request $request, Download $download)
    {
        $request->validate([
            'title' => 'required|string|max:255'
        ]);

        try {
            $download->update(['title' => $request->title]);

            return response()->json([
                'success' => true,
                'message' => 'Название успешно обновлено'
            ]);
        } catch (\Exception $e) {
            \Log::error('Update title error: ' . $e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Ошибка при обновлении названия'
            ], 500);
        }
    }

    private function extractVideoId(string $url): ?string
    {
        $parsedUrl = parse_url($url);

        if (!isset($parsedUrl['path'])) {
            return null;
        }

        $pathParts = explode('/', trim($parsedUrl['path'], '/'));
        return end($pathParts);
    }
}
