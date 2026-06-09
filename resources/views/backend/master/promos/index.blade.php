@extends('backend.layout.app')
@section('title', 'Master Data Promo & Diskon')
@section('content')

    <div id="kt_app_toolbar" class="app-toolbar py-3 py-lg-0">
        <div id="kt_app_toolbar_container" class="app-container container-xxl d-flex flex-stack">
            <div class="page-title d-flex flex-column justify-content-center me-3">
                <h1 class="page-heading d-flex text-gray-900 fw-bold fs-3 flex-column justify-content-center my-0">Data Promo
                    & Diskon</h1>
                <ul class="breadcrumb breadcrumb-separatorless fw-semibold fs-7 my-0 pt-1">
                    <li class="breadcrumb-item text-gray-700 fw-bold lh-1">
                        <a href="{{ route('dashboard') }}" class="text-white text-hover-primary">
                            <i class="ki-outline ki-home text-gray-700 fs-6"></i>
                        </a>
                    </li>
                    <li class="breadcrumb-item"><i class="ki-outline ki-right fs-5 text-gray-700 mx-n1"></i></li>
                    <li class="breadcrumb-item text-gray-700 fw-bold lh-1">Data Master</li>
                    <li class="breadcrumb-item"><i class="ki-outline ki-right fs-5 text-gray-700 mx-n1"></i></li>
                    <li class="breadcrumb-item text-gray-900">Promo & Diskon</li>
                </ul>
            </div>
        </div>
    </div>
    <div id="kt_app_content" class="app-content flex-column-fluid">
        <div id="kt_app_content_container" class="app-container container-xxl">

            <div class="card card-flush shadow-sm">
                <div class="card-header align-items-center py-5 gap-2 gap-md-5">
                    <div class="card-title">
                        <h3 class="card-title fw-bold text-gray-800 fs-3">Daftar Promo & Diskon</h3>
                    </div>
                    <div class="card-toolbar">
                        <button type="button" class="btn btn-sm btn-primary" id="btn-add-promo">
                            <i class="ki-outline ki-plus fs-2"></i> Tambah Promo
                        </button>
                    </div>
                </div>
                <div class="card-body pt-0">
                    <div class="table-responsive">
                        <table class="table align-middle table-row-dashed fs-6 gy-5" id="table-promos">
                            <thead>
                                <tr class="text-start text-gray-500 fw-bold fs-7 text-uppercase gs-0">
                                    <th class="w-50px">No</th>
                                    <th>Nama Promo</th>
                                    <th>Nilai Diskon</th>
                                    <th class="text-center">Status Aktif</th>
                                    <th class="text-center min-w-100px">Aksi</th>
                                </tr>
                            </thead>
                            <tbody class="fw-semibold text-gray-600">
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

        </div>
    </div>
    <div class="modal fade" tabindex="-1" id="Modal_Promo" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered mw-500px">
            <div class="modal-content">
                <form id="form-promo">
                    @csrf
                    <input type="hidden" id="promo_id">
                    <div class="modal-header">
                        <h2 class="modal-title fw-bold" id="modal_title">Tambah Promo</h2>
                        <div class="btn btn-icon btn-sm btn-active-icon-primary" data-bs-dismiss="modal" aria-label="Close">
                            <i class="ki-outline ki-cross fs-1"></i>
                        </div>
                    </div>

                    <div class="modal-body mx-5 mx-xl-15 my-7">
                        <div class="fv-row mb-7">
                            <label class="required fs-6 fw-semibold mb-2">Nama Promo</label>
                            <input type="text" class="form-control" id="name" name="name"
                                placeholder="Misal: Promo Natal, Diskon Karyawan" required />
                        </div>

                        <div class="row mb-7">
                            <div class="col-md-6">
                                <label class="required fs-6 fw-semibold mb-2">Tipe Diskon</label>
                                <select id="discount_type" name="discount_type" class="form-select form-select-solid"
                                    required>
                                    <option value="percentage">Persentase (%)</option>
                                    <option value="nominal">Nominal (Rp)</option>
                                </select>
                            </div>
                            <div class="col-md-6">
                                <label class="required fs-6 fw-semibold mb-2">Nilai Diskon</label>
                                <input type="number" class="form-control" id="discount_value" name="discount_value"
                                    min="1" required />
                            </div>
                        </div>

                        <div class="fv-row mb-3 d-flex flex-stack bg-light rounded p-5">
                            <div class="me-5">
                                <label class="fs-6 fw-bold">Aktifkan Promo?</label>
                                <div class="fs-7 text-muted">Promo aktif akan muncul di Kasir.</div>
                            </div>
                            <label class="form-check form-switch form-check-custom form-check-solid">
                                <input class="form-check-input h-30px w-50px" type="checkbox" id="is_active"
                                    name="is_active" checked="checked" />
                            </label>
                        </div>
                    </div>

                    <div class="modal-footer text-center pt-10 flex-center">
                        <button type="button" class="btn btn-light me-3" data-bs-dismiss="modal">Batal</button>
                        <button type="submit" class="btn btn-primary" id="btn-save">
                            <span class="indicator-label">Simpan Data</span>
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    @push('stylesheets')
        <link rel="stylesheet" href="{{ URL::to('assets/plugins/custom/datatables/datatables.bundle.css') }}" />
    @endpush

    @push('scripts')
        <script src="{{ URL::to('assets/plugins/custom/datatables/datatables.bundle.js') }}"></script>

        <script>
            $(document).ready(function() {
                $.ajaxSetup({
                    headers: {
                        'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
                    }
                });

                // 1. Inisialisasi DataTables
                let table = $('#table-promos').DataTable({
                    processing: true,
                    serverSide: true,
                    ajax: "{{ route('promos.data') }}",
                    columns: [{
                            data: 'DT_RowIndex',
                            name: 'DT_RowIndex',
                            orderable: false,
                            searchable: false
                        },
                        {
                            data: 'name',
                            name: 'name',
                            className: 'fw-bold text-gray-800'
                        },
                        {
                            data: 'discount_info',
                            name: 'discount_value'
                        },
                        {
                            data: 'status_toggle',
                            name: 'is_active',
                            className: 'text-center',
                            orderable: false,
                            searchable: false
                        },
                        {
                            data: 'action',
                            name: 'action',
                            orderable: false,
                            searchable: false,
                            className: 'text-center'
                        }
                    ]
                });

                // 2. Tombol Tambah Ditekan
                $('#btn-add-promo').click(function() {
                    $('#form-promo')[0].reset();
                    $('#promo_id').val('');
                    $('#modal_title').text('Tambah Promo');
                    $('#is_active').prop('checked', true);
                    $('#Modal_Promo').modal('show');
                });

                // 3. Tombol Edit Ditekan
                $('body').on('click', '.btn-edit', function() {
                    let id = $(this).data('id');
                    $.get("{{ url('admin') }}/promos/" + id + "/edit", function(data) {
                        $('#modal_title').text('Edit Promo');
                        $('#promo_id').val(data.id);
                        $('#name').val(data.name);
                        $('#discount_type').val(data.discount_type);
                        $('#discount_value').val(data.discount_value);
                        $('#is_active').prop('checked', data.is_active == 1);
                        $('#Modal_Promo').modal('show');
                    });
                });

                // 4. Submit Form (Create / Update)
                $('#form-promo').submit(function(e) {
                    e.preventDefault();
                    let btn = $('#btn-save');
                    btn.prop('disabled', true).text('Menyimpan...');

                    let id = $('#promo_id').val();
                    let url = id ? "{{ url('admin') }}/promos/" + id : "{{ url('admin') }}/promos";
                    let method = id ? "PUT" : "POST";

                    let formData = $(this).serialize();

                    $.ajax({
                        url: url,
                        type: method,
                        data: formData,
                        success: function(res) {
                            $('#Modal_Promo').modal('hide');
                            Swal.fire('Berhasil!', res.success, 'success');
                            table.ajax.reload(null, false);
                            btn.prop('disabled', false).text('Simpan Data');
                        },
                        error: function(err) {
                            Swal.fire('Error!', 'Pastikan semua form terisi dengan benar.',
                                'error');
                            btn.prop('disabled', false).text('Simpan Data');
                        }
                    });
                });

                // 5. Toggle Switch Status Aktif
                $('body').on('change', '.toggle-status', function() {
                    let id = $(this).data('id');
                    let is_active = $(this).is(':checked') ? 1 : 0;

                    $.ajax({
                        url: "{{ url('admin') }}/promos/toggle/" + id,
                        type: "POST",
                        data: {
                            // 🔥 TAMBAHKAN BARIS INI UNTUK MENGATASI ERROR 419
                            _token: '{{ csrf_token() }}',
                            is_active: is_active
                        },
                        success: function(res) {
                            toastr.success(res.success);
                        },
                        error: function() {
                            toastr.error('Gagal mengubah status promo.');
                            table.ajax.reload(null, false);
                        }
                    });
                });

                // 6. Hapus Data
                $('body').on('click', '.btn-delete', function() {
                    let id = $(this).data('id');
                    let name = $(this).data('name');

                    Swal.fire({
                        title: "Hapus Promo?",
                        text: "Yakin ingin menghapus '" + name + "'?",
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
                                url: "{{ url('admin') }}/promos/" + id,
                                type: "DELETE",
                                success: function(res) {
                                    Swal.fire("Terhapus!", res.success, "success");
                                    table.ajax.reload(null, false);
                                }
                            });
                        }
                    });
                });
            });
        </script>
    @endpush
@endsection
