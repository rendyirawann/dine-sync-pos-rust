@extends('backend.layout.app')
@section('title', 'Manajemen Shift Kasir')
@section('content')

    <div id="kt_app_content" class="app-content flex-column-fluid mt-5">
        <div id="kt_app_content_container" class="app-container container-xxl">

            @if (session('success'))
                <div class="alert alert-success d-flex align-items-center p-5 mb-10">
                    <i class="ki-outline ki-shield-tick fs-2hx text-success me-4"></i>
                    <div class="d-flex flex-column">
                        <h4 class="mb-1 text-success">Berhasil</h4><span>{{ session('success') }}</span>
                    </div>
                </div>
            @endif

            @if (session('error'))
                <div class="alert alert-danger d-flex align-items-center p-5 mb-10">
                    <i class="ki-outline ki-information-5 fs-2hx text-danger me-4"></i>
                    <div class="d-flex flex-column">
                        <h4 class="mb-1 text-danger">Gagal</h4><span>{{ session('error') }}</span>
                    </div>
                </div>
            @endif

            <div class="row g-5 g-xl-10">
                <div class="col-xl-5">
                    @if (!$currentShift)
                        <div class="card shadow-sm border-0">
                            <div class="card-body text-center p-10">
                                <i class="ki-outline ki-time fs-5x text-primary mb-5"></i>
                                <h2 class="fs-2x fw-bold text-gray-800 mb-2">Shift Belum Dibuka</h2>
                                <p class="text-gray-500 fs-5 mb-8">Anda harus membuka shift dan memasukkan modal kembalian
                                    sebelum dapat menggunakan mesin kasir.</p>

                                <form action="{{ route('shifts.open') }}" method="POST" id="formOpenShift">
                                    @csrf

                                    @if ($isFirstShiftOfDay)
                                        <div class="bg-light-primary rounded p-5 mb-6 text-start">
                                            <div class="d-flex align-items-center mb-3">
                                                <i class="ki-outline ki-sun fs-1 text-primary me-2"></i>
                                                <span class="fw-bold text-primary fs-5">Setup Harian (Shift Pertama)</span>
                                            </div>
                                            <p class="text-muted fs-7 mb-4">Karena Anda membuka shift pertama hari ini,
                                                mohon tentukan target dan budget harian.</p>

                                            <div class="mb-4">
                                                <label class="required fw-semibold fs-6 mb-1">Target Penjualan Hari Ini
                                                    (Rp)</label>
                                                <input type="number" name="target_penjualan"
                                                    class="form-control form-control-solid" placeholder="Contoh: 3000000"
                                                    min="0" required>
                                            </div>
                                            <div class="mb-4">
                                                <label class="required fw-semibold fs-6 mb-1">Daily Budget (Rp)</label>
                                                <input type="number" name="daily_budget"
                                                    class="form-control form-control-solid" placeholder="Contoh: 500000"
                                                    min="0" required>
                                            </div>
                                        </div>
                                    @endif
                                    <div class="text-start mb-6">
                                        <label class="required fw-semibold fs-5 mb-2">Modal Uang Kembalian Laci (Rp)</label>
                                        <input type="number" name="starting_cash"
                                            class="form-control form-control-lg form-control-solid text-center fs-3 fw-bold"
                                            placeholder="Contoh: 500000" min="0" required autofocus>
                                    </div>
                                    <button type="submit" class="btn btn-primary btn-lg w-100 fs-4 fw-bold">
                                        <i class="ki-outline ki-unlock fs-2 me-2"></i> Buka Shift Sekarang
                                    </button>
                                </form>
                            </div>
                        </div>
                    @else
                        <div class="card shadow-sm border-0">
                            <div class="card-header bg-light-primary pt-7 border-0">
                                <h3 class="card-title align-items-start flex-column">
                                    <span class="card-label fw-bold text-primary fs-3"><i
                                            class="ki-outline ki-security-user fs-2 text-primary me-2"></i> Shift Sedang
                                        Berjalan</span>
                                    <span class="text-primary mt-1 fw-semibold fs-7">Dimulai:
                                        {{ \Carbon\Carbon::parse($currentShift->start_time)->translatedFormat('d M Y, H:i') }}</span>
                                </h3>
                            </div>
                            <div class="card-body p-8">
                                <div class="d-flex flex-stack mb-5">
                                    <span class="text-gray-600 fs-5">Modal Awal Laci</span>
                                    <span class="text-gray-800 fw-bold fs-4">Rp
                                        {{ number_format($currentShift->starting_cash, 0, ',', '.') }}</span>
                                </div>
                                <div class="d-flex flex-stack mb-5">
                                    <span class="text-gray-600 fs-5">Total Penjualan Tunai (Masuk)</span>
                                    <span class="text-success fw-bold fs-4">+ Rp
                                        {{ number_format($cashSales, 0, ',', '.') }}</span>
                                </div>
                                <div class="separator separator-dashed my-5"></div>
                                <div class="d-flex flex-stack mb-8">
                                    <span class="text-gray-800 fw-bolder fs-4">Harapan Uang di Laci</span>
                                    <span class="text-primary fw-bolder fs-2qx">Rp
                                        {{ number_format($currentShift->starting_cash + $cashSales, 0, ',', '.') }}</span>
                                </div>

                                <form action="{{ route('shifts.close', $currentShift->id) }}" method="POST"
                                    id="formCloseShift">
                                    @csrf
                                    <div class="bg-light-warning rounded p-6 mb-6">
                                        <label class="required fw-bold fs-5 text-gray-800 mb-2">Uang Fisik Aktual di Laci
                                            (Rp)</label>
                                        <p class="text-muted fs-7 mb-4">Hitung uang fisik di laci kasir dan masukkan
                                            totalnya di bawah ini untuk menutup shift.</p>
                                        <input type="number" name="actual_cash"
                                            class="form-control form-control-lg text-center fs-2x fw-bold" placeholder="0"
                                            min="0" required>
                                    </div>
                                    <button type="button" onclick="confirmClose()"
                                        class="btn btn-danger btn-lg w-100 fs-4 fw-bold">
                                        <i class="ki-outline ki-lock-3 fs-2 me-2"></i> Tutup Shift
                                    </button>
                                </form>
                            </div>
                        </div>
                    @endif
                </div>

                <div class="col-xl-7">
                    <div class="card shadow-sm border-0 h-100">
                        <div class="card-header pt-7 border-0">
                            <h3 class="card-title fw-bold text-gray-800 fs-3">Riwayat Shift Anda</h3>
                        </div>
                        <div class="card-body pt-3">
                            <div class="table-responsive">
                                <table class="table align-middle table-row-dashed fs-6 gy-4">
                                    <thead>
                                        <tr class="text-start text-gray-400 fw-bold fs-7 text-uppercase gs-0">
                                            <th>Waktu Buka - Tutup</th>
                                            <th class="text-end">Modal</th>
                                            <th class="text-end">Aktual</th>
                                            <th class="text-end">Selisih</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @forelse($history as $hist)
                                            <tr>
                                                <td>
                                                    <span
                                                        class="d-block fw-bold text-gray-800">{{ \Carbon\Carbon::parse($hist->start_time)->format('d/m/Y H:i') }}</span>
                                                    <span class="d-block text-muted fs-8">s/d
                                                        {{ \Carbon\Carbon::parse($hist->end_time)->format('H:i') }}</span>
                                                </td>
                                                <td class="text-end">Rp
                                                    {{ number_format($hist->starting_cash, 0, ',', '.') }}</td>
                                                <td class="text-end fw-semibold">Rp
                                                    {{ number_format($hist->actual_cash, 0, ',', '.') }}</td>
                                                <td class="text-end">
                                                    @if ($hist->difference == 0)
                                                        <span class="badge badge-light-success">Pas (Rp 0)</span>
                                                    @elseif($hist->difference > 0)
                                                        <span class="badge badge-light-info">Lebih +Rp
                                                            {{ number_format($hist->difference, 0, ',', '.') }}</span>
                                                    @else
                                                        <span class="badge badge-light-danger">Kurang Rp
                                                            {{ number_format(abs($hist->difference), 0, ',', '.') }}</span>
                                                    @endif
                                                </td>
                                            </tr>
                                        @empty
                                            <tr>
                                                <td colspan="4" class="text-center text-muted py-5">Belum ada riwayat
                                                    shift.</td>
                                            </tr>
                                        @endforelse
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

        </div>
    </div>

    @push('scripts')
        <script>
            $(document).ready(function() {
                // Notifikasi SweetAlert untuk Success
                @if (session('success'))
                    Swal.fire({
                        icon: 'success',
                        title: 'Berhasil!',
                        text: '{{ session('success') }}',
                        confirmButtonColor: '#009ef7',
                        timer: 3000
                    });
                @endif

                // Notifikasi SweetAlert untuk Error
                @if (session('error'))
                    Swal.fire({
                        icon: 'error',
                        title: 'Oops!',
                        text: '{{ session('error') }}',
                        confirmButtonColor: '#f1416c'
                    });
                @endif

                // Animasi Loading & Spinner saat Buka Shift
                $('#formOpenShift').on('submit', function() {
                    let btn = $('#btn-open-shift');

                    // Nonaktifkan tombol dan ganti teksnya jadi spinner
                    btn.prop('disabled', true);
                    btn.html('<span class="spinner-border spinner-border-sm me-2"></span> Membuka Shift...');

                    // Munculkan Pop-up Loading
                    Swal.fire({
                        title: 'Memproses...',
                        text: 'Sedang mengatur target dan membuka laci kasir.',
                        allowOutsideClick: false,
                        didOpen: () => {
                            Swal.showLoading();
                        }
                    });
                });
            });

            // Logika Tutup Shift Lama
            function confirmClose() {
                Swal.fire({
                    title: "Yakin tutup shift?",
                    text: "Pastikan uang fisik yang dihitung sudah benar. Aksi ini tidak dapat dibatalkan.",
                    icon: "warning",
                    showCancelButton: true,
                    confirmButtonText: "Ya, Tutup Shift!",
                    cancelButtonText: "Batal",
                    customClass: {
                        confirmButton: "btn btn-danger",
                        cancelButton: "btn btn-secondary"
                    }
                }).then((result) => {
                    if (result.isConfirmed) {
                        Swal.fire({
                            title: 'Menutup Shift...',
                            allowOutsideClick: false,
                            didOpen: () => {
                                Swal.showLoading();
                            }
                        });
                        document.getElementById('formCloseShift').submit();
                    }
                });
            }
        </script>
    @endpush
@endsection
