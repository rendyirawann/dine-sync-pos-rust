<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\LoginRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;
use Illuminate\Validation\ValidationException;
use App\Models\User;
use Jenssegers\Agent\Agent;

class AuthenticatedSessionController extends Controller
{
    /**
     * Display the login view.
     */
    public function create(): View
    {
        return view('auth.login');
    }

    /**
     * Handle an incoming authentication request.
     */
    public function store(LoginRequest $request)
    {
        // 1. PROSES LOGIN & RATE LIMITER (PENTING!)
        // Kita panggil fungsi authenticate() dari LoginRequest.
        // Di sinilah logic deteksi Email/WA/Nama DAN logic Lockout (10s, 15s) berjalan.
        $request->authenticate();

        // 2. Regenerate Session (Keamanan standar)
        $request->session()->regenerate();

        // 3. Ambil Data User
        $user = Auth::user();

        // 4. Cek Status Banned (Double Check setelah login berhasil)
        if ($user->banned_at) {
            Auth::guard('web')->logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();

            // Respon jika banned
            if ($request->expectsJson()) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Akun Anda telah dibekukan. Silakan hubungi admin.',
                ], 403);
            }
            throw ValidationException::withMessages([
                'email' => 'Akun Anda telah dibekukan.',
            ]);
        }

        // 5. Update Data User (IP & Last Login)
        $user->update([
            'last_ip' => $request->ip(),
            'last_login' => now(),
        ]);

        // 6. Catat Activity Log (Spatie + Agent)
        $agent = new Agent;
        if (function_exists('activity')) {
            activity()
                ->useLog('login')
                ->causedBy($user)
                ->withProperties([
                    'ip' => $request->ip(),
                    'agent' => [
                        'browser' => $agent->browser() . ' ' . $agent->version($agent->browser()),
                        'os' => $agent->platform() . ' ' . $agent->version($agent->platform()),
                        'device' => $agent->device(),
                        'is_mobile' => $agent->isMobile(),
                        'is_desktop' => $agent->isDesktop(),
                        'raw' => $request->header('User-Agent'),
                    ],
                    'request' => [
                        'method' => $request->method(),
                        'url' => $request->fullUrl(),
                    ],
                ])
                ->log('Login berhasil');
        }

        // 7. Return Response (Support JSON untuk Metronic)
        if ($request->expectsJson()) {
            return response()->json([
                'status' => 'success',
                'message' => 'Login berhasil, mengalihkan...',
                'redirect' => route('dashboard')
            ], 200);
        }

        // Redirect biasa
        return redirect()->intended(route('dashboard'));
    }


    /**
     * Destroy an authenticated session.
     */
    public function destroy(Request $request): RedirectResponse
    {
        $user = Auth::user();
        $agent = new Agent;

        // Catat Log Logout
        if ($user && function_exists('activity')) {
            activity()
                ->useLog('logout')
                ->causedBy($user)
                ->withProperties([
                    'ip' => $request->ip(),
                    'agent' => [
                        'browser' => $agent->browser() . ' ' . $agent->version($agent->browser()),
                        'os' => $agent->platform() . ' ' . $agent->version($agent->platform()),
                        'device' => $agent->device(),
                        'is_mobile' => $agent->isMobile(),
                        'is_desktop' => $agent->isDesktop(),
                        'raw' => $request->header('User-Agent'),
                    ],
                    'request' => [
                        'method' => $request->method(),
                        'url' => $request->fullUrl(),
                    ],
                ])
                ->log('Logout berhasil');
        }

        Auth::guard('web')->logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('login');
    }
}
