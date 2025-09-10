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

Route::get('/dashboard', [DownloadController::class, 'index'])->middleware(['auth', 'verified'])->name('dashboard');
Route::post('/downloads', [DownloadController::class, 'store'])->name('downloads.store');
Route::get('/downloads/{download}', [DownloadController::class, 'download'])->name('downloads.download');
Route::delete('/downloads/{download}', [DownloadController::class, 'destroy'])->name('downloads.destroy');
Route::patch('/downloads/{download}', [DownloadController::class, 'update'])->name('downloads.update');

require __DIR__.'/auth.php';
