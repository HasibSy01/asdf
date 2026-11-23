<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Models\AuditLog;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;

class AuthController extends Controller
{
    public function showLoginForm()
    {
        if (Auth::check()) {
            return redirect()->route(Auth::user()->isAdmin() ? 'admin.dashboard' : 'gizi.dashboard');
        }
        return view('auth.login');
    }

    public function login(Request $request)
    {
        $credentials = $request->validate([
            'username' => 'required|string',
            'password' => 'required|string',
        ], [
            'username.required' => 'Username wajib diisi.',
            'password.required' => 'Kata sandi wajib diisi.',
        ]);

        $throttleKey = Str::lower($credentials['username']) . '|' . $request->ip();

        if (RateLimiter::tooManyAttempts($throttleKey, 5)) {
            $seconds = RateLimiter::availableIn($throttleKey);

            AuditLog::create([
                'user_id' => null,
                'username' => $credentials['username'],
                'role' => 'tamu',
                'action' => 'login_diblokir_throttling',
                'model_type' => 'user',
                'model_id' => null,
                'ip_address' => $request->ip(),
                'user_agent' => $request->userAgent(),
                'created_at' => now(),
            ]);

            return back()->withInput()->with('error', "Terlalu banyak percobaan login gagal. Silakan coba lagi dalam {$seconds} detik.");
        }

        $user = User::where('username', $credentials['username'])->first();

        // NFR-003: Pesan gagal login tidak boleh menyebutkan apakah username atau kata sandi yang salah
        if (!$user || !Hash::check($credentials['password'], $user->password)) {
            RateLimiter::hit($throttleKey, 60);

            AuditLog::create([
                'user_id' => $user?->id,
                'username' => $credentials['username'],
                'role' => $user?->role ?? 'tamu',
                'action' => 'login_gagal',
                'model_type' => 'user',
                'model_id' => $user?->id,
                'ip_address' => $request->ip(),
                'user_agent' => $request->userAgent(),
                'created_at' => now(),
            ]);

            return back()->withInput()->with('error', 'Kombinasi username atau kata sandi tidak valid.');
        }

        RateLimiter::clear($throttleKey);
        Auth::login($user, $request->boolean('remember'));
        $request->session()->regenerate();

        AuditLog::log('login', 'user', (string)$user->id, null, ['status' => 'berhasil']);

        if ($user->isAdmin()) {
            return redirect()->route('admin.dashboard')->with('success', 'Selamat datang kembali, Admin SPPG Ujung Lare.');
        }

        return redirect()->route('gizi.dashboard')->with('success', 'Selamat datang kembali, Ahli Gizi SPPG Parepare.');
    }

    public function logout(Request $request)
    {
        if (Auth::check()) {
            AuditLog::log('logout', 'user', (string)Auth::id());
        }

        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('login')->with('info', 'Anda telah berhasil keluar dari akun.');
    }
}

