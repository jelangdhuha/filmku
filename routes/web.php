<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\RekomendasiController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\MovieController;

// Halaman utama
Route::get('/', [RekomendasiController::class, 'index'])->name('home');

// Rekomendasi & pencarian
Route::match(['get', 'post'], '/rekomendasi', [RekomendasiController::class, 'cariRekomendasi'])
    ->name('cek.rekomendasi');

// ======================
// Guest (belum login)
// ======================
Route::middleware('guest')->group(function () {
    Route::get('/login', [AuthController::class, 'showLoginForm'])->name('login');
    Route::post('/login', [AuthController::class, 'login']);
});

// ======================
// Authenticated user
// ======================
Route::middleware('auth')->group(function () {
    Route::post('/logout', [AuthController::class, 'logout'])->name('logout');
    Route::post('/simpan-rating', [RekomendasiController::class, 'simpanRating'])
        ->name('simpan.rating');
});

// Debug login (opsional, hapus di production)
Route::get('/cek-debug', function () {
    $user = Illuminate\Support\Facades\Auth::user();
    return "Halo! Anda login sebagai User ID: " . ($user ? $user->id : 'Belum Login');
});

// Sinkronisasi data ke Python
Route::get('/sync-data', [MovieController::class, 'syncData'])->name('sync.data');
