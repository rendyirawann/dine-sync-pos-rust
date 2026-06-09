@extends('auth.app')
@section('title', 'Reset Password - DineSync POS')
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
                        <h1 class="text-gray-900 fw-bolder mb-3 fs-2">Buat Kata Sandi Baru</h1>
                        <div class="text-gray-500 fw-semibold fs-6">
                            Kata sandi minimal 8 karakter.
                        </div>
                    </div>

                    {{-- Form --}}
                    <form class="form w-100" id="kt_reset_password_form" method="POST"
                        action="{{ route('password.store') }}">
                        @csrf

                        {{-- Hidden fields --}}
                        <input type="hidden" name="token" value="{{ $request->route('token') }}" />

                        {{-- Email (readonly) --}}
                        <div class="fv-row mb-8">
                            <input type="email" name="email"
                                value="{{ old('email', $request->email) }}"
                                placeholder="Alamat Email"
                                autocomplete="username"
                                readonly
                                class="form-control bg-transparent @error('email') is-invalid @enderror" />
                            @error('email')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        {{-- New Password --}}
                        <div class="fv-row mb-8 position-relative">
                            <input type="password" name="password" id="newPasswordInput"
                                placeholder="Kata Sandi Baru"
                                autocomplete="new-password"
                                class="form-control bg-transparent @error('password') is-invalid @enderror" />
                            <span class="btn btn-sm btn-icon position-absolute translate-middle top-50 end-0 me-n2"
                                id="toggleNewPassword">
                                <i class="ki-outline ki-eye-slash fs-2" id="toggleNewIcon"></i>
                            </span>
                            @error('password')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        {{-- Confirm Password --}}
                        <div class="fv-row mb-10 position-relative">
                            <input type="password" name="password_confirmation" id="confirmPasswordInput"
                                placeholder="Konfirmasi Kata Sandi"
                                autocomplete="new-password"
                                class="form-control bg-transparent" />
                            <span class="btn btn-sm btn-icon position-absolute translate-middle top-50 end-0 me-n2"
                                id="toggleConfirmPassword">
                                <i class="ki-outline ki-eye-slash fs-2" id="toggleConfirmIcon"></i>
                            </span>
                        </div>

                        {{-- Submit --}}
                        <div class="d-grid mb-10">
                            <button type="submit" id="kt_reset_password_submit" class="btn btn-primary">
                                <span class="indicator-label">
                                    <i class="ki-outline ki-lock-3 fs-4 me-2"></i>Simpan Kata Sandi Baru
                                </span>
                                <span class="indicator-progress">Harap tunggu...
                                    <span class="spinner-border spinner-border-sm align-middle ms-2"></span>
                                </span>
                            </button>
                        </div>

                        {{-- Back to Login --}}
                        <div class="text-gray-500 text-center fw-semibold fs-6">
                            <a href="{{ route('login') }}" class="link-primary">Kembali ke Login</a>
                        </div>

                    </form>
                </div>
            </div>
        </div>
    </div>

    @push('scripts')
        <script>
            // Toggle New Password
            const toggleNew = document.getElementById('toggleNewPassword');
            const newInput = document.getElementById('newPasswordInput');
            const newIcon = document.getElementById('toggleNewIcon');
            if (toggleNew && newInput) {
                toggleNew.addEventListener('click', function() {
                    const type = newInput.getAttribute('type') === 'password' ? 'text' : 'password';
                    newInput.setAttribute('type', type);
                    newIcon.classList.toggle('ki-eye-slash');
                    newIcon.classList.toggle('ki-eye');
                });
            }

            // Toggle Confirm Password
            const toggleConfirm = document.getElementById('toggleConfirmPassword');
            const confirmInput = document.getElementById('confirmPasswordInput');
            const confirmIcon = document.getElementById('toggleConfirmIcon');
            if (toggleConfirm && confirmInput) {
                toggleConfirm.addEventListener('click', function() {
                    const type = confirmInput.getAttribute('type') === 'password' ? 'text' : 'password';
                    confirmInput.setAttribute('type', type);
                    confirmIcon.classList.toggle('ki-eye-slash');
                    confirmIcon.classList.toggle('ki-eye');
                });
            }

            // Loading state on submit
            document.getElementById('kt_reset_password_form').addEventListener('submit', function() {
                const btn = document.getElementById('kt_reset_password_submit');
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
