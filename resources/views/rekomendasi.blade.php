<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>MovieRec - Sistem Rekomendasi Film</title>

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700&display=swap" rel="stylesheet">

    <style>
        :root {
            --accent: #01b4e4;
            --dark-bg: #032541;
            --gold: #ffd166;
            --gray-star: #ccc;
        }

        body {
            font-family: 'Inter', sans-serif;
            background: #ffffff;
            color: #212529;
            overflow-x: hidden;
        }

        /* --- Navbar --- */
        .navbar {
            background: #ffffff;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.05);
        }

        .navbar .site-title {
            color: var(--accent);
            font-weight: 700;
            font-size: 1.2rem;
        }

        /* --- Hero Section --- */
        .hero {
            background-color: var(--dark-bg);
            /* Ganti url di bawah dengan gambar background Anda */
            background-image: linear-gradient(to right, rgba(3, 37, 65, 0.8), rgba(3, 37, 65, 0.6)), url('https://image.tmdb.org/t/p/original/8Y43POKjjKDGI9SMAENCaMt65ue.jpg');
            background-size: cover;
            background-position: center;
            padding: 60px 0;
            color: white;
        }

        /* --- Horizontal Scroll Wrapper (Netflix Style) --- */
        .horizontal-scroll-wrapper {
            display: flex;
            overflow-x: auto;
            padding-bottom: 20px;
            gap: 20px;
            scrollbar-width: thin;
            /* Firefox */
            scrollbar-color: var(--accent) #f1f1f1;
        }

        /* Custom Scrollbar Webkit */
        .horizontal-scroll-wrapper::-webkit-scrollbar {
            height: 8px;
        }

        .horizontal-scroll-wrapper::-webkit-scrollbar-track {
            background: #f1f1f1;
            border-radius: 4px;
        }

        .horizontal-scroll-wrapper::-webkit-scrollbar-thumb {
            background: var(--accent);
            border-radius: 4px;
        }

        .horizontal-scroll-wrapper .movie-card {
            min-width: 180px;
            max-width: 180px;
            flex: 0 0 auto;
        }

        /* --- Grid Style (Search Results) --- */
        .movie-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(160px, 1fr));
            gap: 20px;
        }

        /* --- Movie Card Base --- */
        .movie-card {
            background: #ffffff;
            border-radius: 8px;
            overflow: hidden;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
            transition: transform 0.2s;
            border: 1px solid #e3e3e3;
        }

        .movie-card:hover {
            transform: translateY(-5px);
        }

        .poster {
            width: 100%;
            height: 270px;
            /* Tinggi poster konsisten */
            object-fit: cover;
            background: #f1f3f5;
        }

        .movie-info {
            padding: 12px;
        }

        .movie-title {
            font-weight: 700;
            font-size: 0.9rem;
            line-height: 1.2;
            height: 2.4em;
            /* Batasi 2 baris teks */
            overflow: hidden;
            display: -webkit-box;
            -webkit-line-clamp: 2;
            -webkit-box-orient: vertical;
            margin-bottom: 4px;
        }

        .movie-sub {
            font-size: 0.75rem;
            color: #6c757d;
            margin-bottom: 8px;
        }

        /* --- Badges --- */
        .rating-badge {
            position: absolute;
            left: 8px;
            top: 8px;
            color: #ffffff;
            padding: 4px 8px;
            border-radius: 4px;
            font-weight: 700;
            font-size: 0.75rem;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.3);
            z-index: 2;
        }

        /* --- Star Rating System --- */
        .user-rating-zone {
            border-top: 1px solid #eee;
            padding-top: 8px;
        }

        .star-rating {
            display: flex;
            justify-content: center;
            gap: 5px;
            cursor: pointer;
        }

        .star-btn {
            font-size: 1.1rem;
            color: var(--gray-star);
            transition: color 0.2s, transform 0.1s;
        }

       
        .star-btn.active,
        .star-btn.fas.hover {
            color: var(--gold) !important;
        }

        .star-btn:hover {
            transform: scale(1.2);
        }

        .rating-msg {
            text-align: center;
            font-size: 0.7rem;
            color: #999;
            margin-top: 4px;
            min-height: 15px;
            
            font-weight: 600;
        }

        .ai-recommendation-section {
            background: #f0f7ff;
            padding: 25px;
            border-radius: 12px;
            border: 1px solid #cce5ff;
        }

        .btn-search {
            background: linear-gradient(to right, #1dd4a8, #01b4e4);
            border: none;
            color: white;
            font-weight: 700;
        }
    </style>
</head>

<body>

    <nav class="navbar navbar-expand-lg">
        <div class="container">
            <a class="navbar-brand d-flex align-items-center" href="{{ url('/') }}">
                <div style="background: var(--accent); width:30px; height:30px; border-radius:5px; margin-right:10px;">
                </div>
                <span class="site-title">MovieRec</span>
            </a>
            <div class="ms-auto">
                @auth
                    <span class="me-3 small text-muted">Halo, <strong>{{ Auth::user()->username }}</strong></span>
                    <form action="{{ route('logout') }}" method="POST" class="d-inline">
                        @csrf
                        <button type="submit" class="btn btn-outline-danger btn-sm"
                            style="font-size: 0.75rem;">Logout</button>
                    </form>
                @else
                    <a class="btn btn-primary btn-sm" href="{{ route('login') }}">Login</a>
                @endauth

                {{-- @auth
                    <a href="{{ route('sync.data') }}" class="btn btn-warning btn-sm"
                        onclick="return confirm('Proses ini akan melatih ulang AI. Lanjutkan?')">
                        🔄 Refresh & Retrain AI
                    </a>
                @endauth --}}
            </div>
        </div>
    </nav>

    <header class="hero mb-5"
        style="background-image: url('{{ asset('walpaper.jpg') }}'); background-size: cover; background-position: center;">
        <div class="container">
            <div class="row align-items-center" style="min-height: 250px;">
                <div class="col-md-7">
                    <h1 class="display-5 fw-bold mb-3">Temukan Film Favoritmu</h1>
                    <p class="lead mb-4" style="font-size: 1rem; opacity: 0.9;">Rekomendasi Film Dengan Konsep Model-Based Collaborative
                        Filtering Sesuai Dengan Selera Anda.
                    </p>

                    <form action="{{ route('cek.rekomendasi') }}" method="POST" class="d-flex gap-2">
                        @csrf
                        <input type="text" name="query" class="form-control form-control-lg"
                            placeholder="Ketik judul film (misal: Avengers)..." value="{{ $queryInput ?? '' }}"
                            style="border-radius: 30px;">
                        <button type="submit" class="btn btn-search btn-lg px-4"
                            style="border-radius: 30px;">Cari</button>
                    </form>
                </div>
            </div>
        </div>
    </header>

    <main class="container mb-5">

        {{-- ======================================================= --}}
        {{-- BAGIAN 1: TOP REKOMENDASI AI                            --}}
        {{-- ======================================================= --}}
        @if (isset($recommendations) && count($recommendations) > 0)
            <div class="mb-5 ai-recommendation-section">
                <div class="d-flex align-items-center mb-3">
                    <h4 class="mb-0 fw-bold text-primary">
                        <i class="fas fa-sparkles me-2"></i>Rekomendasi Untukmu
                    </h4>
                    {{-- <span class="badge bg-primary ms-3 rounded-pill">AI Picked</span> --}}
                </div>

                <div class="horizontal-scroll-wrapper">
                    @foreach ($recommendations as $rec)
                        @php
                            // Normalisasi Data (Array vs Object)
                            $recTitle = is_array($rec) ? $rec['title'] ?? $rec['judul'] : $rec->judul;
                            $recId = is_array($rec) ? $rec['movie_id'] ?? 0 : $rec->movie_id;
                            $posterPath = is_array($rec) ? $rec['poster'] ?? null : $rec->poster;

                            // Logika URL Poster
                            if (empty($posterPath)) {
                                $finalPoster = 'https://via.placeholder.com/300x450?text=' . urlencode($recTitle);
                            } elseif (str_starts_with($posterPath, 'http')) {
                                $finalPoster = $posterPath;
                            } else {
                                $finalPoster = 'https://image.tmdb.org/t/p/w500' . $posterPath;
                            }
                        @endphp

                        <div class="movie-card" data-movie-id="{{ $recId }}">
                            <div class="position-relative">
                                {{-- <div class="rating-badge" style="background: #6f42c1;">
                                    AI Score
                                </div> --}}
                                <img src="{{ $finalPoster }}" alt="{{ $recTitle }}" class="poster">
                            </div>

                            <div class="movie-info">
                                <div class="movie-title" title="{{ $recTitle }}">{{ $recTitle }}</div>
                                <div class="movie-sub">Rekomendasi</div>

                                <div class="user-rating-zone">
                                    <div class="star-rating">
                                        @for ($i = 1; $i <= 5; $i++)
                                            <i class="far fa-star star-btn" data-value="{{ $i }}"></i>
                                        @endfor
                                    </div>
                                    <div class="rating-msg">Beri Rating</div>
                                </div>
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>
        @endif


        {{-- ======================================================= --}}
        {{-- BAGIAN 2: FILM YANG SUDAH ANDA RATING (HISTORY)         --}}
        {{-- ======================================================= --}}
        @if (isset($ratedMovies) && count($ratedMovies) > 0)
            <div class="mb-5">
                <h4 class="mb-3 fw-bold border-start border-4 border-warning ps-3">
                    Film yang Sudah Kamu Rating
                </h4>

                <div class="horizontal-scroll-wrapper">
                    @foreach ($ratedMovies as $item)
                        @php
                            $userScore = round($item['skor']); // Rating user dari DB
                            $posterPath = $item['poster'] ?? null;
                            if (empty($posterPath)) {
                                $finalPoster = 'https://via.placeholder.com/300x450?text=No+Poster';
                            } elseif (str_starts_with($posterPath, 'http')) {
                                $finalPoster = $posterPath;
                            } else {
                                $finalPoster = 'https://image.tmdb.org/t/p/w500' . $posterPath;
                            }
                        @endphp

                        <div class="movie-card" data-movie-id="{{ $item['movie_id'] }}">
                            <div class="position-relative">
                                <div class="rating-badge" style="background: #ffc107; color: #000;">
                                    {{ $userScore }} <i class="fas fa-star small"></i>
                                </div>
                                <img src="{{ $finalPoster }}" alt="{{ $item['judul'] }}" class="poster">
                            </div>

                            <div class="movie-info">
                                <div class="movie-title">{{ Str::limit($item['judul'], 40) }}</div>
                                <div class="movie-sub">Ratingmu: {{ $userScore }}/5</div>

                                <div class="user-rating-zone">
                                    <div class="star-rating">
                                        @for ($i = 1; $i <= 5; $i++)
                                            <i class="{{ $userScore >= $i ? 'fas active' : 'far' }} fa-star star-btn"
                                                data-value="{{ $i }}"></i>
                                        @endfor
                                    </div>
                                    <div class="rating-msg" style="color: #198754;">Tersimpan</div>
                                </div>
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>
        @endif


        {{-- ======================================================= --}}
        {{-- BAGIAN 3: HASIL PENCARIAN / SEMUA KOLEKSI               --}}
        {{-- ======================================================= --}}
        <div class="mt-4">
            <h4 class="mb-4 fw-bold border-start border-4 border-info ps-3">
                @if (isset($queryInput) && $queryInput !== '')
                    Hasil Pencarian: "{{ $queryInput }}"
                @else
                    Koleksi Film Lengkap
                @endif
            </h4>

            @if (count($hasil) > 0)
                <div class="movie-grid">
                    @foreach ($hasil as $item)
                        @php
                            $posterPath = $item['poster'] ?? null;
                            if (empty($posterPath)) {
                                $finalPoster = 'https://via.placeholder.com/300x450?text=No+Poster';
                            } elseif (str_starts_with($posterPath, 'http')) {
                                $finalPoster = $posterPath;
                            } else {
                                $finalPoster = 'https://image.tmdb.org/t/p/w500' . $posterPath;
                            }
                        @endphp

                        <div class="movie-card" data-movie-id="{{ $item['movie_id'] }}">
                            <div class="position-relative">
                                <div class="rating-badge" style="background: rgba(0,0,0,0.6);">
                                    {{-- GANTI 'vote_average' MENJADI 'skor' --}}
                                    {{ number_format((float) ($item['skor'] ?? 0), 1) }}
                                </div>
                                <img src="{{ $finalPoster }}" alt="{{ $item['judul'] }}" class="poster">
                            </div>

                            <div class="movie-info">
                                <div class="movie-title">{{ $item['judul'] }}</div>
                                <div class="movie-sub text-truncate">{{ $item['genres'] ?? 'General' }}</div>

                                @php
                                    // Ambil rating user jika ada (hasil search / koleksi)
                                    $userRating = $item['personal_rating'] ?? 0;
                                @endphp

                                <div class="user-rating-zone">
                                    <div class="star-rating" data-initial-rating="{{ $userRating }}">
                                        @for ($i = 1; $i <= 5; $i++)
                                            <i class="{{ $i <= $userRating ? 'fas active' : 'far' }} fa-star star-btn"
                                                data-value="{{ $i }}"></i>
                                        @endfor
                                    </div>
                                    <div class="rating-msg">
                                        {{ $userRating > 0 ? 'Tersimpan' : 'Beri nilai' }}
                                    </div>
                                </div>

                            </div>
                        </div>
                    @endforeach
                </div>

                <div class="mt-5 d-flex justify-content-center">
                    {!! $films->withQueryString()->links('pagination::bootstrap-5') !!}
                </div>
            @else
                <div class="alert alert-light text-center border">
                    <i class="fas fa-film fa-3x mb-3 text-muted"></i>
                    <p>Film tidak ditemukan.</p>
                </div>
            @endif
        </div>

    </main>

    <footer class="text-center py-4 bg-light border-top mt-5">
        <p class="mb-0 text-muted small">&copy; {{ date('Y') }} MovieRec System</p>
    </footer>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>

    <script>
        document.addEventListener("DOMContentLoaded", function() {

            // Cek status login dari Blade
            const isUserLoggedIn = {{ Auth::check() ? 'true' : 'false' }};
            const loginUrl = "{{ route('login') }}";

            // Ambil semua tombol bintang di halaman
            const stars = document.querySelectorAll('.star-rating .star-btn');

            stars.forEach(star => {

                // 1. EVENT: HOVER (Mouse Masuk)
                star.addEventListener('mouseenter', function() {
                    const value = parseInt(this.dataset.value);
                    const container = this.parentElement;
                    const siblings = container.querySelectorAll('.star-btn');

                    // Loop semua bintang di container ini
                    siblings.forEach(s => {
                        const sValue = parseInt(s.dataset.value);
                        if (sValue <= value) {
                            // Warnai kuning (tambahkan kelas .hover)
                            s.classList.remove('far');
                            s.classList.add('fas', 'hover');
                        } else {
                            // Jika belum diklik (tidak punya .active), biarkan abu-abu
                            if (!s.classList.contains('active')) {
                                s.classList.remove('fas', 'hover');
                                s.classList.add('far');
                            }
                        }
                    });
                });

                // 2. EVENT: MOUSE LEAVE (Mouse Keluar)
                star.addEventListener('mouseleave', function() {
                    const container = this.parentElement;
                    const siblings = container.querySelectorAll('.star-btn');

                    siblings.forEach(s => {
                        // Hapus efek hover
                        s.classList.remove('hover');

                        // Kembalikan ke state asli (berdasarkan class .active)
                        if (s.classList.contains('active')) {
                            // Jika sudah dirating: Kuning Solid
                            s.classList.remove('far');
                            s.classList.add('fas');
                        } else {
                            // Jika belum dirating: Abu-abu Outline
                            s.classList.remove('fas');
                            s.classList.add('far');
                        }
                    });
                });

                // 3. EVENT: CLICK (Simpan Data & Update UI Permanen)
                star.addEventListener('click', function() {
                    if (!isUserLoggedIn) {
                        alert("Silakan login untuk memberi rating!");
                        window.location.href = loginUrl;
                        return;
                    }

                    const value = parseInt(this.dataset.value);
                    const container = this.parentElement;
                    const grandParent = container.closest('.movie-card'); 
                    const movieId = grandParent.dataset.movieId;
                    const siblings = container.querySelectorAll('.star-btn');
                    const msgBox = container.nextElementSibling; 

                    
                    siblings.forEach(s => {
                        const sValue = parseInt(s.dataset.value);
                        if (sValue <= value) {
                            s.classList.add('active', 'fas');
                            s.classList.remove('far');
                        } else {
                            s.classList.remove('active', 'fas');
                            s.classList.add('far');
                        }
                    });

                    // Update Teks Status
                    msgBox.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Menyimpan...';
                    msgBox.style.color = '#6c757d';

                    // B. KIRIM KE SERVER (AJAX)
                    fetch("{{ url('/simpan-rating') }}", {
                            method: "POST",
                            headers: {
                                "Content-Type": "application/json",
                                "X-CSRF-TOKEN": "{{ csrf_token() }}"
                            },
                            body: JSON.stringify({
                                movie_id: movieId,
                                rating: value
                            })
                        })
                        .then(response => response.json())
                        .then(data => {
                            if (data.success) {
                                msgBox.innerText = "Tersimpan";
                                msgBox.style.color = "#198754"; 
                            } else {
                                msgBox.innerText = "Gagal";
                                msgBox.style.color = "#dc3545"; 
                            }
                        })
                        .catch(error => {
                            console.error('Error:', error);
                            msgBox.innerText = "Error";
                            msgBox.style.color = "#dc3545";
                        });
                });

            });
        });
    </script>
</body>

</html>
