@extends('backend.layout.app')
@section('title', 'Pengaturan Toko & Pajak')
@section('content')

    <div id="kt_app_content" class="app-content flex-column-fluid mt-5">
        <div id="kt_app_content_container" class="app-container container-xxl">

            <div class="card shadow-sm">
                <div class="card-header">
                    <h3 class="card-title fw-bold"><i class="ki-outline ki-setting-2 fs-2 me-2"></i> Konfigurasi Sistem</h3>
                </div>

                <form action="{{ route('settings.update') }}" method="POST" id="form-settings">
                    @csrf
                    <div class="card-body">

                        <div class="row mb-6">
                            <label class="col-lg-3 col-form-label required fw-semibold fs-6">Nama Toko</label>
                            <div class="col-lg-9">
                                <input type="text" name="store_name" class="form-control form-control-solid"
                                    value="{{ $setting->store_name }}" required>
                            </div>
                        </div>

                        <div class="row mb-6">
                            <label class="col-lg-3 col-form-label fw-semibold fs-6">Alamat Toko</label>
                            <div class="col-lg-9">
                                <textarea name="address" class="form-control form-control-solid" rows="3">{{ $setting->address }}</textarea>
                            </div>
                        </div>

                        <div class="row mb-6">
                            <label class="col-lg-3 col-form-label fw-semibold fs-6">No. Telepon / WA</label>
                            <div class="col-lg-9">
                                <input type="text" name="phone" class="form-control form-control-solid"
                                    value="{{ $setting->phone }}">
                            </div>
                        </div>

                        <div class="row mb-6">
                            <label class="col-lg-3 col-form-label required fw-semibold fs-6">Pajak Restoran (PB1)</label>
                            <div class="col-lg-9">
                                <div class="input-group input-group-solid">
                                    <input type="number" name="tax_rate" class="form-control form-control-solid"
                                        value="{{ $setting->tax_rate }}" min="0" max="100" required>
                                    <span class="input-group-text">%</span>
                                </div>
                                <div class="form-text">Masukkan angka 0 jika toko tidak membebankan pajak ke pelanggan.
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="card-footer d-flex justify-content-end py-6 px-9">
                        <button type="submit" class="btn btn-primary" id="btn-save">Simpan Pengaturan</button>
                    </div>
                </form>
            </div>

        </div>
    </div>

    @push('scripts')
        <script>
            $(document).ready(function() {
                // 3. Tampilkan SweetAlert Success jika ada pesan 'success' dari Controller
                @if (session('success'))
                    Swal.fire({
                        icon: 'success',
                        title: 'Berhasil!',
                        text: '{{ session('success') }}',
                        confirmButtonColor: '#009ef7',
                        timer: 3000 // Otomatis tertutup dalam 3 detik
                    });
                @endif

                // 4. Mencegat proses Submit Form untuk memunculkan loading
                $('#form-settings').on('submit', function() {
                    let btn = $('#btn-save');

                    // Matikan tombol agar tidak di-klik double
                    btn.prop('disabled', true);

                    // Ganti teks tombol dengan spinner bawaan Bootstrap
                    btn.html(
                        '<span class="spinner-border spinner-border-sm me-2 align-middle"></span> Memproses...'
                        );

                    // Munculkan Pop-up Loading SweetAlert
                    Swal.fire({
                        title: 'Menyimpan...',
                        text: 'Mohon tunggu sebentar.',
                        allowOutsideClick: false,
                        didOpen: () => {
                            Swal.showLoading();
                        }
                    });
                });
            });
        </script>
    @endpush
@endsection
