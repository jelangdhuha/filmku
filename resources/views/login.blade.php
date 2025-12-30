<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - MovieRec</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body {
            background: #041827;
            color: white;
            height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .login-box {
            background: #fff;
            color: #333;
            padding: 30px;
            border-radius: 10px;
            width: 100%;
            max-width: 400px;
        }

        .btn-primary {
            background-color: #01b4e4;
            border: none;
        }

        .btn-primary:hover {
            background-color: #0289ad;
        }
    </style>
</head>

<body>

    <div class="login-box">
        <h3 class="text-center mb-4">Login MovieRec</h3>

        @if ($errors->any())
            <div class="alert alert-danger">
                {{ $errors->first() }}
            </div>
        @endif

        <form action="{{ route('login') }}" method="POST">
            @csrf <div class="mb-3">
                <label for="username" class="form-label">Username</label>
                <input type="text" name="username" class="form-control" id="username" required autofocus
                    value="{{ old('username') }}">
            </div>

            <div class="mb-3">
                <label for="password" class="form-label">Password</label>
                <input type="password" name="password" class="form-control" id="password" required>
            </div>

            <button type="submit" class="btn btn-primary w-100 py-2">Masuk</button>
        </form>

        <div class="text-center mt-3">
            <a href="{{ route('home') }}" class="text-decoration-none small">Kembali ke Beranda</a>
        </div>
    </div>

</body>

</html>
