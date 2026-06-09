@extends('auth.app')
@section('title', 'Lupa Password - DineSync POS')
@section('content')
    <div class="d-flex flex-column-fluid flex-lg-row-auto justify-content-center justify-content-lg-start p-12">

        <div class="bg-body d-flex flex-column flex-center rounded-4 w-md-600px p-10 shadow-lg">

            <div class="d-flex flex-center flex-column align-items-stretch h-lg-100 w-md-400px">

                {{-- Logo --}}
                <div class="d-flex flex-center flex-column flex-column-fluid mb-2">
                    <img alt="Logo" class="theme-light-show h-40px h-lg-150px"
                        src="{{ asset('assets/media/logos/dine-sync-pos2.png') }}" />
                    <img alt="Logo" class="theme-dark-show h-40px h-lg-150px"
                        src="{{ asset('assets/media/logos/dine-sync-pos2.png') }}" />
                </div>

                <div class="d-flex flex-center flex-column flex-column-fluid pb-15 pb-lg-20 my-6">

                    {{-- Title --}}
                    <div class="text-center mb-10">
                        <h1 class="text-gray-900 fw-bolder mb-3 fs-2">Lupa Kata Sandi?</h1>
                        <div class="text-gray-500 fw-semibold fs-6">
                            Masukkan email Anda dan kami akan mengirimkan<br>
                            tautan untuk mereset kata sandi Anda.
                        </div>
                    </div>

                    {{-- Session Status (success) --}}
                    @if (session('status'))
                        <div class="alert alert-success d-flex align-items-center p-4 mb-8 rounded-3 w-100" role="alert">
                            <i class="ki-outline ki-shield-tick fs-2hx text-success me-4"></i>
                            <div class="d-flex flex-column">
                                <h5 class="mb-1 fw-bold">Email Terkirim!</h5>
                                <span class="fs-6">{{ session('status') }}</span>
                            </div>
                        </div>
                    @endif

                    {{-- Form --}}
                    <form class="form w-100" id="kt_forgot_password_form" method="POST"
                        action="{{ route('password.email') }}">
                        @csrf

                        {{-- Email --}}
                        <div class="fv-row mb-8">
                            <input type="email" placeholder="Alamat Email Anda" name="email"
                                value="{{ old('email') }}" autocomplete="off"
                                class="form-control bg-transparent @error('email') is-invalid @enderror" />
                            @error('email')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        {{-- Submit Button --}}
                        <div class="d-grid mb-10">
                            <button type="submit" id="kt_forgot_password_submit" class="btn btn-primary">
                                <span class="indicator-label">
                                    <i class="ki-outline ki-send fs-4 me-2"></i>Kirim Tautan Reset
                                </span>
                                <span class="indicator-progress">Harap tunggu...
                                    <span class="spinner-border spinner-border-sm align-middle ms-2"></span>
                                </span>
                            </button>
                        </div>

                        {{-- Back to Login --}}
                        <div class="text-gray-500 text-center fw-semibold fs-6">
                            Ingat kata sandi Anda?
                            <a href="{{ route('login') }}" class="link-primary">Kembali ke Login</a>
                        </div>

                    </form>
                </div>
            </div>
        </div>
    </div>

    @push('scripts')
        <script>
            document.getElementById('kt_forgot_password_form').addEventListener('submit', function(e) {
                const btn = document.getElementById('kt_forgot_password_submit');
                const label = btn.querySelector('.indicator-label');
                const progress = btn.querySelector('.indicator-progress');
                btn.disabled = true;
                btn.classList.add('disabled');
                if (label) label.style.display = 'none';
                if (progress) progress.style.display = 'inline-block';
            });
        </script>
    @endpush
@endsection
