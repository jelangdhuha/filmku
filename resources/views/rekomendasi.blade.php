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
            --accent: #01b4e4
        }

        body {
            font-family: Inter, system-ui, Segoe UI, Roboto, "Helvetica Neue", Arial;
            background: #ffffff;
            color: #212529;
            overflow-x: hidden;
        }

        .navbar {
            background: #ffffff
        }

        .navbar .site-title {
            color: var(--accent);
            font-weight: 700
        }

        .hero {
            background: none;
            padding: 40px 0
        }

        /* === STYLE BARU UNTUK SCROLL SAMPING === */
        .horizontal-scroll-wrapper {
            display: flex;
            overflow-x: auto;
            padding-bottom: 20px;
            gap: 16px;
            /* Sembunyikan Scrollbar default tapi tetap bisa scroll */
            scrollbar-width: thin;
            scrollbar-color: var(--accent) #f1f1f1;
        }

        /* Agar card ukurannya tetap fix saat di-scroll */
        .horizontal-scroll-wrapper .movie-card {
            min-width: 180px;
            max-width: 180px;
            flex: 0 0 auto;
        }

        /* === GRID STYLE === */
        .movie-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(160px, 1fr));
            gap: 18px
        }

        .movie-card {
            background: #ffffff;
            border-radius: 8px;
            overflow: hidden;
            position: relative;
            box-shadow: 0 2px 8px rgba(2, 6, 23, 0.06);
            transition: transform 0.2s;
        }

        .movie-card:hover {
            transform: translateY(-5px);
        }

        .poster {
            width: 100%;
            height: 240px;
            object-fit: cover;
            background: #f1f3f5
        }

        .movie-info {
            padding: 10px
        }

        .rating-badge {
            position: absolute;
            left: 8px;
            top: 8px;
            color: #ffffff;
            padding: 4px 8px;
            border-radius: 6px;
            font-weight: 700;
            font-size: 0.8rem;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.2);
            z-index: 10;
        }

        .movie-title {
            color: #212529;
            font-weight: 600;
            font-size: 0.95rem;
            line-height: 1.2;
            height: 2.4em;
            overflow: hidden;
        }

        .movie-sub {
            color: #6c757d;
            font-size: 0.82rem
        }

        .btn-primary-custom {
            background: var(--accent);
            border: 0;
            color: #fff
        }

        /* Bintang Input */
        .star-rating .star-btn {
            color: #ccc;
            cursor: pointer;
            transition: 0.2s;
        }

        .star-rating .star-btn.fas,
        .star-rating .star-btn.active {
            color: #ffd166 !important;
        }

        .star-rating .star-btn.hover {
            transform: scale(1.2);
        }
    </style>
</head>

<body>

    <nav class="navbar navbar-expand-lg navbar-dark">
        <div class="container">
            <a class="navbar-brand d-flex align-items-center" href="{{ url('/') }}">
                <svg width="34" height="34" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg"
                    class="me-2">
                    <rect width="24" height="24" rx="4" fill="#01b4e4" />
                    <path d="M6 8h12v2H6zM6 12h9v2H6z" fill="#041827" />
                </svg>
                <span class="site-title">MovieRec</span>
            </a>
            <div class="d-flex ms-auto align-items-center">
                @auth
                    <span class="me-3">Halo, <strong>{{ Auth::user()->username }}</strong></span>
                    <form action="{{ route('logout') }}" method="POST">
                        @csrf
                        <button type="submit" class="btn btn-outline-danger btn-sm">Logout</button>
                    </form>
                @else
                    <a class="btn btn-outline-dark btn-sm" href="{{ route('login') }}">Sign In</a>
                @endauth
            </div>
        </div>
    </nav>

    <header class="hero mb-4"
        style="background-image: url('{{ asset('walpaper.jpg') }}'); background-size: cover; background-position: center;">
        <div class="container">
            <div class="row align-items-center" style="min-height: 350px;">
                <div class="col-md-6">
                    <h1 class="display-6 mb-2 text-white">Cari Film Favoritmu</h1>
                    <div class="search-card">
                        <form action="{{ route('cek.rekomendasi') }}" method="POST" class="row g-2">
                            @csrf
                            <div class="col-9">
                                <input type="text" name="query" class="form-control" placeholder="Judul film..."
                                    value="{{ $queryInput ?? '' }}">
                            </div>
                            <div class="col-3 d-grid">
                                <button type="submit" class="btn btn-primary-custom">Cari</button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </header>

    <main class="container mb-5">

        @if (isset($ratedMovies) && count($ratedMovies) > 0)
            <div class="mb-5">
                <h4 class="mb-3 border-start border-4 border-warning ps-3">
                    Film yang Sudah Dirating
                    <span class="text-muted fs-6 ms-2 fw-normal">(Geser untuk melihat)</span>
                </h4>

                <div class="horizontal-scroll-wrapper">
                    @foreach ($ratedMovies as $item)
                        <div class="movie-card">
                            <div class="position-relative" style="overflow: hidden;">
                                @php
                                    $isPersonal = true; // Karena di loop ratedMovies
                                    $badgeBg = '#ffc107';
                                    $badgeColor = '#000';
                                    $starColor = '#000';
                                    $userScore = round($item['skor']);
                                @endphp

                                <div class="rating-badge"
                                    style="background: {{ $badgeBg }}; color: {{ $badgeColor }};">
                                    {{ number_format((float) $item['skor'], 1) }}
                                    <i class="fas fa-star ms-1" style="color: {{ $starColor }}"></i>
                                    <div style="font-size: 0.6rem; font-weight: normal; margin-top: 2px;">RATING ANDA
                                    </div>
                                </div>

                                <img src="{{ $item['poster'] ?? 'https://via.placeholder.com/300x450?text=No+Poster' }}"
                                    alt="{{ $item['judul'] }}" class="poster d-block w-100">
                            </div>

                            <div class="movie-info">
                                <div class="movie-title" title="{{ $item['judul'] }}">
                                    {{ Str::limit($item['judul'], 45) }}
                                </div>
                                <div class="movie-sub text-muted">ID: {{ $item['movie_id'] }}</div>

                                <div class="user-rating-zone mt-2">
                                    <div class="star-rating" data-movie-id="{{ $item['movie_id'] }}"
                                        style="display: flex; justify-content: center; gap: 4px;">
                                        @for ($i = 1; $i <= 5; $i++)
                                            <i class="{{ $userScore >= $i ? 'fas active' : 'far' }} fa-star star-btn"
                                                data-value="{{ $i }}" style="font-size: 1.1rem;"></i>
                                        @endfor
                                    </div>
                                    <div class="rating-msg text-center"
                                        style="font-size: 0.65rem; color: #6c757d; margin-top: 2px;">
                                        Rating Anda: {{ $userScore }}
                                    </div>
                                </div>
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>

            <hr class="my-5" style="border-top: 2px dashed #eee;">
        @endif

        <div>
            <div class="d-flex justify-content-between align-items-center mb-3">
                <h4 class="mb-0 border-start border-4 border-info ps-3">
                    @if (isset($queryInput) && $queryInput !== '')
                        Hasil Pencarian "{{ $queryInput }}"
                    @else
                        Koleksi Film
                    @endif
                </h4>
                <div class="text-muted small">Total: <strong>{{ $films->total() }}</strong></div>
            </div>

            @if (count($hasil) > 0)
                <div class="movie-grid">
                    @foreach ($hasil as $item)
                        <div class="movie-card">
                            <div class="position-relative" style="overflow: hidden;">
                                @php
                                    $isPersonal = false; // Karena di loop koleksi biasa
                                    $badgeBg = 'rgba(1, 180, 228, 0.9)';
                                    $badgeColor = '#fff';
                                    $starColor = '#ffd166';
                                    $userScore = 0;
                                @endphp

                                <div class="rating-badge"
                                    style="background: {{ $badgeBg }}; color: {{ $badgeColor }};">
                                    {{ number_format((float) $item['skor'], 1) }}
                                    <i class="fas fa-star ms-1" style="color: {{ $starColor }}"></i>
                                </div>

                                <img src="{{ $item['poster'] ?? 'https://via.placeholder.com/300x450?text=No+Poster' }}"
                                    alt="{{ $item['judul'] }}" class="poster d-block w-100">
                            </div>

                            <div class="movie-info">
                                <div class="movie-title" title="{{ $item['judul'] }}">
                                    {{ Str::limit($item['judul'], 45) }}
                                </div>
                                <div class="movie-sub text-muted">ID: {{ $item['movie_id'] }}</div>

                                <div class="user-rating-zone mt-2">
                                    <div class="star-rating" data-movie-id="{{ $item['movie_id'] }}"
                                        style="display: flex; justify-content: center; gap: 4px;">
                                        @for ($i = 1; $i <= 5; $i++)
                                            <i class="far fa-star star-btn" data-value="{{ $i }}"
                                                style="font-size: 1.1rem;"></i>
                                        @endfor
                                    </div>
                                    <div class="rating-msg text-center"
                                        style="font-size: 0.65rem; color: #6c757d; margin-top: 2px;">
                                        Beri rating
                                    </div>
                                </div>
                            </div>
                        </div>
                    @endforeach
                </div>

                <div class="mt-5 d-flex justify-content-center">
                    {!! $films->links('pagination::bootstrap-5') !!}
                </div>
            @else
                <div class="alert alert-light text-center">Belum ada film lain yang tersedia.</div>
            @endif
        </div>

    </main>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Cek status login dari Blade ke JS
        const isUserLoggedIn = {{ Auth::check() ? 'true' : 'false' }};

        document.querySelectorAll('.star-rating .star-btn').forEach(star => {

            // 1. Efek Hover (Visual)
            star.addEventListener('mouseover', function() {
                let val = this.dataset.value;
                let parent = this.parentElement;
                parent.querySelectorAll('.star-btn').forEach(s => {
                    if (s.dataset.value <= val) {
                        s.classList.remove('far');
                        s.classList.add('fas', 'hover');
                        s.style.color = '#ffd166';
                    } else if (!s.classList.contains('active')) {
                        s.classList.remove('fas');
                        s.classList.add('far');
                        s.style.color = '#ccc';
                    }
                });
            });

            // 2. Efek Mouse Out
            star.addEventListener('mouseout', function() {
                let parent = this.parentElement;
                parent.querySelectorAll('.star-btn').forEach(s => {
                    s.classList.remove('hover');
                    if (s.classList.contains('active')) {
                        s.classList.remove('far');
                        s.classList.add('fas');
                        s.style.color = '#ffd166';
                    } else {
                        s.classList.remove('fas');
                        s.classList.add('far');
                        s.style.color = '#ccc';
                    }
                });
            });

            // 3. LOGIKA KLIK (SIMPAN)
            star.addEventListener('click', function() {
                // Cek Login dulu
                if (!isUserLoggedIn) {
                    alert("Silakan login terlebih dahulu untuk memberi rating!");
                    window.location.href = "{{ route('login') }}";
                    return;
                }

                let val = this.dataset.value;
                let parent = this.parentElement;
                let movieId = parent.dataset.movieId;
                let stars = parent.querySelectorAll('.star-btn');
                let msgElement = parent.nextElementSibling;

                // Visual Update
                stars.forEach(s => {
                    if (s.dataset.value <= val) {
                        s.classList.add('active', 'fas');
                        s.classList.remove('far');
                        s.style.color = '#ffd166';
                    } else {
                        s.classList.remove('active', 'fas');
                        s.classList.add('far');
                        s.style.color = '#ccc';
                    }
                });

                msgElement.innerText = "Menyimpan...";
                msgElement.style.color = "#6c757d";

                // --- KIRIM DATA ---
                // Menggunakan URL relative '/simpan-rating' agar aman dari isu localhost vs 127.0.0.1
                // Pastikan route di web.php url-nya adalah '/simpan-rating'
                fetch("/simpan-rating", {
                        method: "POST",
                        headers: {
                            "Content-Type": "application/json",
                            "Accept": "application/json", // <--- PENTING: Mencegah CORB!
                            "X-CSRF-TOKEN": "{{ csrf_token() }}"
                        },
                        body: JSON.stringify({
                            movie_id: movieId,
                            rating: val
                        })
                    })
                    .then(response => {
                        // Jika server mengirim error (misal 500 atau 401), kita ambil JSON-nya
                        return response.json().then(data => {
                            if (!response.ok) {
                                // Lempar error agar ditangkap catch di bawah
                                throw new Error(data.message || 'Terjadi kesalahan server.');
                            }
                            return data;
                        });
                    })
                    .then(data => {
                        // BERHASIL
                        msgElement.innerText = "Tersimpan!";
                        msgElement.style.color = "#198754";
                        console.log("Sukses:", data);
                    })
                    .catch(error => {
                        // GAGAL
                        console.error('Error Detail:', error);
                        msgElement.innerText = "Error: " + error.message; // Tampilkan error di layar
                        msgElement.style.color = "#dc3545";
                    });
            });
        });
    </script>
</body>

</html>
