@extends('backend.layout.app')
@section('title', 'Stock Opname')
@section('content')
    <div id="kt_app_toolbar" class="app-toolbar py-3 py-lg-0">
        <div id="kt_app_toolbar_container" class="app-container container-xxl d-flex flex-stack">
            <div class="page-title d-flex flex-column justify-content-center me-3">
                <h1 class="page-heading d-flex text-gray-900 fw-bold fs-3 flex-column justify-content-center my-0">Stock Opname (Hitung Fisik)</h1>
                <span class="text-muted fs-7">Bandingkan stok sistem dengan stok fisik di dapur</span>
            </div>
        </div>
    </div>

    <div id="kt_app_content" class="app-content flex-column-fluid">
        <div id="kt_app_content_container" class="app-container container-xxl">
            <div class="card card-flush">
                <div class="card-header align-items-center py-5 gap-2 gap-md-5">
                    <div class="card-title">
                        <div class="d-flex align-items-center position-relative my-1">
                            <i class="ki-outline ki-magnifier fs-3 position-absolute ms-4"></i>
                            <input type="text" id="search" class="form-control w-250px ps-12" placeholder="Cari Bahan..." />
                        </div>
                    </div>
                    <div class="card-toolbar">
                        <button type="button" class="btn btn-primary" id="btn_save_all">
                            <i class="ki-outline ki-check-square fs-2"></i> Simpan Hasil Opname
                        </button>
                    </div>
                </div>
                <div class="card-body pt-0">
                    <ul class="nav nav-stretch nav-line-tabs nav-line-tabs-2x border-transparent fs-5 fw-bold mb-5">
                        <li class="nav-item">
                            <a class="nav-link text-active-primary py-5 active" data-bs-toggle="tab" href="#tab_opname">Form Opname Baru</a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link text-active-primary py-5" data-bs-toggle="tab" href="#tab_history">Riwayat Opname</a>
                        </li>
                    </ul>

                    <div class="tab-content">
                        <div class="tab-pane fade show active" id="tab_opname" role="tabpanel">
                            <div class="alert alert-dismissible bg-light-warning d-flex flex-column flex-sm-row p-5 mb-10">
                                <i class="ki-outline ki-information-5 fs-2hx text-warning me-4 mb-5 mb-sm-0"></i>
                                <div class="d-flex flex-column pe-0 pe-sm-10">
                                    <h4 class="fw-bold">Penting!</h4>
                                    <span>Input jumlah stok fisik yang benar-benar ada di dapur. Sistem akan menghitung selisih (Loss/Waste) secara otomatis dan menyesuaikan batch stok via FEFO (First Expired First Out).</span>
                                </div>
                            </div>

                            <table class="table align-middle table-row-dashed fs-6 gy-5" id="table-opname">
                                <thead>
                                    <tr class="text-start text-gray-400 fw-bold fs-7 text-uppercase gs-0">
                                        <th class="w-10px pe-2">No</th>
                                        <th class="min-w-200px">Nama Bahan</th>
                                        <th class="min-w-150px">Stok Sistem</th>
                                        <th class="min-w-150px">Stok Fisik</th>
                                        <th class="min-w-100px text-end">Selisih (Loss)</th>
                                    </tr>
                                </thead>
                                <tbody class="fw-semibold text-gray-600"></tbody>
                            </table>
                        </div>

                        <div class="tab-pane fade" id="tab_history" role="tabpanel">
                            <table class="table align-middle table-row-dashed fs-6 gy-5" id="table-history">
                                <thead>
                                    <tr class="text-start text-gray-400 fw-bold fs-7 text-uppercase gs-0">
                                        <th class="w-10px pe-2">No</th>
                                        <th class="min-w-150px">Tanggal</th>
                                        <th class="min-w-150px">Petugas</th>
                                        <th class="min-w-200px">Keterangan</th>
                                        <th class="text-end min-w-100px">Aksi</th>
                                    </tr>
                                </thead>
                                <tbody class="fw-semibold text-gray-600"></tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    @push('stylesheets')
        <meta name="csrf-token" content="{{ csrf_token() }}" />
        <link rel="stylesheet" href="{{ URL::to('assets/plugins/custom/datatables/datatables.bundle.css') }}" />
    @endpush

    @push('scripts')
        <script src="{{ URL::to('assets/plugins/custom/datatables/datatables.bundle.js') }}"></script>
        <script>
            $(document).ready(function() {
                var table = $('#table-opname').DataTable({
                    processing: true,
                    serverSide: true,
                    paging: false, // Menghindari kehilangan input saat ganti halaman
                    ajax: "{{ route('stock-opname.get-data') }}",
                    columns: [
                        {data: 'DT_RowIndex', name: 'DT_RowIndex', orderable: false, searchable: false},
                        {data: 'name', name: 'name'},
                        {data: 'system_stock', name: 'id'},
                        {data: 'input_physical', name: 'id', orderable: false, searchable: false},
                        {data: 'difference', name: 'id', className: 'text-end', orderable: false, searchable: false}
                    ]
                });

                var tableHistory = $('#table-history').DataTable({
                    processing: true,
                    serverSide: true,
                    ajax: "{{ route('stock-opname.history-data') }}",
                    columns: [
                        {data: 'DT_RowIndex', name: 'DT_RowIndex', orderable: false, searchable: false},
                        {data: 'date_format', name: 'date'},
                        {data: 'user_name', name: 'user.name'},
                        {data: 'notes', name: 'notes'},
                        {data: 'action', name: 'action', orderable: false, searchable: false, className: 'text-end'}
                    ]
                });

                $('#search').on('keyup', function() {
                    table.search(this.value).draw();
                });

                // Hitung selisih real-time
                $('body').on('input', '.physical-input', function() {
                    let id = $(this).data('id');
                    let system = parseFloat($(this).data('system'));
                    let physical = parseFloat($(this).val());
                    
                    if (!isNaN(physical)) {
                        let diff = physical - system;
                        let color = diff < 0 ? 'text-danger' : (diff > 0 ? 'text-success' : 'text-gray-600');
                        let sign = diff > 0 ? '+' : '';
                        $(`#diff-${id}`).text(sign + diff.toFixed(2)).attr('class', 'diff-display ' + color);
                    } else {
                        $(`#diff-${id}`).text('0').attr('class', 'diff-display text-gray-600');
                    }
                });

                $('#btn_save_all').click(function() {
                    let adjustments = [];
                    $('.physical-input').each(function() {
                        let val = $(this).val();
                        if (val !== '') {
                            adjustments.push({
                                id: $(this).data('id'),
                                physical_qty: val
                            });
                        }
                    });

                    if (adjustments.length === 0) {
                        Swal.fire("Peringatan", "Isi minimal satu data stok fisik.", "warning");
                        return;
                    }

                    Swal.fire({
                        title: "Simpan Stock Opname?",
                        text: "Stok sistem akan disesuaikan dengan stok fisik yang Anda input.",
                        icon: "question",
                        showCancelButton: true,
                        confirmButtonText: "Ya, Simpan!"
                    }).then((result) => {
                        if (result.isConfirmed) {
                            let btn = $('#btn_save_all');
                            let originalText = btn.html();
                            btn.prop('disabled', true).html('<span class="spinner-border spinner-border-sm me-2"></span> Menyimpan...');

                            $.ajax({
                                url: "{{ route('stock-opname.store') }}",
                                method: 'POST',
                                data: {
                                    _token: $('meta[name="csrf-token"]').attr('content'),
                                    adjustments: adjustments
                                },
                                success: function(res) {
                                    table.ajax.reload();
                                    tableHistory.ajax.reload();
                                    Swal.fire("Berhasil!", res.success, "success");
                                    btn.prop('disabled', false).html(originalText);
                                },
                                error: function(xhr) {
                                    Swal.fire("Error", xhr.responseJSON.error || "Gagal menyimpan data", "error");
                                    btn.prop('disabled', false).html(originalText);
                                }
                            });
                        }
                    });
                });
            });
        </script>
    @endpush
@endsection
