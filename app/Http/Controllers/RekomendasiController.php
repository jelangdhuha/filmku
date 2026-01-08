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

    // ===============================
    // A. Riwayat film yang dirating
    // ===============================
    $ratedMoviesFormatted = collect([]);
    $ratedMovieIds = [];

    if ($userId) {
        $ratedRaw = DB::table('movies')
            ->join('ratings', 'movies.id', '=', 'ratings.movie_id')
            ->where('ratings.user_id', $userId)
            ->select('movies.*', 'ratings.rating as personal_rating')
            ->orderBy('ratings.created_at', 'desc')
            ->get();

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
    }

    // ===============================
    // B. Rekomendasi AI
    // ===============================
    $recommendations = [];

    if ($userId) {
        try {
            $response = Http::timeout(2)
                ->get("http://127.0.0.1:8001/recommend/{$userId}");

            if ($response->successful()) {
                $apiData = $response->json();

                if (is_array($apiData)) {
                    $recommendations = collect($apiData)
                        ->reject(function ($item) use ($ratedMovieIds) {
                            return in_array($item['movie_id'], $ratedMovieIds);
                        })
                        ->map(function ($item) {
                            return [
                                'movie_id' => $item['movie_id'],
                                'title'    => $item['title'] ?? 'Unknown',
                                'poster'   => $item['poster'] ?? null,
                            ];
                        })
                        ->values()
                        ->toArray();
                }
            }
        } catch (\Exception $e) {
            $recommendations = [];
        }
    }

    // ===============================
    // C. Koleksi film lainnya
    // ===============================
    $query = DB::table('movies');

    if (!empty($ratedMovieIds)) {
        $query->whereNotIn('id', $ratedMovieIds);
    }

    $films = $query->orderBy('title', 'asc')->paginate(21);

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
                     ->where('ratings.user_id', $userId);
            })
            ->select('movies.*', 'ratings.rating as personal_rating')
            ->orderByRaw('CASE WHEN ratings.rating IS NOT NULL THEN 1 ELSE 0 END')
            ->orderBy('title', 'asc');
        }

        $films = $dbQuery->paginate(22)->appends(['query' => $queryInput]);

        $hasil = collect($films->items())->map(function ($film) {
            $personalRating = $film->personal_rating ?? null;
            $globalRating   = $film->rating ?? 0;

            return [
                'movie_id'        => $film->id,
                'judul'           => $film->title,
                'skor'            => (float) ($personalRating ?? $globalRating),
                'poster'          => $film->poster_path,
                'is_personal'     => !is_null($personalRating),
                'personal_rating'=> $personalRating
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
            return response()->json(['success' => false], 401);
        }

        try {
            $userId = Auth::id();

            $request->validate([
                'movie_id' => 'required',
                'rating'   => 'required|integer|min:1|max:5'
            ]);

            $existing = DB::table('ratings')
                ->where('user_id', $userId)
                ->where('movie_id', $request->movie_id)
                ->first();

            if ($existing) {
                DB::table('ratings')
                    ->where('id', $existing->id)
                    ->update(['rating' => $request->rating]);
            } else {
                DB::table('ratings')->insert([
                    'user_id'    => $userId,
                    'movie_id'   => $request->movie_id,
                    'rating'     => $request->rating,
                    'created_at'=> now()
                ]);
            }

            return response()->json(['success' => true]);

        } catch (\Exception $e) {
            return response()->json(['success' => false], 500);
        }
    }
}
