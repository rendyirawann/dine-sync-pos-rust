<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Display Monitor Antrian</title>
    <link rel="stylesheet" href="https://fonts.googleapis.com/css?family=Poppins:300,400,500,600,700" />
    <link href="{{ asset('assets/plugins/global/plugins.bundle.css') }}" rel="stylesheet" type="text/css" />
    <link href="{{ asset('assets/css/style.bundle.css') }}" rel="stylesheet" type="text/css" />
    <style>
        body {
            background: #f4f6fa;
            /* Warna dasar cerah & bersih */
            overflow: hidden;
            height: 100vh;
            font-family: 'Poppins', sans-serif;
            color: #181c32;
        }

        /* Header Mewah */
        .display-header {
            background: #ffffff;
            padding: 20px 40px;
            box-shadow: 0 5px 20px rgba(0, 0, 0, 0.03);
            position: relative;
            z-index: 10;
        }

        /* Layout Belah Dua */
        .split-screen {
            display: flex;
            height: calc(100vh - 90px);
        }

        /* SISI KIRI: CURRENT CALLED (Putih Bersih) */
        .left-panel {
            width: 60%;
            padding: 40px;
            background: #ffffff;
            border-right: 2px solid #eff2f5;
            display: flex;
            flex-direction: column;
            gap: 25px;
        }

        .current-card {
            background: #ffffff;
            border-radius: 24px;
            padding: 30px 40px;
            border: 2px solid #eff2f5;
            display: flex;
            align-items: center;
            justify-content: space-between;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.02);
            transition: all 0.3s ease;
        }

        .waiting-badge {
            width: 70px;
            height: 70px;
            border-radius: 20px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 2.5rem;
            font-weight: 900;
        }

        .card-number {
            font-size: 8rem;
            font-weight: 900;
            line-height: 1;
            letter-spacing: -2px;
        }

        /* SISI KANAN: WAITING LIST (Agak Biru Muda/Abu) */
        .right-panel {
            width: 40%;
            padding: 40px;
            background: #f9fbfd;
            position: relative;
        }

        .waiting-box {
            background: #ffffff;
            border-radius: 20px;
            padding: 20px;
            margin-bottom: 20px;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.02);
            border: 1px solid #eff2f5;
        }

        .waiting-list {
            display: flex;
            flex-wrap: wrap;
            gap: 10px;
            margin-top: 15px;
        }

        .waiting-item {
            background: #f4f6fa;
            color: #5e6278;
            padding: 8px 16px;
            border-radius: 12px;
            font-weight: 700;
            font-size: 1.2rem;
            border: 1px solid #e4e6ef;
        }

        /* Animasi Panggilan (Pulse) */
        @keyframes pulseA {
            0% {
                box-shadow: 0 0 0 0 rgba(80, 205, 137, 0.4);
            }

            70% {
                box-shadow: 0 0 0 20px rgba(80, 205, 137, 0);
            }

            100% {
                box-shadow: 0 0 0 0 rgba(80, 205, 137, 0);
            }
        }

        @keyframes pulseB {
            0% {
                box-shadow: 0 0 0 0 rgba(0, 158, 247, 0.4);
            }

            70% {
                box-shadow: 0 0 0 20px rgba(0, 158, 247, 0);
            }

            100% {
                box-shadow: 0 0 0 0 rgba(0, 158, 247, 0);
            }
        }

        @keyframes pulseC {
            0% {
                box-shadow: 0 0 0 0 rgba(241, 196, 15, 0.4);
            }

            70% {
                box-shadow: 0 0 0 20px rgba(241, 196, 15, 0);
            }

            100% {
                box-shadow: 0 0 0 0 rgba(241, 196, 15, 0);
            }
        }

        .called-active-A {
            animation: pulseA 2s infinite;
            border-color: #50cd89;
            background: #f1faff;
        }

        .called-active-B {
            animation: pulseB 2s infinite;
            border-color: #009ef7;
            background: #f1faff;
        }

        .called-active-C {
            animation: pulseC 2s infinite;
            border-color: #f1c40f;
            background: #fffdf2;
        }

        /* Overlay Awal */
        #overlay-start {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(255, 255, 255, 0.95);
            z-index: 9999;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            text-align: center;
        }

        /* Ilustrasi SVG Online */
        .illustration-bottom {
            position: absolute;
            bottom: -10px;
            right: -20px;
            width: 350px;
            opacity: 0.9;
            pointer-events: none;
            z-index: 0;
        }
    </style>
    <script>
        // FUNGSI INTI (God Mode: Diletakkan paling atas agar tidak mungkin error)
        window.isReady = false;
        function initAudio() {
            try {
                console.log("Tombol diklik, mengaktifkan audio...");
                const synth = window.speechSynthesis;
                let utterance = new SpeechSynthesisUtterance("Monitor aktif.");
                utterance.lang = 'id-ID';
                utterance.volume = 0;
                synth.speak(utterance);
                
                // Sembunyikan overlay
                const overlay = document.getElementById('overlay-start');
                if(overlay) overlay.style.display = 'none';
                window.isReady = true;
                console.log("Monitor Siap.");
            } catch (e) {
                console.error("Gagal inisialisasi audio:", e);
                const overlay = document.getElementById('overlay-start');
                if(overlay) overlay.style.display = 'none';
            }
        }
    </script>
</head>

<body>

    <div id="overlay-start" onclick="initAudio()">
        <i class="ki-outline ki-mouse fs-5x text-primary mb-5 shadow-sm p-5 bg-light-primary rounded-circle"></i>
        <h1 class="text-dark fs-2qx fw-bolder mb-3">Klik Layar Untuk Mengaktifkan Monitor</h1>
        <p class="text-gray-500 fs-4">Browser memerlukan satu kali interaksi untuk memutar suara panggilan secara
            otomatis.</p>
    </div>

    <div class="display-header d-flex justify-content-between align-items-center">
        <div class="d-flex align-items-center">
            <i class="ki-outline ki-shop fs-2hx text-primary me-4"></i>
            <h1 class="text-gray-900 fw-bolder fs-1 me-3 m-0">{{ $setting->store_name ?? 'DineSync POS' }}</h1>
            <span class="badge badge-light-success fs-7 fw-bold px-3 py-2">LIVE DISPLAY</span>
        </div>
        <div class="text-end">
            <h2 class="text-primary fw-bolder m-0 fs-1" id="live-clock">00:00:00</h2>
            <span class="text-gray-500 fw-semibold fs-6">{{ \Carbon\Carbon::today()->format('l, d F Y') }}</span>
        </div>
    </div>

    <div class="split-screen">

        <div class="left-panel">
            <h3 class="text-gray-500 text-uppercase tracking-wider fw-bolder fs-5 mb-2"><i
                    class="ki-outline ki-notification-on fs-2 me-2"></i> Nomor Antrian Dipanggil</h3>

            <div class="current-card border-success" id="card-A">
                <div class="d-flex align-items-center">
                    <div class="waiting-badge bg-light-success text-success me-6 shadow-sm">A</div>
                    <div>
                        <h2 class="fs-1 text-gray-800 fw-bolder mb-1">Meja Kecil</h2>
                        <span class="fs-5 text-gray-500 fw-semibold">(1 - 2 Orang)</span>
                    </div>
                </div>
                <div class="card-number text-success" id="current-A">{{ $lastA->queue_number ?? '---' }}</div>
            </div>

            <div class="current-card border-primary" id="card-B">
                <div class="d-flex align-items-center">
                    <div class="waiting-badge bg-light-primary text-primary me-6 shadow-sm">B</div>
                    <div>
                        <h2 class="fs-1 text-gray-800 fw-bolder mb-1">Meja Sedang</h2>
                        <span class="fs-5 text-gray-500 fw-semibold">(3 - 4 Orang)</span>
                    </div>
                </div>
                <div class="card-number text-primary" id="current-B">{{ $lastB->queue_number ?? '---' }}</div>
            </div>

            <div class="current-card border-warning" id="card-C">
                <div class="d-flex align-items-center">
                    <div class="waiting-badge bg-light-warning text-warning me-6 shadow-sm">C</div>
                    <div>
                        <h2 class="fs-1 text-gray-800 fw-bolder mb-1">Meja Besar</h2>
                        <span class="fs-5 text-gray-500 fw-semibold">(5+ Orang)</span>
                    </div>
                </div>
                <div class="card-number text-warning" id="current-C">{{ $lastC->queue_number ?? '---' }}</div>
            </div>
        </div>

        <div class="right-panel">
            <h3 class="text-gray-500 text-uppercase tracking-wider fw-bolder fs-5 mb-6"><i
                    class="ki-outline ki-time fs-2 me-2"></i> Daftar Tunggu</h3>

            <div class="waiting-box position-relative z-index-1">
                <div class="d-flex align-items-center border-bottom border-gray-200 pb-3">
                    <span class="badge badge-success fs-5 px-3 py-2 me-3">A</span>
                    <span class="fs-4 fw-bolder text-gray-700">Meja Kecil</span>
                </div>
                <div class="waiting-list" id="list-A">
                    @forelse($waitingA as $w)
                        <div class="waiting-item">{{ $w->queue_number }}</div>
                    @empty
                        <span class="text-muted fs-6 fst-italic mt-3">Tidak ada antrian</span>
                    @endforelse
                </div>
            </div>

            <div class="waiting-box position-relative z-index-1">
                <div class="d-flex align-items-center border-bottom border-gray-200 pb-3">
                    <span class="badge badge-primary fs-5 px-3 py-2 me-3">B</span>
                    <span class="fs-4 fw-bolder text-gray-700">Meja Sedang</span>
                </div>
                <div class="waiting-list" id="list-B">
                    @forelse($waitingB as $w)
                        <div class="waiting-item">{{ $w->queue_number }}</div>
                    @empty
                        <span class="text-muted fs-6 fst-italic mt-3">Tidak ada antrian</span>
                    @endforelse
                </div>
            </div>

            <div class="waiting-box position-relative z-index-1">
                <div class="d-flex align-items-center border-bottom border-gray-200 pb-3">
                    <span class="badge badge-warning fs-5 px-3 py-2 me-3">C</span>
                    <span class="fs-4 fw-bolder text-gray-700">Meja Besar</span>
                </div>
                <div class="waiting-list" id="list-C">
                    @forelse($waitingC as $w)
                        <div class="waiting-item">{{ $w->queue_number }}</div>
                    @empty
                        <span class="text-muted fs-6 fst-italic mt-3">Tidak ada antrian</span>
                    @endforelse
                </div>
            </div>

            <img src="https://raw.githubusercontent.com/KaterinaLupacheva/undraw_illustrations/master/undraw_wait_in_line_o2aq.svg"
                class="illustration-bottom" alt="Waiting in line">
        </div>
    </div>

    <script src="{{ asset('assets/plugins/global/plugins.bundle.js') }}"></script>

    <script src="https://js.pusher.com/8.0.1/pusher.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/laravel-echo@1.15.3/dist/echo.iife.js"></script>

    <script>
        // 1. Definisikan Fungsi Speak yang aman
        function speak(text) {
            if (!window.isReady) return;
            try {
                window.speechSynthesis.cancel();
                let utterance = new SpeechSynthesisUtterance(text);
                utterance.lang = 'id-ID';
                utterance.rate = 0.85;
                utterance.pitch = 1;
                utterance.volume = 1;
                window.speechSynthesis.speak(utterance);
            } catch (e) {
                console.error("Gagal speak:", e);
            }
        }

        // 2. Jam Digital
        setInterval(() => {
            if (typeof moment !== 'undefined') {
                $('#live-clock').text(moment().format('HH:mm:ss'));
            }
        }, 1000);

        // 3. Inisialisasi Echo Dinamis (Pemisah agar tidak mematikan script lain)
        setTimeout(() => {
            try {
                if (typeof Echo !== 'undefined') {
                    // Deteksi Otomatis: Jika localhost pakai 8080, jika domain pakai 443
                    const isLocal = window.location.hostname === '127.0.0.1' || window.location.hostname === 'localhost';
                    
                    const echoClient = new Echo({
                        broadcaster: 'pusher',
                        key: "{{ config('services.reverb.key', 'error_key') }}",
                        cluster: "mt1",
                        wsHost: window.location.hostname,
                        wsPort: isLocal ? 8080 : 443,
                        wssPort: isLocal ? 8080 : 443,
                        forceTLS: isLocal ? false : true,
                        disableStats: true,
                        enabledTransports: ['ws', 'wss'],
                    });

                    echoClient.channel('public-display').listen('.call-event', (e) => {
                        console.log("🔊 Sinyal Panggilan:", e);
                        if (e.type === 'queue') {
                            const numberCalled = e.display_data.number;
                            const category = numberCalled.charAt(0);
                            $(`#current-${category}`).text(numberCalled);
                            $(`.current-card`).removeClass('called-active-A called-active-B called-active-C');
                            $(`#card-${category}`).addClass(`called-active-${category}`);
                            speak(e.text_to_speak);
                            setTimeout(() => {
                                $(`#card-${category}`).removeClass(`called-active-${category}`);
                                refreshWaitingList();
                            }, 5000);
                        } else if (e.type === 'food') {
                            speak(e.text_to_speak);
                        }
                    });

                    echoClient.channel('public-queue').listen('.new-queue', (e) => {
                        console.log("🆕 Antrian Baru dari Kiosk:", e);
                        refreshWaitingList();
                    });

                    // Fungsi pembantu agar panel kanan update otomatis
                    function refreshWaitingList() {
                        console.log("Memperbarui daftar tunggu...");
                        $.ajax({
                            url: window.location.href,
                            cache: false,
                            success: function(response) {
                                const newHtml = $(response).find('.right-panel').html();
                                if(newHtml) {
                                    $('.right-panel').html(newHtml);
                                    console.log("✅ Daftar tunggu diperbarui.");
                                }
                            }
                        });
                    }
                }
            } catch (err) {
                console.error("Echo Error:", err);
            }
        }, 500);

        // 4. Backup Auto Refresh (1 menit)
        setInterval(refreshWaitingList, 60000);
    </script>
</body>

</html>
