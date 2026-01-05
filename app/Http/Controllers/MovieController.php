<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;

class MovieController extends Controller
{
    // ... Function index dll biarkan saja ...

    public function syncData()
    {
        // ==========================================
        // 1. CONFIG PATH (PERBAIKAN DISINI)
        // ==========================================
        
        // ❌ SALAH: "film"
        // ✅ BENAR: "filmku-app" (Sesuai screenshot Windows Explorer Anda)
        $folderPath = "D:/Sistem Rekomendasi/filmku-app/app/data";
        
        // File 1: Katalog Film
        $pathMovies = $folderPath . "/movies_fixed.csv";
        
        // File 2: Rating User (Pastikan namanya sama persis dengan di Python)
        $pathRatings = $folderPath . "/ratings_final_fixed.csv"; 

        // Buat folder jika belum ada
        if (!file_exists($folderPath)) {
            mkdir($folderPath, 0777, true);
        }

        // ==========================================
        // 2. EXPORT FILE A: MOVIES
        // ==========================================
        try {
            $movies = DB::table('movies')
                ->select('id', 'title', 'genre', 'poster_path', 'rating')
                ->get();

            $handle = fopen($pathMovies, 'w');
            fputcsv($handle, ['movie_id', 'title', 'genre', 'poster', 'rating']);

            foreach ($movies as $m) {
                fputcsv($handle, [
                    $m->id, 
                    $m->title, 
                    $m->genre, 
                    $m->poster_path, 
                    $m->rating
                ]);
            }
            fclose($handle);
        } catch (\Exception $e) {
            return redirect()->back()->with('error', 'Gagal Export Movies: ' . $e->getMessage());
        }

        // ==========================================
        // 3. EXPORT FILE B: RATINGS
        // ==========================================
        try {
            $ratings = DB::table('ratings')
                ->select('user_id', 'movie_id', 'rating')
                ->get();

            $handle2 = fopen($pathRatings, 'w');
            // Header ini harus sama dengan yang diminta Python
            fputcsv($handle2, ['user_id', 'movie_id', 'rating']); 

            foreach ($ratings as $r) {
                fputcsv($handle2, [
                    $r->user_id, 
                    $r->movie_id, 
                    $r->rating
                ]);
            }
            fclose($handle2);
        } catch (\Exception $e) {
            return redirect()->back()->with('error', 'Gagal Export Ratings: ' . $e->getMessage());
        }

        // ==========================================
        // 4. HUBUNGI PYTHON
        // ==========================================
        $pesan = "";
        try {
            // Panggil endpoint Python untuk baca ulang data
            Http::get('http://127.0.0.1:8001/system/reload-data');
            
            // Panggil endpoint Python untuk training ulang
            $response = Http::get('http://127.0.0.1:8001/system/retrain');

            if ($response->successful()) {
                $pesan = " & AI Semakin Pintar! 🧠";
            } else {
                // Tampilkan pesan error dari Python jika ada
                $json = $response->json();
                $errorMsg = isset($json['message']) ? $json['message'] : 'Unknown Error';
                $pesan = " (Tapi AI gagal update: $errorMsg).";
            }

        } catch (\Exception $e) {
            $pesan = " (⚠️ Warning: Python mati/tidak konek).";
        }

        return redirect()->back()->with('success', '✅ Data Film & Rating Terupdate' . $pesan);
    }
}