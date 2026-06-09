<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport"
        content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no, viewport-fit=cover">
    <title>Pesanan Diterima!</title>
    <link rel="stylesheet" href="https://fonts.googleapis.com/css?family=Inter:300,400,500,600,700" />
    <link href="{{ asset('assets/plugins/global/plugins.bundle.css') }}" rel="stylesheet" type="text/css" />
    <link href="{{ asset('assets/css/style.bundle.css') }}" rel="stylesheet" type="text/css" />
    <style>
        body,
        html {
            height: 100%;
            margin: 0;
            background: #f5f8fa;
            font-family: 'Inter', sans-serif;
        }

        .mobile-container {
            width: 100%;
            max-width: 480px;
            margin: 0 auto;
            background: #ffffff;
            min-height: 100dvh;
            display: flex;
            flex-direction: column;
            justify-content: center;
            align-items: center;
            text-align: center;
            padding: 30px;
            box-shadow: 0 0 30px rgba(0, 0, 0, 0.05);
        }

        .success-icon {
            width: 100px;
            height: 100px;
            background: #e8fff3;
            color: #50cd89;
            border-radius: 50%;
            display: flex;
            justify-content: center;
            align-items: center;
            margin-bottom: 25px;
            animation: bounceIn 0.8s ease;
        }

        @keyframes bounceIn {
            0% {
                transform: scale(0);
            }

            50% {
                transform: scale(1.2);
            }

            100% {
                transform: scale(1);
            }
        }
    </style>
</head>

<body>
    <div class="mobile-container">
        <div class="success-icon shadow-sm">
            <i class="ki-outline ki-check fs-5x"></i>
        </div>
        <h1 class="fw-bolder text-gray-900 mb-3 fs-1">Pesanan Diterima! 🎉</h1>
        <p class="text-muted fs-5 mb-8">Terima kasih <b>{{ $customerName }}</b>. Pesanan Anda (Meja
            {{ $table->table_number }}) langsung dikirim ke dapur kami dan akan segera dihidangkan.</p>

        <div class="bg-light-primary rounded p-5 w-100 mb-8 border border-primary border-dashed">
            <span class="d-block fw-bold text-primary fs-6"><i class="ki-outline ki-time fs-4 me-1"></i> Silakan tunggu
                di meja Anda.</span>
        </div>

        <a href="{{ route('frontend.scan', $table->uuid) }}"
            class="btn btn-light-primary fw-bold px-8 py-3 rounded-pill">Lihat Menu Lagi</a>
    </div>
</body>

</html>
