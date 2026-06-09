@extends('backend.layout.app')
@section('title', 'Input Stock Opname Baru')
@section('content')

    <div id="kt_app_content" class="app-content flex-column-fluid mt-5">
        <div id="kt_app_content_container" class="app-container container-xxl">
            <form id="form-opname">
                @csrf
                <div class="card card-flush mb-8">
                    <div class="card-body row p-8">
                        <div class="col-md-4">
                            <label class="required fs-6 fw-semibold mb-2">Tanggal Opname</label>
                            <input type="date" class="form-control" name="date" value="{{ date('Y-m-d') }}" required />
                        </div>
                        <div class="col-md-8">
                            <label class="fs-6 fw-semibold mb-2">Catatan Ekstra</label>
                            <input type="text" class="form-control" name="notes"
                                placeholder="Contoh: Audit Akhir Bulan Maret" />
                        </div>
                    </div>
                </div>

                <div class="card card-flush">
                    <div class="card-header pt-7">
                        <h3 class="card-title fw-bold">Input Hasil Perhitungan Fisik</h3>
                    </div>
                    <div class="card-body pt-0">
                        <div class="alert alert-info d-flex align-items-center p-5 mb-7">
                            <i class="ki-outline ki-information-5 fs-2hx text-info me-4"></i>
                            <div class="d-flex flex-column">
                                <span>Sistem hanya akan memproses dan melakukan penyesuaian jika kolom <b>Fisik Aktual</b>
                                    berbeda dengan <b>Stok Sistem</b>. Jika jumlahnya sama, baris tersebut akan
                                    diabaikan.</span>
                            </div>
                        </div>

                        <table class="table table-bordered table-striped align-middle gs-0 gy-4">
                            <thead class="bg-dark text-white">
                                <tr class="fw-bold fs-6">
                                    <th class="ps-3 w-50px">No</th>
                                    <th>Produk & Varian</th>
                                    <th class="text-center w-150px">Stok Komputer</th>
                                    <th class="text-center w-200px">Fisik Aktual (Gudang)</th>
                                    <th class="text-center w-150px">Selisih</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($variants as $var)
                                    <tr>
                                        <td class="ps-3 text-center">{{ $loop->iteration }}</td>
                                        <td>
                                            <span class="fw-bold d-block">{{ $var->product->brand }}
                                                {{ $var->product->model_name }}</span>
                                            <span class="text-muted fs-8">Sz: {{ $var->size }} | {{ $var->color }} |
                                                SKU: {{ $var->sku }}</span>
                                        </td>
                                        <td class="text-center fs-4 fw-bolder bg-light">
                                            {{ $var->stock }}
                                            <input type="hidden" name="items[{{ $var->id }}][system]"
                                                class="system-qty" value="{{ $var->stock }}">
                                        </td>
                                        <td>
                                            <input type="number" name="items[{{ $var->id }}][actual]"
                                                class="form-control form-control-solid text-center fw-bold actual-qty"
                                                value="{{ $var->stock }}" min="0" required
                                                onfocus="this.select()">
                                        </td>
                                        <td class="text-center fw-bolder fs-4 diff-text">0</td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>

                        <div class="text-end pt-10">
                            <a href="{{ route('stock-opname.index') }}" class="btn btn-light me-3">Batal</a>
                            <button type="submit" class="btn btn-primary" id="btn-submit">Selesaikan Opname & Sesuaikan
                                Stok!</button>
                        </div>
                    </div>
                </div>
            </form>
        </div>
    </div>

    @push('scripts')
        <script>
            $('.actual-qty').on('input', function() {
                let row = $(this).closest('tr');
                let sys = parseInt(row.find('.system-qty').val());
                let act = parseInt($(this).val()) || 0;
                let diff = act - sys;

                let diffCell = row.find('.diff-text');
                if (diff > 0) {
                    diffCell.html(`<span class="text-success">+${diff}</span>`);
                } else if (diff < 0) {
                    diffCell.html(`<span class="text-danger">${diff}</span>`);
                } else {
                    diffCell.html(`<span class="text-gray-500">0</span>`);
                }
            });

            $('#form-opname').on('submit', function(e) {
                e.preventDefault();
                Swal.fire({
                    title: "Yakin sesuaikan stok?",
                    text: "Aksi ini akan mengubah stok fisik komputer sesuai dengan input Anda secara permanen.",
                    icon: "warning",
                    showCancelButton: true,
                    confirmButtonText: "Ya, Terapkan!",
                    customClass: {
                        confirmButton: "btn btn-primary",
                        cancelButton: "btn btn-secondary"
                    }
                }).then((result) => {
                    if (result.isConfirmed) {
                        $('#btn-submit').prop('disabled', true).text('Memproses...');
                        $.ajax({
                            url: "{{ route('stock-opname.store') }}",
                            method: "POST",
                            data: $(this).serialize(),
                            success: function(res) {
                                Swal.fire("Selesai!", res.success, "success").then(() => window
                                    .location.href = "{{ route('stock-opname.index') }}");
                            },
                            error: function(err) {
                                Swal.fire("Gagal", "Terjadi kesalahan sistem.", "error");
                                $('#btn-submit').prop('disabled', false).text(
                                    'Selesaikan Opname & Sesuaikan Stok!');
                            }
                        });
                    }
                });
            });
        </script>
    @endpush
@endsection
