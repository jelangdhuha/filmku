<?php

use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\DB;
use App\Http\Controllers\RekomendasiController;
use App\Http\Controllers\AuthController; // <--- BARIS INI WAJIB ADA
use App\Http\Controllers\MovieController;


// 1. Halaman Utama
// Saya tambahkan ->name('home') agar controller login bisa redirect ke sini dengan mudah
Route::get('/', [RekomendasiController::class, 'index'])->name('home');

// 2. Route Rekomendasi/Cari
Route::match(['get', 'post'], '/rekomendasi', [RekomendasiController::class, 'cariRekomendasi'])->name('cek.rekomendasi');

// 3. Group untuk TAMU (Belum Login)
Route::middleware('guest')->group(function () {
    // Menampilkan form login
    Route::get('/login', [AuthController::class, 'showLoginForm'])->name('login');
    // Proses submit login
    Route::post('/login', [AuthController::class, 'login']);
});

// 4. Group untuk MEMBER (Sudah Login)
// Wajib ditambahkan agar tombol Logout di navbar berfungsi
Route::middleware('auth')->group(function () {
    Route::post('/logout', [AuthController::class, 'logout'])->name('logout');
});
// TEMPEL INI DI PALING BAWAH routes/web.php
Route::get('/cek-debug', function () {
    $user = Illuminate\Support\Facades\Auth::user();
    return "Halo! Anda login sebagai User ID: " . ($user ? $user->id : 'Belum Login');
});
// Pastikan baris ini ada di paling atas

// Route untuk menyimpan rating
Route::post('/simpan-rating', [RekomendasiController::class, 'simpanRating'])->name('simpan.rating')->middleware('auth');
// Route untuk sinkronisasi (bisa diakses siapa saja atau tambahkan middleware admin jika perlu)


// ... route lainnya ...

// ==========================================================
// GROUP KHUSUS USER LOGIN (Middleware 'auth')
// ==========================================================
// Semua route di dalam kurung kurawal ini HANYA BISA diakses kalau user sudah login
Route::middleware(['auth'])->group(function () {
    // ... route dashboard dll ...

    // TAMBAHKAN INI:
    
});
Route::get('/sync-data', [MovieController::class, 'syncData'])->name('sync.data');