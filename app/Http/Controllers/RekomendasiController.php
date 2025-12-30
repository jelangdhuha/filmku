<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth; // Tambahkan ini untuk cek User Login
use Illuminate\Support\Facades\Schema;

class RekomendasiController extends Controller
{
    // MENAMPILKAN HALAMAN UTAMA (Dengan Prioritas Rating User)
   public function index()
    {
        $userId = Auth::id();
        
        // --- BAGIAN A: FILM YANG SUDAH DIRATING (SCROLL SAMPING) ---
        $ratedMoviesFormatted = collect([]); 
        $ratedMovieIds = []; 

        if ($userId) {
            $ratedRaw = DB::table('movies')
                ->join('ratings', 'movies.id', '=', 'ratings.movie_id')
                ->where('ratings.user_id', '=', $userId)
                ->select('movies.*', 'ratings.rating as personal_rating')
                ->orderBy('ratings.created_at', 'desc') // Urutkan: yang baru dirating muncul duluan
                ->get();

            $ratedMovieIds = $ratedRaw->pluck('id')->toArray();

            $ratedMoviesFormatted = $ratedRaw->map(function($film) {
                return [
                    'movie_id'    => $film->id,
                    'judul'       => $film->title,
                    'skor'        => (float) $film->personal_rating, 
                    'poster'      => $film->poster_path,
                    'is_personal' => true
                ];
            });
        }

        // --- BAGIAN B: KOLEKSI FILM (GRID KE BAWAH) ---
        $query = DB::table('movies');

        // 1. Jangan tampilkan lagi film yang sudah dirating di bagian bawah
        if (!empty($ratedMovieIds)) {
            $query->whereNotIn('id', $ratedMovieIds);
        }

        // 2. LOGIKA SORTING (UBAH DISINI)
        // Sebelumnya: $query->inRandomOrder();
        // Sekarang: Diurutkan dari rating tertinggi
        
        // Pastikan kolom 'rating' ada di tabel movies. 
        // Jika nama kolomnya beda (misal: 'vote_average'), sesuaikan string 'rating' dibawah.
        $query->orderBy('rating', 'desc'); 
        
        // Opsional: Jika rating sama, urutkan berdasarkan judul A-Z
        $query->orderBy('title', 'asc');

        // 3. Ambil data (Pagination)
        $films = $query->paginate(18);

        // 4. Format data
        $unratedFormatted = collect($films->items())->map(function($film) {
            return [
                'movie_id'    => $film->id,
                'judul'       => $film->title,
                // Gunakan rating global dari database, jika null anggap 0
                'skor'        => (float) ($film->rating ?? 0), 
                'poster'      => $film->poster_path,
                'is_personal' => false
            ];
        });

        return view('rekomendasi', [
            'ratedMovies' => $ratedMoviesFormatted,
            'hasil'       => $unratedFormatted,
            'films'       => $films
        ]);
    }
    // MENAMPILKAN HASIL PENCARIAN
    public function cariRekomendasi(Request $request)
    {
        $queryInput = $request->input('query');
        $userId = Auth::id();
        
        // Setup Query Dasar
        $dbQuery = DB::table('movies')
            ->where('title', 'LIKE', "%{$queryInput}%");

        // --- LOGIKA TAMBAHAN: JOIN RATING USER ---
        if ($userId) {
            $dbQuery->leftJoin('ratings', function($join) use ($userId) {
                $join->on('movies.id', '=', 'ratings.movie_id')
                     ->where('ratings.user_id', '=', $userId);
            })
            ->select(
                'movies.*', 
                'ratings.rating as personal_rating'
            )
            // Urutkan: Yang sudah dirating user naik ke atas
            ->orderByRaw('CASE WHEN ratings.rating IS NOT NULL THEN 0 ELSE 1 END')
            // Lalu urutkan rating user tertinggi
            ->orderBy('ratings.rating', 'desc');
        }

        // Eksekusi Pagination
        $films = $dbQuery->paginate(22)->appends(['query' => $queryInput]);

        // Format Data
        $hasil = collect($films->items())->map(function($film) {
            $personalRating = $film->personal_rating ?? null;
            $globalRating   = $film->rating ?? 0;
            $skorAkhir      = $personalRating ? $personalRating : $globalRating;

            return [
                'movie_id'    => $film->id,
                'judul'       => $film->title,
                'skor'        => (float) $skorAkhir,
                'poster'      => $film->poster_path,
                'is_personal' => !is_null($personalRating)
            ];
        });

        return view('rekomendasi', compact('hasil', 'films', 'queryInput')); 
    }
    // Fungsi untuk menangani AJAX Request simpan rating
  public function simpanRating(Request $request)
    {
        // 1. Cek Login
        if (!Auth::check()) {
            return response()->json(['message' => 'User belum login.'], 401);
        }

        try {
            $userId = Auth::id();
            $movieId = $request->movie_id;
            $ratingVal = $request->rating;

            // 2. Validasi Input
            if (!$movieId || !$ratingVal) {
                return response()->json(['message' => 'Data tidak lengkap.'], 400);
            }

            // 3. Cek apakah user sudah pernah rating film ini?
            $existingRating = DB::table('ratings')
                ->where('user_id', $userId)
                ->where('movie_id', $movieId)
                ->first();

            if ($existingRating) {
                // === KONDISI UPDATE ===
                // Kita hanya update skor rating saja, tanpa updated_at
                DB::table('ratings')
                    ->where('id', $existingRating->id)
                    ->update([
                        'rating' => $ratingVal
                    ]);
            } else {
                // === KONDISI BARU (INSERT) ===
                // Kita insert tanpa created_at / updated_at
                DB::table('ratings')->insert([
                    'user_id'    => $userId,
                    'movie_id'   => $movieId,
                    'rating'     => $ratingVal
                    // Kolom created_at & updated_at SAYA HAPUS karena tidak ada di DB
                ]);
            }

            return response()->json(['message' => 'Berhasil disimpan!']);

        } catch (\Illuminate\Database\QueryException $e) {
            // Error Database Spesifik
            return response()->json([
                'message' => 'SQL Error: ' . $e->getMessage()
            ], 500);
        } catch (\Exception $e) {
            // Error Umum
            return response()->json([
                'message' => 'Server Error: ' . $e->getMessage()
            ], 500);
        }
    }
}