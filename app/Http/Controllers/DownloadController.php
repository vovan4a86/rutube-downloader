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
