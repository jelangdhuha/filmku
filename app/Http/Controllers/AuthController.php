<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class AuthController extends Controller
{
    // 1. Menampilkan Form Login
    public function showLoginForm()
    {
        return view('login');
    }

    // 2. Memproses Login
    public function login(Request $request)
    {
        // Validasi input
        $credentials = $request->validate([
            'username' => ['required'],
            'password' => ['required'],
        ]);

        // Coba login (Auth::attempt otomatis mengecek hash password)
        if (Auth::attempt($credentials)) {
            $request->session()->regenerate();

            // Jika sukses, alihkan ke dashboard
            return redirect()->intended('/');
        }

        // Jika gagal, kembalikan ke form login dengan pesan error
        return back()->withErrors([
            'username' => 'Username atau password salah.',
        ])->onlyInput('username');
    }

    // 3. Proses Logout
    public function logout(Request $request)
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect('/login');
    }
}