<?php

namespace App\Http\Controllers;
use App\Http\Controllers\MovieController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Http; 

class RekomendasiController extends Controller
{
    public function index()
    {
        $userId = Auth::id();
        try {
        // Kita buat instance MovieController dan panggil fungsinya
        $movieController = new MovieController();
        $movieController->syncData(); 
    } catch (\Exception $e) {
        // Jika gagal sync, abaikan saja agar halaman tetap tampil
    }
        // ==========================================================
        // BAGIAN A: FILM YANG SUDAH DIRATING (HISTORY)
        // ==========================================================
        $ratedMoviesFormatted = collect([]);
        $ratedMovieIds = [];

        if ($userId) {
            // Kita gunakan try-catch agar kalau kolom created_at tidak ada, tidak error 500
            try {
                $query = DB::table('movies')
                    ->join('ratings', 'movies.id', '=', 'ratings.movie_id')
                    ->where('ratings.user_id', '=', $userId)
                    ->select('movies.*', 'ratings.rating as personal_rating');
                
                // Cek apakah perlu sorting based on timestamp
                // Jika tabel ratings tidak punya created_at, hapus baris orderBy ini
                $query->orderBy('ratings.created_at', 'desc');

                $ratedRaw = $query->get();

                $ratedMovieIds = $ratedRaw->pluck('id')->toArray();

                $ratedMoviesFormatted = $ratedRaw->map(function ($film) {
                    return [
                        'movie_id'    => $film->id,
                        'judul'       => $film->title,
                        'skor'        => (float) $film->personal_rating,
                        'poster'      => $film->poster_path,
                        'is_personal' => true
                    ];
                });
            } catch (\Exception $e) {
                // Fallback jika query gagal (misal kolom created_at tidak ada)
                $ratedMoviesFormatted = collect([]);
            }
        }

        // ==========================================================
        // BAGIAN B: REKOMENDASI AI (ANTI-CRASH FIXED)
        // ==========================================================
        $recommendations = [];

        if ($userId) {
            try {
                // Timeout dipercepat jadi 2 detik agar user tidak menunggu lama jika Python mati
                $response = Http::timeout(2)->get("http://127.0.0.1:8001/recommend/{$userId}");
                
                if ($response->successful()) {
                    $apiData = $response->json(); 

                    // [PENTING] Validasi Struktur Data
                    // Pastikan data yang diterima adalah ARRAY OF OBJECTS (Daftar Film)
                    // Bukan Array Asosiatif (Pesan Error seperti {"status": "error"})
                    if (is_array($apiData) && !empty($apiData) && isset($apiData[0]) && is_array($apiData[0])) {
                        
                        $recommendations = collect($apiData)->map(function ($item) {
                            // Validasi ekstra per item
                            return [
                                'movie_id' => $item['movie_id'] ?? 0,
                                'title'    => $item['title'] ?? 'Unknown',    
                                'poster'   => $item['poster'] ?? null,   
                            ];
                        });

                    } else {
                        // Jika Python merespon tapi bukan daftar film (misal pesan error), kosongkan saja.
                        $recommendations = [];
                    }
                }
            } catch (\Exception $e) {
                // Jika Python mati/timeout, biarkan kosong (jangan error)
                $recommendations = [];
            }
        }

        // ==========================================================
        // BAGIAN C: KOLEKSI FILM UMUM (NONTON LAINNYA)
        // ==========================================================
        $query = DB::table('movies');

        // Jangan tampilkan film yang sudah ditonton
        if (!empty($ratedMovieIds)) {
            $query->whereNotIn('id', $ratedMovieIds);
        }

        $query->orderBy('title', 'asc');
        $films = $query->paginate(18);

        $unratedFormatted = collect($films->items())->map(function ($film) {
            return [
                'movie_id'    => $film->id,
                'judul'       => $film->title,
                'skor'        => (float) ($film->rating ?? 0), 
                'poster'      => $film->poster_path,
                'is_personal' => false
            ];
        });

        return view('rekomendasi', [
            'ratedMovies'     => $ratedMoviesFormatted,
            'recommendations' => $recommendations,
            'hasil'           => $unratedFormatted,
            'films'           => $films
        ]);
    }

    public function cariRekomendasi(Request $request)
    {
        $queryInput = $request->input('query');
        $userId = Auth::id();

        $dbQuery = DB::table('movies')
            ->where('title', 'LIKE', "%{$queryInput}%");

        if ($userId) {
            $dbQuery->leftJoin('ratings', function ($join) use ($userId) {
                $join->on('movies.id', '=', 'ratings.movie_id')
                    ->where('ratings.user_id', '=', $userId);
            })
            ->select('movies.*', 'ratings.rating as personal_rating')
            ->orderByRaw('CASE WHEN ratings.rating IS NOT NULL THEN 1 ELSE 0 END') 
            ->orderBy('title', 'asc');
        }

        $films = $dbQuery->paginate(22)->appends(['query' => $queryInput]);

        $hasil = collect($films->items())->map(function ($film) {
            $personalRating = $film->personal_rating ?? null;
            $globalRating   = $film->rating ?? 0;
            $skorAkhir      = $personalRating ? $personalRating : $globalRating;

            return [
                'movie_id'    => $film->id,
                'judul'       => $film->title,
                'skor'        => (float) $skorAkhir,
                'poster'      => $film->poster_path,
                'is_personal' => !is_null($personalRating),
                'personal_rating' => $personalRating 
            ];
        });

        return view('rekomendasi', [
            'hasil'           => $hasil,
            'films'           => $films,
            'queryInput'      => $queryInput,
            'recommendations' => [], 
            'ratedMovies'     => []  
        ]);
    }

    public function simpanRating(Request $request)
    {
        if (!Auth::check()) {
            return response()->json(['success' => false, 'message' => 'User belum login.'], 401);
        }

        try {
            $userId = Auth::id();
            
            $request->validate([
                'movie_id' => 'required',
                'rating'   => 'required|integer|min:1|max:5'
            ]);

            // Cek rating lama
            $existing = DB::table('ratings')
                ->where('user_id', $userId)
                ->where('movie_id', $request->movie_id)
                ->first();

            if ($existing) {
                // Update
                DB::table('ratings')
                    ->where('id', $existing->id)
                    ->update([
                        'rating' => $request->rating,
                        // 'updated_at' => now() // Uncomment jika kolom updated_at ada
                    ]);
            } else {
                // Insert Baru
                $dataInsert = [
                    'user_id'  => $userId,
                    'movie_id' => $request->movie_id,
                    'rating'   => $request->rating,
                ];

                // Cek apakah tabel ratings punya kolom created_at sebelum insert
                // Cara paling aman adalah menggunakan try-catch atau asumsi default
                // Disini saya tambahkan created_at, jika error di database, hapus baris ini.
                $dataInsert['created_at'] = now(); 

                DB::table('ratings')->insert($dataInsert);
            }

            return response()->json([
                'success' => true, 
                'message' => 'Berhasil disimpan!'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Server Error: ' . $e->getMessage()
            ], 500);
        }
    }
}