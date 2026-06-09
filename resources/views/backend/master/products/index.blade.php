@extends('backend.layout.app')
@section('title', 'Master Produk')
@section('content')
    <div id="kt_app_toolbar" class="app-toolbar py-3 py-lg-0">
        <div id="kt_app_toolbar_container" class="app-container container-xxl d-flex flex-stack">
            <div class="page-title d-flex flex-column justify-content-center me-3">
                <h1 class="page-heading d-flex text-gray-900 fw-bold fs-3 flex-column justify-content-center my-0">Data
                    Produk (Induk)</h1>
                <ul class="breadcrumb breadcrumb-separatorless fw-semibold fs-7 my-0 pt-1">
                    <li class="breadcrumb-item text-gray-700 fw-bold lh-1"><a href="{{ route('dashboard') }}"
                            class="text-white text-hover-primary"><i class="ki-outline ki-home text-gray-700 fs-6"></i></a>
                    </li>
                    <li class="breadcrumb-item"><i class="ki-outline ki-right fs-5 text-gray-700 mx-n1"></i></li>
                    <li class="breadcrumb-item text-gray-700 fw-bold lh-1">Master Data</li>
                    <li class="breadcrumb-item"><i class="ki-outline ki-right fs-5 text-gray-700 mx-n1"></i></li>
                    <li class="breadcrumb-item text-gray-900">Produk</li>
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
                            <input type="text" id="search" class="form-control w-250px ps-12"
                                placeholder="Cari produk/merek..." />
                        </div>
                    </div>
                    <div class="card-toolbar">
                        <button type="button" class="btn btn-sm btn-primary" id="btn_tambah_data">
                            <i class="ki-outline ki-plus fs-2"></i> Tambah Produk Induk
                        </button>
                    </div>
                </div>
                <div class="card-body pt-0">
                    <table class="table align-middle table-row-dashed fs-6 gy-5 table-product" id="table-product">
                        <thead>
                            <tr class="text-start text-gray-400 fw-bold fs-7 text-uppercase gs-0">
                                <th class="w-10px pe-2">No</th>
                                <th class="min-w-200px">Merek & Model</th>
                                <th class="min-w-150px">Kategori</th>
                                <th class="min-w-100px">Total Stok</th>
                                <th class="text-end min-w-100px">Aksi</th>
                            </tr>
                        </thead>
                        <tbody class="fw-semibold text-gray-600"></tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <div class="modal fade" id="Modal_Tambah_Data" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered mw-650px">
            <div class="modal-content">
                <div class="modal-header">
                    <h2 class="fw-bold">Tambah Produk Induk</h2>
                    <div class="btn btn-icon btn-sm btn-active-icon-primary" data-bs-dismiss="modal"><i
                            class="ki-outline ki-cross fs-1"></i></div>
                </div>
                <div class="modal-body scroll-y mx-5 mx-xl-15 my-7">
                    <form id="FormTambahModalID" class="form">
                        @csrf

                        <div class="row mb-7">
                            <div class="col-md-12 fv-row">
                                <label class="required fs-6 fw-semibold mb-2">Kategori</label>
                                <select name="category_id" class="form-select form-select-solid" data-control="select2"
                                    data-placeholder="Pilih Kategori" data-dropdown-parent="#Modal_Tambah_Data">
                                    <option></option>
                                    @foreach ($categories as $cat)
                                        <option value="{{ $cat->id }}">{{ $cat->name }}</option>
                                    @endforeach
                                </select>
                                <span class="text-danger error-text category_id_error_add mt-2"></span>
                            </div>
                        </div>

                        <div class="row mb-7">
                            <div class="col-md-6 fv-row">
                                <label class="required fs-6 fw-semibold mb-2">Merek (Brand)</label>
                                <input type="text" class="form-control" name="brand"
                                    placeholder="Contoh: Nike, Adidas" />
                                <span class="text-danger error-text brand_error_add mt-2"></span>
                            </div>
                            <div class="col-md-6 fv-row">
                                <label class="required fs-6 fw-semibold mb-2">Nama Model</label>
                                <input type="text" class="form-control" name="model_name"
                                    placeholder="Contoh: Air Force 1" />
                                <span class="text-danger error-text model_name_error_add mt-2"></span>
                            </div>
                        </div>

                        <div class="fv-row mb-7">
                            <label class="fs-6 fw-semibold mb-2">Deskripsi Produk</label>
                            <textarea class="form-control" name="description" rows="3" placeholder="Masukkan deskripsi singkat produk"></textarea>
                            <span class="text-danger error-text description_error_add mt-2"></span>
                        </div>

                        <div class="text-center pt-15">
                            <button type="reset" class="btn btn-light me-3" data-bs-dismiss="modal">Batal</button>
                            <button type="submit" class="btn btn-primary" id="btn-add-data">
                                <span class="indicator-label">Simpan</span>
                                <span class="indicator-progress" style="display: none;">Harap tunggu... <span
                                        class="spinner-border spinner-border-sm align-middle ms-2"></span></span>
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <div class="modal fade" id="Modal_Edit_Data" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered mw-650px">
            <div class="modal-content">
                <div class="modal-header">
                    <h2 class="fw-bold">Edit Produk Induk</h2>
                    <div class="btn btn-icon btn-sm btn-active-icon-primary" data-bs-dismiss="modal"><i
                            class="ki-outline ki-cross fs-1"></i></div>
                </div>
                <div class="modal-body scroll-y mx-5 mx-xl-15 my-7">
                    <form id="FormEditModalID" class="form">
                        @csrf @method('PUT')
                        <div id="EditRowModalBody"></div>
                        <div class="text-center pt-15">
                            <button type="button" class="btn btn-light me-3" data-bs-dismiss="modal">Batal</button>
                            <button type="submit" class="btn btn-primary" id="btn-edit-data">
                                <span class="indicator-label">Simpan Perubahan</span>
                                <span class="indicator-progress" style="display: none;">Harap tunggu... <span
                                        class="spinner-border spinner-border-sm align-middle ms-2"></span></span>
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <div class="modal fade" id="Modal_Detail_Data" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered mw-650px">
            <div class="modal-content">
                <div class="modal-header">
                    <h2 class="fw-bold">Detail Produk</h2>
                    <div class="btn btn-icon btn-sm btn-active-icon-primary" data-bs-dismiss="modal"><i
                            class="ki-outline ki-cross fs-1"></i></div>
                </div>
                <div class="modal-body scroll-y mx-5 mx-xl-15 my-7" id="DetailRowModalBody">
                </div>
                <div class="modal-footer flex-center">
                    <button type="button" class="btn btn-light" data-bs-dismiss="modal">Tutup</button>
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
                var table = $('.table-product').DataTable({
                    processing: true,
                    serverSide: true,
                    order: false,
                    ajax: "{{ route('get-dataproducts') }}",
                    columns: [{
                            data: 'DT_RowIndex',
                            name: 'DT_RowIndex',
                            orderable: false,
                            searchable: false
                        },
                        {
                            data: 'product_info',
                            name: 'brand'
                        }, // brand and model_name
                        {
                            data: 'category',
                            name: 'category.name'
                        },
                        {
                            data: 'total_stock',
                            name: 'total_stock',
                            searchable: false
                        },
                        {
                            data: 'action',
                            name: 'action',
                            orderable: false,
                            searchable: false,
                            className: 'text-end'
                        }
                    ]
                });

                let timeout;
                $('#search').on('keyup', function() {
                    clearTimeout(timeout);
                    timeout = setTimeout(() => {
                        table.search($(this).val()).draw();
                    }, 500);
                });

                // Clear select2 on modal open
                $('#btn_tambah_data').click(function() {
                    $('#FormTambahModalID')[0].reset();
                    $('[name="category_id"]').val(null).trigger('change');
                    $('[name="supplier_id"]').val(null).trigger('change');
                    $('.error-text').text('');
                    $('#Modal_Tambah_Data').modal('show');
                });

                $('#FormTambahModalID').on('submit', function(e) {
                    e.preventDefault();
                    $('#btn-add-data .indicator-label').hide();
                    $('#btn-add-data .indicator-progress').show();
                    $('#btn-add-data').prop('disabled', true);

                    $.ajax({
                        url: "{{ route('products.store') }}",
                        method: 'POST',
                        data: new FormData(this),
                        contentType: false,
                        processData: false,
                        success: function(res) {
                            if (res.errors) {
                                $.each(res.errors, function(prefix, val) {
                                    $('span.' + prefix + '_error_add').text(val[0]);
                                });
                            } else {
                                $('#Modal_Tambah_Data').modal('hide');
                                table.ajax.reload();
                                Swal.fire("Berhasil!", res.success, "success");
                            }
                            resetButton('#btn-add-data');
                        },
                        error: function() {
                            Swal.fire("Error", "Gagal menyimpan data", "error");
                            resetButton('#btn-add-data');
                        }
                    });
                });

                $('body').on('click', '.btn-detail', function() {
                    let id = $(this).data('id');
                    $('#DetailRowModalBody').html(
                        '<div class="text-center"><div class="spinner-border text-primary" role="status"></div></div>'
                    );
                    $('#Modal_Detail_Data').modal('show');

                    $.get("{{ url('admin') }}/products/" + id, function(res) {
                        $('#DetailRowModalBody').html(res.html);
                    });
                });

                $('body').on('click', '.btn-edit', function() {
                    let id = $(this).data('id');
                    $.get("{{ url('admin') }}/products/" + id + "/edit", function(res) {
                        $('#EditRowModalBody').html(res.html);
                        // Initialize Select2 yang baru dirender via AJAX
                        $('#EditRowModalBody select').select2({
                            dropdownParent: $('#Modal_Edit_Data')
                        });
                        $('#Modal_Edit_Data').modal('show');
                    });
                });

                $('#FormEditModalID').on('submit', function(e) {
                    e.preventDefault();
                    let id = $('#edit_product_id').val();
                    $('#btn-edit-data .indicator-label').hide();
                    $('#btn-edit-data .indicator-progress').show();
                    $('#btn-edit-data').prop('disabled', true);

                    $.ajax({
                        url: "{{ url('admin') }}/products/" + id,
                        method: 'POST', // Spoofed as PUT in form
                        data: new FormData(this),
                        contentType: false,
                        processData: false,
                        success: function(res) {
                            if (res.errors) {
                                $.each(res.errors, function(prefix, val) {
                                    $('span.' + prefix + '_error_edit').text(val[0]);
                                });
                            } else {
                                $('#Modal_Edit_Data').modal('hide');
                                table.ajax.reload();
                                Swal.fire("Berhasil!", res.success, "success");
                            }
                            resetButton('#btn-edit-data');
                        },
                        error: function() {
                            Swal.fire("Error", "Gagal mengupdate data", "error");
                            resetButton('#btn-edit-data');
                        }
                    });
                });

                $('body').on('click', '.btn-delete', function() {
                    let id = $(this).data('id');
                    let name = $(this).data('name');
                    Swal.fire({
                        title: "Hapus Produk?",
                        text: "Produk '" + name + "' akan dihapus sementara (Soft Delete).",
                        icon: "warning",
                        showCancelButton: true,
                        confirmButtonText: "Ya, Hapus!",
                        cancelButtonText: "Batal",
                        customClass: {
                            confirmButton: "btn btn-danger",
                            cancelButton: "btn btn-secondary"
                        }
                    }).then((result) => {
                        if (result.isConfirmed) {
                            $.ajax({
                                url: "{{ url('admin') }}/products/" + id,
                                type: "DELETE",
                                data: {
                                    _token: $('meta[name="csrf-token"]').attr('content')
                                },
                                success: function(res) {
                                    if (res.success) {
                                        table.ajax.reload();
                                        Swal.fire("Terhapus!", res.success, "success");
                                    } else {
                                        Swal.fire("Gagal!", res.error, "error");
                                    }
                                }
                            });
                        }
                    });
                });

                function resetButton(selector) {
                    $(selector + ' .indicator-label').show();
                    $(selector + ' .indicator-progress').hide();
                    $(selector).prop('disabled', false);
                }
            });
        </script>
    @endpush
@endsection
