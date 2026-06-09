@extends('backend.layout.app')
@section('title', 'Master Meja')
@section('content')
    <div id="kt_app_toolbar" class="app-toolbar py-3 py-lg-0">
        <div id="kt_app_toolbar_container" class="app-container container-xxl d-flex flex-stack">
            <div class="page-title d-flex flex-column justify-content-center me-3">
                <h1 class="page-heading d-flex text-gray-900 fw-bold fs-3 flex-column justify-content-center my-0">Data Meja
                </h1>
                <ul class="breadcrumb breadcrumb-separatorless fw-semibold fs-7 my-0 pt-1">
                    <li class="breadcrumb-item text-gray-700 fw-bold lh-1">
                        <a href="{{ route('dashboard') }}" class="text-white text-hover-primary">
                            <i class="ki-outline ki-home text-gray-700 fs-6"></i>
                        </a>
                    </li>
                    <li class="breadcrumb-item"><i class="ki-outline ki-right fs-5 text-gray-700 mx-n1"></i></li>
                    <li class="breadcrumb-item text-gray-700 fw-bold lh-1">Data Master</li>
                    <li class="breadcrumb-item"><i class="ki-outline ki-right fs-5 text-gray-700 mx-n1"></i></li>
                    <li class="breadcrumb-item text-gray-900">Meja Resto</li>
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
                                placeholder="Cari No. Meja..." />
                        </div>
                    </div>
                    <div class="card-toolbar">
                        <button type="button" class="btn btn-sm btn-primary" id="btn_tambah_data">
                            <i class="ki-outline ki-plus fs-2"></i> Tambah Meja
                        </button>
                    </div>
                </div>
                <div class="card-body pt-0">
                    <table class="table align-middle table-row-dashed fs-6 gy-5" id="table-meja">
                        <thead>
                            <tr class="text-start text-gray-400 fw-bold fs-7 text-uppercase gs-0">
                                <th class="w-10px pe-2">No</th>
                                <th class="min-w-150px">Nomor / Nama Meja</th>
                                <th class="min-w-100px">Kapasitas</th>
                                <th class="min-w-100px">Status</th>
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
        <div class="modal-dialog modal-dialog-centered mw-500px">
            <div class="modal-content">
                <div class="modal-header">
                    <h2 class="fw-bold">Tambah Meja</h2>
                    <div class="btn btn-icon btn-sm btn-active-icon-primary" data-bs-dismiss="modal"><i
                            class="ki-outline ki-cross fs-1"></i></div>
                </div>
                <div class="modal-body mx-5 mx-xl-15 my-7">
                    <form id="FormTambahModalID" class="form">
                        @csrf
                        <div class="fv-row mb-7">
                            <label class="required fs-6 fw-semibold mb-2">Nomor/Nama Meja</label>
                            <input type="text" class="form-control" name="table_number"
                                placeholder="Contoh: Meja 01, VIP 2" />
                            <span class="text-danger error-text table_number_error_add mt-2"></span>
                        </div>
                        <div class="fv-row mb-7">
                            <label class="required fs-6 fw-semibold mb-2">Kapasitas (Orang)</label>
                            <input type="number" class="form-control" name="capacity" value="2" min="1" />
                            <span class="text-danger error-text capacity_error_add mt-2"></span>
                        </div>
                        <div class="text-center pt-10">
                            <button type="button" class="btn btn-light me-3" data-bs-dismiss="modal">Batal</button>
                            <button type="submit" class="btn btn-primary" id="btn-add-data">Simpan</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <div class="modal fade" id="Modal_Edit_Data" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered mw-500px">
            <div class="modal-content">
                <div class="modal-header">
                    <h2 class="fw-bold">Edit Meja</h2>
                    <div class="btn btn-icon btn-sm btn-active-icon-primary" data-bs-dismiss="modal"><i
                            class="ki-outline ki-cross fs-1"></i></div>
                </div>
                <div class="modal-body mx-5 mx-xl-15 my-7">
                    <form id="FormEditModalID" class="form">
                        @csrf @method('PUT')
                        <div id="EditRowModalBody"></div>
                        <div class="text-center pt-10">
                            <button type="button" class="btn btn-light me-3" data-bs-dismiss="modal">Batal</button>
                            <button type="submit" class="btn btn-primary" id="btn-edit-data">Simpan Perubahan</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <div class="modal fade" id="Modal_Detail_Data" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered mw-500px">
            <div class="modal-content">
                <div class="modal-header">
                    <h2 class="fw-bold">Detail Meja</h2>
                    <div class="btn btn-icon btn-sm btn-active-icon-primary" data-bs-dismiss="modal"><i
                            class="ki-outline ki-cross fs-1"></i></div>
                </div>
                <div class="modal-body mx-5 mx-xl-15 my-7" id="DetailRowModalBody">
                </div>
                <div class="modal-footer text-center">
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
                // Inisialisasi DataTable
                var table = $('#table-meja').DataTable({
                    processing: true,
                    serverSide: true,
                    ajax: "{{ route('get-datatables') }}",
                    columns: [{
                            data: 'DT_RowIndex',
                            name: 'DT_RowIndex',
                            orderable: false,
                            searchable: false
                        },
                        {
                            data: 'table_number',
                            name: 'table_number'
                        },
                        {
                            data: 'capacity',
                            name: 'capacity',
                            render: function(data) {
                                return data + ' Orang';
                            }
                        },
                        {
                            data: 'status_badge',
                            name: 'status',
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

                // Fitur Pencarian
                $('#search').on('keyup', function() {
                    table.search(this.value).draw();
                });

                // Buka Modal Tambah
                $('#btn_tambah_data').click(function() {
                    $('#FormTambahModalID')[0].reset();
                    $('.error-text').text('');
                    $('#Modal_Tambah_Data').modal('show');
                });

                // Submit Form Tambah
                $('#FormTambahModalID').on('submit', function(e) {
                    e.preventDefault();
                    $('.error-text').text('');
                    $('#btn-add-data').prop('disabled', true).text('Menyimpan...');

                    $.ajax({
                        url: "{{ route('tables.store') }}",
                        method: 'POST',
                        data: $(this).serialize(),
                        success: function(res) {
                            $('#Modal_Tambah_Data').modal('hide');
                            table.ajax.reload();
                            Swal.fire("Berhasil!", res.success, "success");
                            $('#btn-add-data').prop('disabled', false).text('Simpan');
                        },
                        error: function(xhr) {
                            if (xhr.status === 422) {
                                let errors = xhr.responseJSON.errors;
                                $.each(errors, function(key, val) {
                                    $('span.' + key + '_error_add').text(val[0]);
                                });
                            } else {
                                Swal.fire("Error", "Gagal menyimpan data", "error");
                            }
                            $('#btn-add-data').prop('disabled', false).text('Simpan');
                        }
                    });
                });

                // Buka Modal Edit
                $('body').on('click', '.btn-edit', function() {
                    let id = $(this).data('id');
                    $.get("{{ url('admin') }}/tables/" + id + "/edit", function(res) {
                        $('#EditRowModalBody').html(res.html);
                        $('#Modal_Edit_Data').modal('show');
                    });
                });

                // Submit Form Edit
                $('#FormEditModalID').on('submit', function(e) {
                    e.preventDefault();
                    $('.error-text').text('');
                    let id = $('#edit_table_id').val();
                    $('#btn-edit-data').prop('disabled', true).text('Menyimpan...');

                    $.ajax({
                        url: "{{ url('admin') }}/tables/" + id,
                        type: 'PUT', // 🔥 PERBAIKAN DI SINI: Langsung gunakan type PUT
                        data: $(this).serialize(),
                        success: function(res) {
                            $('#Modal_Edit_Data').modal('hide');
                            table.ajax.reload();
                            Swal.fire("Berhasil!", res.success, "success");
                            $('#btn-edit-data').prop('disabled', false).text('Simpan Perubahan');
                        },
                        error: function(xhr) {
                            if (xhr.status === 422) {
                                let errors = xhr.responseJSON.errors;
                                $.each(errors, function(key, val) {
                                    $('span.' + key + '_error_edit').text(val[0]);
                                });
                            } else {
                                Swal.fire("Error", "Gagal mengupdate data", "error");
                            }
                            $('#btn-edit-data').prop('disabled', false).text('Simpan Perubahan');
                        }
                    });
                });

                // Buka Modal Detail
                $('body').on('click', '.btn-detail', function() {
                    let id = $(this).data('id');

                    // Tambahkan loading spinner
                    $('#DetailRowModalBody').html(
                        '<div class="text-center"><span class="spinner-border text-primary"></span></div>');
                    $('#Modal_Detail_Data').modal('show');

                    $.get("{{ url('admin') }}/tables/" + id, function(res) {
                        $('#DetailRowModalBody').html(res.html);
                    }).fail(function() {
                        Swal.fire("Error", "Gagal mengambil data detail", "error");
                        $('#Modal_Detail_Data').modal('hide');
                    });
                });

                // Hapus Data
                $('body').on('click', '.btn-delete', function() {
                    let id = $(this).data('id');
                    let name = $(this).data('number');

                    Swal.fire({
                        title: "Hapus Meja?",
                        text: "Meja '" + name + "' akan dihapus permanen.",
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
                                url: "{{ url('admin') }}/tables/" + id,
                                type: "DELETE",
                                data: {
                                    _token: $('meta[name="csrf-token"]').attr('content')
                                },
                                success: function(res) {
                                    table.ajax.reload();
                                    Swal.fire("Terhapus!", res.success, "success");
                                },
                                error: function() {
                                    Swal.fire("Error",
                                        "Meja sedang digunakan atau gagal dihapus.",
                                        "error");
                                }
                            });
                        }
                    });
                });
            });
        </script>
    @endpush
@endsection
