@extends('backend.layout.app')
@section('title', 'Input Stok FIFO')
@section('content')
    <div id="kt_app_toolbar" class="app-toolbar py-3 py-lg-0">
        <div id="kt_app_toolbar_container" class="app-container container-xxl d-flex flex-stack">
            <div class="page-title d-flex flex-column justify-content-center me-3">
                <h1 class="page-heading d-flex text-gray-900 fw-bold fs-3 flex-column justify-content-center my-0">Manajemen Stok (Masuk)</h1>
                <span class="text-muted fs-7">Input stok bahan makanan dengan sistem FIFO</span>
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
                            <input type="text" id="search" class="form-control w-250px ps-12" placeholder="Cari Batch..." />
                        </div>
                    </div>
                    <div class="card-toolbar">
                        <button type="button" class="btn btn-sm btn-primary" id="btn_tambah_data">
                            <i class="ki-outline ki-plus fs-2"></i> Input Stok Baru
                        </button>
                    </div>
                </div>
                <div class="card-body pt-0">
                    <table class="table align-middle table-row-dashed fs-6 gy-5" id="table-stocks">
                        <thead>
                            <tr class="text-start text-gray-400 fw-bold fs-7 text-uppercase gs-0">
                                <th class="w-10px pe-2">No</th>
                                <th class="min-w-150px">Nama Bahan</th>
                                <th class="min-w-150px">Supplier</th>
                                <th class="min-w-100px">Sisa / Total</th>
                                <th class="min-w-100px">Total Belanja (Satuan)</th>
                                <th class="min-w-100px">Tgl Masuk</th>
                                <th class="text-end min-w-100px">Aksi</th>
                            </tr>
                        </thead>
                        <tbody class="fw-semibold text-gray-600"></tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <div class="modal fade" id="Modal_Data" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered mw-800px">
            <div class="modal-content">
                <div class="modal-header">
                    <h2 class="fw-bold">Input Stok Bahan Makanan</h2>
                    <div class="btn btn-icon btn-sm btn-active-icon-primary" data-bs-dismiss="modal">
                        <i class="ki-outline ki-cross fs-1"></i>
                    </div>
                </div>
                <div class="modal-body mx-5 mx-xl-15 my-7">
                    <form id="FormModalID" class="form">
                        @csrf
                        <div class="fv-row mb-7">
                            <label class="required fs-6 fw-semibold mb-2">Pilih Bahan Makanan</label>
                            <select name="ingredient_id" id="ingredient_id" class="form-select" data-control="select2" data-placeholder="Cari Bahan..." data-dropdown-parent="#Modal_Data">
                                <option></option>
                                @foreach ($ingredients as $ing)
                                    <option value="{{ $ing->id }}">{{ $ing->name }} ({{ $ing->unit }})</option>
                                @endforeach
                            </select>
                        </div>

                        <div class="row mb-7">
                            <div class="col-md-6 fv-row">
                                <label class="required fs-6 fw-semibold mb-2">Jumlah Masuk</label>
                                <input type="number" step="0.01" class="form-control" name="initial_quantity" placeholder="Misal: 10" required />
                            </div>
                            <div class="col-md-6 fv-row">
                                <label class="required fs-6 fw-semibold mb-2">Total Harga Belanja</label>
                                <input type="number" class="form-control" name="buy_price" placeholder="Misal: 75000" required />
                                <small class="text-muted">Input total harga untuk seluruh jumlah masuk.</small>
                            </div>
                        </div>

                        <div class="fv-row mb-7">
                            <label class="fs-6 fw-semibold mb-2">Pilih Supplier (Opsional)</label>
                            <select name="supplier_id" id="supplier_id" class="form-select" data-control="select2" data-placeholder="Manual / Pilih Supplier..." data-dropdown-parent="#Modal_Data">
                                <option value="">Tanpa Supplier (Input Manual)</option>
                                @foreach ($suppliers as $sup)
                                    <option value="{{ $sup->id }}">{{ $sup->name }}</option>
                                @endforeach
                            </select>
                        </div>

                        <div class="row mb-7">
                            <div class="col-md-6 fv-row">
                                <label class="required fs-6 fw-semibold mb-2">Tanggal Masuk</label>
                                <input type="date" class="form-control" name="entry_date" value="{{ date('Y-m-d') }}" required />
                            </div>
                            <div class="col-md-6 fv-row">
                                <label class="fs-6 fw-semibold mb-2">Tanggal Kadaluarsa</label>
                                <input type="date" class="form-control" name="expiry_date" />
                            </div>
                        </div>

                        <div class="text-center pt-10">
                            <button type="button" class="btn btn-light me-3" data-bs-dismiss="modal">Batal</button>
                            <button type="submit" class="btn btn-primary" id="btn-save">Simpan Stok</button>
                        </div>
                    </form>
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
                var table = $('#table-stocks').DataTable({
                    processing: true,
                    serverSide: true,
                    ajax: "{{ route('get-datastocks') }}",
                    columns: [
                        {data: 'DT_RowIndex', name: 'DT_RowIndex', orderable: false, searchable: false},
                        {data: 'ingredient_name', name: 'ingredient.name'},
                        {data: 'supplier_name', name: 'supplier.name'},
                        {data: 'quantity_display', name: 'remaining_quantity'},
                        {data: 'price_format', name: 'buy_price'},
                        {data: 'entry_date', name: 'entry_date'},
                        {data: 'action', name: 'action', orderable: false, searchable: false, className: 'text-end'}
                    ]
                });

                $('#btn_tambah_data').click(function() {
                    $('#FormModalID')[0].reset();
                    $('#ingredient_id').val(null).trigger('change');
                    $('#supplier_id').val(null).trigger('change');
                    $('#Modal_Data').modal('show');
                });

                $('#FormModalID').on('submit', function(e) {
                    e.preventDefault();
                    let btn = $('#btn-save');
                    let originalText = btn.html();
                    btn.prop('disabled', true).html('<span class="spinner-border spinner-border-sm me-2"></span> Menyimpan...');

                    $.ajax({
                        url: "{{ route('stocks.store') }}",
                        method: 'POST',
                        data: $(this).serialize(),
                        success: function(res) {
                            $('#Modal_Data').modal('hide');
                            table.ajax.reload();
                            Swal.fire("Berhasil!", res.success, "success");
                            btn.prop('disabled', false).html(originalText);
                        },
                        error: function() {
                            Swal.fire("Error", "Gagal menyimpan stok", "error");
                            btn.prop('disabled', false).html(originalText);
                        }
                    });
                });

                $('body').on('click', '.btn-delete', function() {
                    let id = $(this).data('id');
                    Swal.fire({
                        title: "Hapus Batch Stok?",
                        text: "Data akan dihapus permanen.",
                        icon: "warning",
                        showCancelButton: true,
                        confirmButtonText: "Ya, Hapus!"
                    }).then((result) => {
                        if (result.isConfirmed) {
                            $.ajax({
                                url: "{{ url('admin/stocks') }}/" + id,
                                type: "DELETE",
                                data: {_token: $('meta[name="csrf-token"]').attr('content')},
                                success: function(res) {
                                    table.ajax.reload();
                                    Swal.fire("Terhapus!", res.success, "success");
                                }
                            });
                        }
                    });
                });
            });
        </script>
    @endpush
@endsection
