@extends('backend.layout.app')
@section('title', 'Master Bahan Makanan')
@section('content')
    <div id="kt_app_toolbar" class="app-toolbar py-3 py-lg-0">
        <div id="kt_app_toolbar_container" class="app-container container-xxl d-flex flex-stack">
            <div class="page-title d-flex flex-column justify-content-center me-3">
                <h1 class="page-heading d-flex text-gray-900 fw-bold fs-3 flex-column justify-content-center my-0">Manajemen Bahan Makanan</h1>
                <ul class="breadcrumb breadcrumb-separatorless fw-semibold fs-7 my-0 pt-1">
                    <li class="breadcrumb-item text-muted">Master Data</li>
                    <li class="breadcrumb-item"><span class="bullet bg-gray-500 w-5px h-2px"></span></li>
                    <li class="breadcrumb-item text-muted">Bahan Makanan</li>
                </ul>
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
                        <button type="button" class="btn btn-sm btn-primary" id="btn_tambah_data">
                            <i class="ki-outline ki-plus fs-2"></i> Tambah Bahan
                        </button>
                    </div>
                </div>
                <div class="card-body pt-0">
                    <table class="table align-middle table-row-dashed fs-6 gy-5" id="table-ingredients">
                        <thead>
                            <tr class="text-start text-gray-400 fw-bold fs-7 text-uppercase gs-0">
                                <th class="w-10px pe-2">No</th>
                                <th class="min-w-200px">Nama Bahan</th>
                                <th class="min-w-100px">Satuan</th>
                                <th class="min-w-100px">Min. Stok</th>
                                <th class="min-w-150px">Stok Saat Ini</th>
                                <th class="text-end min-w-100px">Aksi</th>
                            </tr>
                        </thead>
                        <tbody class="fw-semibold text-gray-600"></tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal Tambah/Edit -->
    <div class="modal fade" id="Modal_Data" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered mw-650px">
            <div class="modal-content">
                <div class="modal-header">
                    <h2 class="fw-bold" id="modal_title">Tambah Bahan</h2>
                    <div class="btn btn-icon btn-sm btn-active-icon-primary" data-bs-dismiss="modal">
                        <i class="ki-outline ki-cross fs-1"></i>
                    </div>
                </div>
                <div class="modal-body mx-5 mx-xl-15 my-7">
                    <form id="FormModalID" class="form">
                        @csrf
                        <input type="hidden" name="id" id="ingredient_id">
                        <div class="fv-row mb-7">
                            <label class="required fs-6 fw-semibold mb-2">Nama Bahan</label>
                            <input type="text" class="form-control" name="name" id="name" placeholder="Misal: Tepung Terigu" required />
                        </div>
                        <div class="row mb-7">
                            <div class="col-md-6 fv-row">
                                <label class="required fs-6 fw-semibold mb-2">Satuan</label>
                                <select name="unit" id="unit" class="form-select" data-control="select2" data-placeholder="Pilih Satuan">
                                    <option></option>
                                    <option value="gram">Gram (g)</option>
                                    <option value="kg">Kilogram (kg)</option>
                                    <option value="ml">Mililiter (ml)</option>
                                    <option value="liter">Liter (L)</option>
                                    <option value="pcs">Pcs / Butir</option>
                                    <option value="slice">Slice / Lembar</option>
                                    <option value="bungkus">Bungkus</option>
                                </select>
                            </div>
                            <div class="col-md-6 fv-row">
                                <label class="required fs-6 fw-semibold mb-2">Minimum Stok</label>
                                <input type="number" step="0.01" class="form-control" name="minimum_stock" id="minimum_stock" placeholder="Alert jika stok dibawah..." required />
                            </div>
                        </div>
                        <div class="text-center pt-10">
                            <button type="button" class="btn btn-light me-3" data-bs-dismiss="modal">Batal</button>
                            <button type="submit" class="btn btn-primary" id="btn-save">Simpan</button>
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
                var table = $('#table-ingredients').DataTable({
                    processing: true,
                    serverSide: true,
                    ajax: "{{ route('get-dataingredients') }}",
                    columns: [
                        {data: 'DT_RowIndex', name: 'DT_RowIndex', orderable: false, searchable: false},
                        {data: 'name', name: 'name'},
                        {data: 'unit', name: 'unit'},
                        {data: 'minimum_stock', name: 'minimum_stock'},
                        {data: 'stock_display', name: 'stock', searchable: false},
                        {data: 'action', name: 'action', orderable: false, searchable: false, className: 'text-end'}
                    ]
                });

                $('#search').on('keyup', function() {
                    table.search(this.value).draw();
                });

                $('#btn_tambah_data').click(function() {
                    $('#FormModalID')[0].reset();
                    $('#ingredient_id').val('');
                    $('#modal_title').text('Tambah Bahan Makanan');
                    $('#Modal_Data').modal('show');
                });

                $('body').on('click', '.btn-edit', function() {
                    let id = $(this).data('id');
                    $.get("{{ url('admin/ingredients') }}/" + id + "/edit", function(res) {
                        $('#ingredient_id').val(res.id);
                        $('#name').val(res.name);
                        $('#unit').val(res.unit).trigger('change');
                        $('#minimum_stock').val(res.minimum_stock);
                        $('#modal_title').text('Edit Bahan Makanan');
                        $('#Modal_Data').modal('show');
                    });
                });

                $('#FormModalID').on('submit', function(e) {
                    e.preventDefault();
                    let id = $('#ingredient_id').val();
                    let url = id ? "{{ url('admin/ingredients') }}/" + id : "{{ route('ingredients.store') }}";
                    let method = id ? "PUT" : "POST";

                    $.ajax({
                        url: url,
                        method: method,
                        data: $(this).serialize(),
                        success: function(res) {
                            $('#Modal_Data').modal('hide');
                            table.ajax.reload();
                            Swal.fire("Berhasil!", res.success, "success");
                        },
                        error: function() {
                            Swal.fire("Error", "Gagal menyimpan data", "error");
                        }
                    });
                });

                $('body').on('click', '.btn-delete', function() {
                    let id = $(this).data('id');
                    let name = $(this).data('name');
                    Swal.fire({
                        title: "Hapus Bahan?",
                        text: "Data '" + name + "' akan dihapus.",
                        icon: "warning",
                        showCancelButton: true,
                        confirmButtonText: "Ya, Hapus!"
                    }).then((result) => {
                        if (result.isConfirmed) {
                            $.ajax({
                                url: "{{ url('admin/ingredients') }}/" + id,
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
