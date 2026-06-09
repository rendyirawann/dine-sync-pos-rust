<?php

namespace App\Http\Requests\Auth;

use Illuminate\Auth\Events\Lockout;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\Cache; // Wajib import Cache
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class LoginRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     */
    public function rules(): array
    {
        return [
            'email' => ['required', 'string'], // Input bernama 'email' tapi isinya bisa WA/Nama
            'password' => ['required', 'string'],
        ];
    }

    /**
     * Attempt to authenticate the request's credentials.
     */
    public function authenticate(): void
    {
        // 1. Tentukan Key untuk Rate Limiter & Counter Gagal
        $throttleKey = $this->throttleKey();
        $failCounterKey = $throttleKey . ':fails'; // Key khusus untuk simpan jumlah gagal hari ini

        // 2. CEK DULU: Apakah user sedang dalam masa hukuman (Lockout)?
        if (RateLimiter::tooManyAttempts($throttleKey, 1)) {
            $seconds = RateLimiter::availableIn($throttleKey);

            // Lempar Error 429 agar ditangkap JS Countdown
            throw ValidationException::withMessages([
                'email' => "Terlalu banyak percobaan. Tunggu $seconds detik.",
                'seconds' => $seconds, // Data detik untuk JS
            ])->status(429);
        }

        // 3. DETEKSI TIPE LOGIN (Email / No WA / Nama)
        $loginValue = $this->input('email');

        if (filter_var($loginValue, FILTER_VALIDATE_EMAIL)) {
            $field = 'email';
        } elseif (is_numeric($loginValue)) {
            $field = 'no_wa';
        } else {
            $field = 'name'; // Asumsi kolom di DB adalah 'name'
        }

        // 4. COBA LOGIN
        if (! Auth::attempt([$field => $loginValue, 'password' => $this->input('password')], $this->boolean('remember'))) {

            // === JIKA LOGIN GAGAL ===

            // Ambil jumlah gagal sebelumnya dari Cache, lalu tambah 1
            $fails = Cache::get($failCounterKey, 0) + 1;

            // Simpan counter baru (Expired 1 hari biar besok reset)
            Cache::put($failCounterKey, $fails, now()->addDay());

            $lockDuration = 0;

            // --- ATURAN HUKUMAN BERTINGKAT ---
            if ($fails == 3) {
                $lockDuration = 10; // Gagal ke-3: Tunggu 10 detik
            } elseif ($fails == 4) {
                $lockDuration = 15; // Gagal ke-4: Tunggu 15 detik
            } elseif ($fails == 5) {
                $lockDuration = 20; // Gagal ke-5: Tunggu 20 detik
            } elseif ($fails >= 6) {
                $lockDuration = 60; // Gagal ke-6++: Tunggu 60 detik (Device Ban Sementara)
            }

            // Jika kena hukuman, KUNCI RateLimiter sekarang
            if ($lockDuration > 0) {
                // Hit limiter dengan durasi spesifik
                RateLimiter::hit($throttleKey, $lockDuration);

                throw ValidationException::withMessages([
                    'email' => "Gagal login $fails kali. Tunggu sebentar.",
                    'seconds' => $lockDuration, // Kirim durasi ke JS
                ])->status(429);
            }

            // Jika belum kena hukuman (baru salah 1x atau 2x), lempar error biasa
            throw ValidationException::withMessages([
                'email' => 'Akun atau Password salah.',
            ]);
        }

        // 5. JIKA SUKSES LOGIN
        // Wajib HAPUS semua catatan dosa (RateLimiter & Counter Cache)
        RateLimiter::clear($throttleKey);
        Cache::forget($failCounterKey);
    }

    /**
     * Get the rate limiting throttle key for the request.
     */
    public function throttleKey(): string
    {
        return Str::transliterate(Str::lower($this->input('email')) . '|' . $this->ip());
    }
}
