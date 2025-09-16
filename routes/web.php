<?php

use App\Http\Controllers\DownloadController;
use App\Http\Controllers\ProfileController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

//Route::get('/dashboard', [ProfileController::class, 'index'])->middleware(['auth', 'verified'])->name('dashboard');

Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

Route::get('/diagnostics', function () {
    // Проверяем доступность yt-dlp
    $ytdlpCheck = shell_exec('which yt-dlp');
    $ytdlpVersion = shell_exec('yt-dlp --version');

    // Проверяем доступность ffmpeg
    $ffmpegCheck = shell_exec('which ffmpeg');
    $ffmpegVersion = shell_exec('ffmpeg -version | head -n 1');

    // Проверяем права на запись
    $storageWritable = is_writable(storage_path());
    $downloadsWritable = is_writable(storage_path('app/downloads'));

    return response()->json([
        'yt-dlp' => [
            'path' => trim($ytdlpCheck),
            'version' => trim($ytdlpVersion),
            'available' => !empty($ytdlpCheck)
        ],
        'ffmpeg' => [
            'path' => trim($ffmpegCheck),
            'version' => trim($ffmpegVersion),
            'available' => !empty($ffmpegCheck)
        ],
        'permissions' => [
            'storage_writable' => $storageWritable,
            'downloads_writable' => $downloadsWritable
        ]
    ]);
});

Route::get('/dashboard', [DownloadController::class, 'index'])->middleware(['auth', 'verified'])->name('dashboard');
Route::post('/downloads', [DownloadController::class, 'store'])->name('downloads.store');
Route::get('/downloads/progress', [DownloadController::class, 'progress'])->name('downloads.progress');
Route::get('/downloads/{download}', [DownloadController::class, 'download'])->name('downloads.download');
Route::delete('/downloads/{download}', [DownloadController::class, 'destroy'])->name('downloads.destroy');
Route::patch('/downloads/{download}', [DownloadController::class, 'update'])->name('downloads.update');
Route::post('/downloads/{download}/cancel', [DownloadController::class, 'cancel'])->name('downloads.cancel');

require __DIR__.'/auth.php';
