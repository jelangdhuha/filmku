<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;

class MovieController extends Controller
{
    // Function lain (index, dll) tidak diubah

    public function syncData()
    {
        // 1. Path konfigurasi
        $folderPath = "D:/Sistem Rekomendasi/filmku-app/app/data";
        $pathMovies = $folderPath . "/movies_fixed.csv";
        $pathRatings = $folderPath . "/ratings_final_fixed.csv";

        if (!file_exists($folderPath)) {
            mkdir($folderPath, 0777, true);
        }

        // 2. Export data movies
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
            return redirect()->back()
                ->with('error', 'Gagal export movies: ' . $e->getMessage());
        }

        // 3. Export data ratings
        try {
            $ratings = DB::table('ratings')
                ->select('user_id', 'movie_id', 'rating')
                ->get();

            $handle2 = fopen($pathRatings, 'w');
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
            return redirect()->back()
                ->with('error', 'Gagal export ratings: ' . $e->getMessage());
        }

        // 4. Trigger service Python
        $pesan = "";

        try {
            Http::get('http://127.0.0.1:8001/system/reload-data');
            $response = Http::get('http://127.0.0.1:8001/system/retrain');

            if ($response->successful()) {
                $pesan = " dan model AI diperbarui.";
            } else {
                $json = $response->json();
                $errorMsg = $json['message'] ?? 'Unknown error';
                $pesan = " tetapi AI gagal diperbarui: $errorMsg.";
            }
        } catch (\Exception $e) {
            $pesan = " tetapi service Python tidak dapat diakses.";
        }

        return redirect()->back()
            ->with('success', 'Data film dan rating berhasil diperbarui' . $pesan);
    }
}
