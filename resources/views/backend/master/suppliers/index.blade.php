@extends('backend.layout.app')
@section('title', 'Master Supplier')
@section('content')
    <div id="kt_app_toolbar" class="app-toolbar py-3 py-lg-0">
        <div id="kt_app_toolbar_container" class="app-container container-xxl d-flex flex-stack">
            <div class="page-title d-flex flex-column justify-content-center me-3">
                <h1 class="page-heading d-flex text-gray-900 fw-bold fs-3 flex-column justify-content-center my-0">Manajemen Supplier</h1>
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
                            <input type="text" id="search" class="form-control w-250px ps-12" placeholder="Cari Supplier..." />
                        </div>
                    </div>
                    <div class="card-toolbar">
                        <button type="button" class="btn btn-sm btn-primary" id="btn_tambah_data">
                            <i class="ki-outline ki-plus fs-2"></i> Tambah Supplier
                        </button>
                    </div>
                </div>
                <div class="card-body pt-0">
                    <table class="table align-middle table-row-dashed fs-6 gy-5" id="table-suppliers">
                        <thead>
                            <tr class="text-start text-gray-400 fw-bold fs-7 text-uppercase gs-0">
                                <th class="w-10px pe-2">No</th>
                                <th class="min-w-200px">Nama Supplier</th>
                                <th class="min-w-150px">Kontak / Person</th>
                                <th class="min-w-150px">No. Telepon</th>
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
        <div class="modal-dialog modal-dialog-centered mw-650px">
            <div class="modal-content">
                <div class="modal-header">
                    <h2 class="fw-bold" id="modal_title">Tambah Supplier</h2>
                    <div class="btn btn-icon btn-sm btn-active-icon-primary" data-bs-dismiss="modal">
                        <i class="ki-outline ki-cross fs-1"></i>
                    </div>
                </div>
                <div class="modal-body mx-5 mx-xl-15 my-7">
                    <form id="FormModalID" class="form">
                        @csrf
                        <input type="hidden" name="id" id="supplier_id">
                        <div class="fv-row mb-7">
                            <label class="required fs-6 fw-semibold mb-2">Nama Supplier</label>
                            <input type="text" class="form-control" name="name" id="name" placeholder="Misal: PT. Jaya Abadi" required />
                        </div>
                        <div class="fv-row mb-7">
                            <label class="fs-6 fw-semibold mb-2">Kontak Person</label>
                            <input type="text" class="form-control" name="contact_person" id="contact_person" placeholder="Nama PIC" />
                        </div>
                        <div class="fv-row mb-7">
                            <label class="fs-6 fw-semibold mb-2">No. Telepon</label>
                            <input type="text" class="form-control" name="phone" id="phone" placeholder="0812..." />
                        </div>
                        <div class="fv-row mb-7">
                            <label class="fs-6 fw-semibold mb-2">Alamat</label>
                            <textarea class="form-control" name="address" id="address" rows="3"></textarea>
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
                var table = $('#table-suppliers').DataTable({
                    processing: true,
                    serverSide: true,
                    ajax: "{{ route('get-datasuppliers') }}",
                    columns: [
                        {data: 'DT_RowIndex', name: 'DT_RowIndex', orderable: false, searchable: false},
                        {data: 'name', name: 'name'},
                        {data: 'contact_person', name: 'contact_person'},
                        {data: 'phone', name: 'phone'},
                        {data: 'action', name: 'action', orderable: false, searchable: false, className: 'text-end'}
                    ]
                });

                $('#btn_tambah_data').click(function() {
                    $('#FormModalID')[0].reset();
                    $('#supplier_id').val('');
                    $('#modal_title').text('Tambah Supplier');
                    $('#Modal_Data').modal('show');
                });

                $('body').on('click', '.btn-edit', function() {
                    let id = $(this).data('id');
                    $.get("{{ url('admin/suppliers') }}/" + id + "/edit", function(res) {
                        $('#supplier_id').val(res.id);
                        $('#name').val(res.name);
                        $('#contact_person').val(res.contact_person);
                        $('#phone').val(res.phone);
                        $('#address').val(res.address);
                        $('#modal_title').text('Edit Supplier');
                        $('#Modal_Data').modal('show');
                    });
                });

                $('#FormModalID').on('submit', function(e) {
                    e.preventDefault();
                    let id = $('#supplier_id').val();
                    let url = id ? "{{ url('admin/suppliers') }}/" + id : "{{ route('suppliers.store') }}";
                    let method = id ? "PUT" : "POST";
                    $.ajax({
                        url: url,
                        method: method,
                        data: $(this).serialize(),
                        success: function(res) {
                            $('#Modal_Data').modal('hide');
                            table.ajax.reload();
                            Swal.fire("Berhasil!", res.success, "success");
                        }
                    });
                });

                $('body').on('click', '.btn-delete', function() {
                    let id = $(this).data('id');
                    let name = $(this).data('name');
                    Swal.fire({
                        title: "Hapus Supplier?",
                        text: "Data '" + name + "' akan dihapus.",
                        icon: "warning",
                        showCancelButton: true,
                        confirmButtonText: "Ya, Hapus!"
                    }).then((result) => {
                        if (result.isConfirmed) {
                            $.ajax({
                                url: "{{ url('admin/suppliers') }}/" + id,
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