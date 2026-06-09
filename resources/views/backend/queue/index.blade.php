@extends('backend.layout.app')
@section('title', 'Manajemen Antrian')
@section('content')

    <div class="app-content flex-column-fluid mt-5">
        <div class="app-container container-xxl">

            <div class="d-flex justify-content-between align-items-center mb-6">
                <h1 class="text-gray-900 fw-bold fs-2"><i class="ki-outline ki-people fs-1 me-2"></i> Daftar Antrian (Hari
                    Ini)</h1>
                <button class="btn btn-sm btn-light-primary" onclick="location.reload()">
                    <i class="ki-outline ki-arrows-circle fs-3"></i> Refresh
                </button>
            </div>

            <div class="card shadow-sm">
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table align-middle table-row-dashed fs-6 gy-5">
                            <thead>
                                <tr class="text-start text-gray-500 fw-bold fs-7 text-uppercase gs-0">
                                    <th>Waktu</th>
                                    <th>No. Antrian</th>
                                    <th>Pelanggan</th>
                                    <th>Pax</th>
                                    <th>Status</th>
                                    <th class="text-end">Aksi</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($queues as $q)
                                    <tr>
                                        <td>{{ $q->created_at->format('H:i') }}</td>
                                        <td><span class="badge badge-light-primary fs-6">{{ $q->queue_number }}</span></td>
                                        <td class="fw-bolder">{{ $q->customer_name }}</td>
                                        <td>{{ $q->pax }} Orang</td>
                                        <td>
                                            @if ($q->status == 'waiting')
                                                <span class="badge badge-warning">Menunggu</span>
                                            @elseif($q->status == 'called')
                                                <span class="badge badge-primary">Dipanggil</span>
                                            @elseif($q->status == 'seated')
                                                <span class="badge badge-success">Sudah Duduk</span>
                                            @endif
                                        </td>
                                        <td class="text-end">
                                            @if ($q->status != 'seated')
                                                <button class="btn btn-sm btn-icon btn-primary me-2 btn-call"
                                                    data-id="{{ $q->id }}" title="Panggil ke TV">
                                                    <i class="ki-outline ki-notification-on fs-3"></i>
                                                </button>
                                                <button class="btn btn-sm btn-light-success fw-bold btn-seat"
                                                    data-id="{{ $q->id }}">Selesai / Duduk</button>
                                            @else
                                                <i class="ki-outline ki-check-circle text-success fs-2"></i>
                                            @endif
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

        </div>
    </div>

    @push('scripts')
        <script src="{{ asset('assets/js/pusher.min.js') }}"></script>
        <script src="{{ asset('assets/js/echo.iife.js') }}"></script>

        <script>
            // ==========================================
            // KONEKSI WEBSOCKET (REVERB)
            // ==========================================
            // 🔥 PERBAIKAN: Gunakan const echoClient agar tidak bentrok dengan bawaan Metronic
            const echoClient = new Echo({
                broadcaster: 'pusher',
                key: "{{ env('REVERB_APP_KEY') }}",
                cluster: "mt1", // 🔥 WAJIB DITAMBAHKAN UNTUK PUSHER.JS
                wsHost: window.location.hostname,
                wsPort: {{ env('REVERB_PORT', 8080) }},
                wssPort: {{ env('REVERB_PORT', 8080) }},
                forceTLS: false,
                disableStats: true,
                enabledTransports: ['ws', 'wss'],
            });

            // 1. DENGARKAN JIKA ADA ANTRIAN BARU DARI KIOSK
            echoClient.channel('public-queue')
                .listen('.new-queue', (e) => {
                    toastr.info("Ada antrian baru masuk dari Kiosk depan!", "Antrian Baru");
                    setTimeout(() => {
                        location.reload();
                    }, 1500);
                });

            // 2. DENGARKAN JIKA ADA PANGGILAN
            echoClient.channel('public-display')
                .listen('.call-event', (e) => {
                    setTimeout(() => {
                        location.reload();
                    }, 2000);
                });

            // ==========================================
            // TIMER COOLDOWN PANGGILAN SUARA
            // ==========================================
            let globalCooldown = {{ $cooldownLeft ?? 0 }};

            function startCooldownTimer(seconds) {
                let timeLeft = seconds;
                $('.btn-call').prop('disabled', true);

                $('.btn-call').each(function() {
                    $(this).html(`<span class="fw-bolder text-danger fs-6">${timeLeft}s</span>`);
                });

                let timerInterval = setInterval(() => {
                    timeLeft--;
                    $('.btn-call').html(`<span class="fw-bolder text-danger fs-6">${timeLeft}s</span>`);

                    if (timeLeft <= 0) {
                        clearInterval(timerInterval);
                        $('.btn-call').prop('disabled', false).html(
                            '<i class="ki-outline ki-notification-on fs-3"></i>');
                    }
                }, 1000);
            }

            if (globalCooldown > 0) {
                startCooldownTimer(globalCooldown);
            }

            // ==========================================
            // AKSI TOMBOL
            // ==========================================
            $('.btn-call').click(function() {
                let btn = $(this);
                let id = btn.data('id');

                btn.prop('disabled', true).html('<span class="spinner-border spinner-border-sm"></span>');

                $.ajax({
                    url: "{{ route('queues.call') }}",
                    method: "POST",
                    data: {
                        _token: '{{ csrf_token() }}',
                        id: id
                    },
                    success: function(res) {
                        toastr.success(res.message, "Memanggil...");
                        startCooldownTimer(15);
                    },
                    error: function(xhr) {
                        btn.prop('disabled', false).html(
                            '<i class="ki-outline ki-notification-on fs-3"></i>');
                        if (xhr.status === 429) {
                            Swal.fire('Sabar Bos!', xhr.responseJSON.message, 'warning');
                        } else {
                            Swal.fire('Error', 'Terjadi kesalahan sistem', 'error');
                        }
                    }
                });
            });

            $('.btn-seat').click(function() {
                let id = $(this).data('id');
                $.post(`{{ url('admin') }}/queues/status/${id}`, {
                    _token: '{{ csrf_token() }}',
                    status: 'seated'
                }, function() {
                    location.reload();
                });
            });
        </script>
    @endpush
@endsection
