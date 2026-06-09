<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport"
        content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no, viewport-fit=cover">
    <title>Selamat Datang - {{ $setting->store_name ?? 'DineSync POS' }}</title>

    <link rel="stylesheet" href="https://fonts.googleapis.com/css?family=Inter:300,400,500,600,700" />
    <link href="{{ asset('assets/plugins/global/plugins.bundle.css') }}" rel="stylesheet" type="text/css" />
    <link href="{{ asset('assets/css/style.bundle.css') }}" rel="stylesheet" type="text/css" />

    <style>
        html,
        body {
            margin: 0;
            padding: 0;
            width: 100%;
            height: 100%;
            overflow-x: hidden;
            /* Background utama untuk layar lebar */
            background-color: #f5f8fa;
            font-family: 'Inter', sans-serif;
            -webkit-text-size-adjust: 100%;
            -webkit-tap-highlight-color: transparent;
        }

        /* Container sekarang FULL 100% mengisi layar apa pun */
        .mobile-container {
            width: 100%;
            min-height: 100vh;
            min-height: 100dvh;
            display: flex;
            flex-direction: column;
            background: #f5f8fa;
            /* Pastikan background bawaan ikut ke bawah */
        }

        /* HEADER FULL WIDTH */
        .cover-header {
            background: linear-gradient(135deg, #009ef7 0%, #0052cc 100%);
            position: relative;
            padding: calc(40px + env(safe-area-inset-top)) 30px 80px 30px;
            text-align: center;
            width: 100%;
        }

        .custom-shape-divider-bottom {
            position: absolute;
            bottom: 0;
            left: 0;
            width: 100%;
            overflow: hidden;
            line-height: 0;
            transform: rotate(180deg);
        }

        .custom-shape-divider-bottom svg {
            position: relative;
            display: block;
            width: calc(100% + 1.3px);
            height: 40px;
        }

        .custom-shape-divider-bottom .shape-fill {
            fill: #f5f8fa;
            /* 🔥 Samakan warna ombak dengan background bawah agar menyatu */
        }

        /* WRAPPER KONTEN (Dibatasi lebarnya agar rapi di tablet/desktop) */
        .content-wrapper {
            width: 100%;
            max-width: 480px;
            /* Batas maksimal form */
            margin: 0 auto;
            /* Tengah secara horizontal */
            display: flex;
            flex-direction: column;
            flex-grow: 1;
            /* Penuhi sisa tinggi layar */
            padding: 0 30px;
            /* Padding kiri-kanan */
        }

        .floating-card {
            animation: float 4s ease-in-out infinite;
            border-top: 4px solid #009ef7;
        }

        @keyframes float {
            0% {
                transform: translateY(0px);
            }

            50% {
                transform: translateY(-10px);
            }

            100% {
                transform: translateY(0px);
            }
        }

        .input-group-custom {
            background-color: #ffffff;
            /* Paksa form putih agar kontras */
        }

        .input-group-custom .input-group-text {
            background: transparent;
            border-right: none;
            padding-left: 20px;
        }

        .input-group-custom input {
            border-left: none;
            padding-left: 0;
            background-color: transparent !important;
        }

        .input-group-custom input:focus,
        .input-group-custom .input-group-text.focused {
            border-color: #009ef7;
            background-color: transparent;
        }

        .btn-modern {
            background: linear-gradient(90deg, #009ef7 0%, #0073e6 100%);
            box-shadow: 0 8px 15px rgba(0, 158, 247, 0.3);
            transition: all 0.3s ease;
        }

        .btn-modern:hover {
            transform: translateY(-2px);
            box-shadow: 0 12px 20px rgba(0, 158, 247, 0.4);
        }

        .btn-modern:active {
            transform: scale(0.98);
        }

        .footer-area {
            text-align: center;
            padding: 20px 20px calc(20px + env(safe-area-inset-bottom)) 20px;
            color: #a1a5b7;
            font-size: 0.85rem;
            margin-top: auto;
        }
    </style>
</head>

<body>

    <div class="mobile-container">

        <div class="cover-header">
            <div class="mb-4">
                <svg width="64" height="64" viewBox="0 0 24 24" fill="none"
                    xmlns="http://www.w3.org/2000/svg">
                    <path opacity="0.3"
                        d="M21 10H13V9H21V10ZM21 14H13V13H21V14ZM11 5H3V4H11V5ZM11 9H3V8H11V9ZM11 13H3V12H11V13Z"
                        fill="#ffffff" />
                    <path d="M22 2V18C22 19.1 21.1 20 20 20H4C2.9 20 2 19.1 2 18V2H22ZM20 18V4H4V18H20Z"
                        fill="#ffffff" />
                    <path
                        d="M7 22H17C17.6 22 18 21.6 18 21C18 20.4 17.6 20 17 20H7C6.4 20 6 20.4 6 21C6 21.6 6.4 22 7 22Z"
                        fill="#ffffff" />
                </svg>
            </div>

            <h1 class="text-white mb-2 fw-bolder fs-1">Hai, Selamat Datang! 🚀</h1>
            <span class="fs-6 text-white opacity-75">di {{ $setting->store_name ?? 'DineSync Cafe' }}</span>

            <div class="custom-shape-divider-bottom">
                <svg data-name="Layer 1" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 1200 120"
                    preserveAspectRatio="none">
                    <path
                        d="M321.39,56.44c58-10.79,114.16-30.13,172-41.86,82.39-16.72,168.19-17.73,250.45-.39C823.78,31,906.67,72,985.66,92.83c70.05,18.48,146.53,26.09,214.34,3V0H0V27.35A600.21,600.21,0,0,0,321.39,56.44Z"
                        class="shape-fill"></path>
                </svg>
            </div>
        </div>

        <div class="content-wrapper pt-5">

            <div class="text-center mb-10 mt-n15 position-relative z-index-1">
                <div class="bg-white rounded-4 px-8 py-5 d-inline-block shadow-sm floating-card">
                    <span class="text-gray-500 fw-bold fs-7 d-block mb-1 text-uppercase tracking-wider">Anda berada
                        di</span>
                    <span class="text-dark fw-bolder fs-2x">{{ $table->table_number }}</span>
                </div>
            </div>

            @if (isset($isOccupied) && $isOccupied)
                <div class="text-center bg-white rounded-4 shadow-sm p-6 mb-5">
                    <i class="ki-outline ki-lock-3 fs-5x text-danger mb-4"></i>
                    <h1 class="fw-bolder text-gray-900 mb-2 fs-2">Meja Terkunci</h1>
                    <p class="text-muted fs-7 mb-6">Meja ini sedang digunakan oleh pelanggan lain.</p>

                    <div
                        class="alert bg-light-warning border border-warning d-flex flex-column align-items-center p-4 mb-0 rounded-4">
                        <i class="ki-outline ki-information fs-3x text-warning mb-3"></i>
                        <span class="text-gray-800 text-center fw-semibold fs-8">Jika Anda berada di rombongan yang
                            sama, silakan lakukan tambahan pesanan dari HP pertama, atau panggil Kasir.</span>
                    </div>

                    <a href="{{ route('frontend.scan', $table->uuid) }}"
                        class="btn btn-light-primary w-100 mt-6 rounded-3 py-3 fw-bold">Cek Ulang Status</a>
                </div>
            @else
                <form action="{{ route('frontend.scan.post', $table->uuid) }}" method="POST" class="mt-2 w-100">
                    @csrf
                    <div class="fv-row mb-8">
                        <label class="form-label fs-5 fw-bolder text-gray-800 mb-3">Siapa Nama Anda?</label>

                        <div
                            class="input-group input-group-solid input-group-lg input-group-custom border border-gray-300 rounded-3">
                            <span class="input-group-text border-0" id="basic-addon1">
                                <i class="ki-outline ki-user fs-2 text-gray-500"></i>
                            </span>
                            <input type="text" class="form-control form-control-solid border-0 fs-4"
                                name="customer_name" placeholder="Ketik nama di sini..." required autocomplete="off">
                        </div>

                        <div class="text-muted fs-7 mt-3 d-flex align-items-start">
                            <i class="ki-outline ki-information-5 fs-5 text-primary me-2 mt-1"></i>
                            <span>Nama ini akan dipanggil saat pesanan siap.</span>
                        </div>
                    </div>

                    <button type="submit" class="btn btn-primary btn-modern w-100 fs-3 fw-bold py-4 rounded-3 mt-2">
                        Lihat Buku Menu <i class="ki-outline ki-arrow-right fs-1 ms-2"></i>
                    </button>
                </form>
            @endif

            <div class="footer-area">
                Powered with <i class="ki-outline ki-heart text-danger fs-6 mx-1"></i> by <strong>DineSync POS</strong>
            </div>

        </div>

    </div>

    <script src="{{ asset('assets/plugins/global/plugins.bundle.js') }}"></script>
    <script src="{{ asset('assets/js/scripts.bundle.js') }}"></script>
    <script>
        const inputField = document.querySelector('.input-group-custom input');
        const iconContainer = document.querySelector('.input-group-custom .input-group-text');

        inputField.addEventListener('focus', () => iconContainer.classList.add('focused'));
        inputField.addEventListener('blur', () => iconContainer.classList.remove('focused'));
    </script>
</body>

</html>
