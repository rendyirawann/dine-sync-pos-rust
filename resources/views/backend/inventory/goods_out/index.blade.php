@extends('backend.layout.app')
@section('title', 'Barang Keluar (Goods Out)')
@section('content')

    <div id="kt_app_content" class="app-content flex-column-fluid mt-5">
        <div id="kt_app_content_container" class="app-container container-xxl">
            <div class="card card-flush">
                <div class="card-header align-items-center py-5 gap-2 gap-md-5">
                    <div class="card-title">
                        <h3 class="fw-bold text-gray-800 fs-2">Riwayat Barang Keluar</h3>
                    </div>
                    <div class="card-toolbar">
                        <button type="button" class="btn btn-sm btn-danger" id="btn_tambah_data">
                            <i class="ki-outline ki-minus-square fs-2"></i> Input Keluar Manual
                        </button>
                    </div>
                </div>
                <div class="card-body pt-0">
                    <table class="table align-middle table-row-dashed fs-6 gy-5 table-goodsout">
                        <thead>
                            <tr class="text-start text-gray-400 fw-bold fs-7 text-uppercase gs-0">
                                <th class="w-10px pe-2">No</th>
                                <th>Tanggal</th>
                                <th>No. Referensi</th>
                                <th>Tipe Keluar</th>
                                <th>Total Item</th>
                                <th>Catatan</th>
                                <th class="text-end min-w-100px">Aksi</th>
                            </tr>
                        </thead>
                        <tbody class="fw-semibold text-gray-600"></tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <div class="modal fade" id="Modal_Tambah_Data" tabindex="-1" aria-hidden="true" data-bs-backdrop="static">
        <div class="modal-dialog modal-dialog-centered mw-900px">
            <div class="modal-content">
                <div class="modal-header">
                    <h2 class="fw-bold text-danger">Input Barang Keluar Manual</h2>
                    <div class="btn btn-icon btn-sm btn-active-icon-primary" data-bs-dismiss="modal"><i
                            class="ki-outline ki-cross fs-1"></i></div>
                </div>
                <div class="modal-body scroll-y mx-5 mx-xl-15 my-7">
                    <form id="FormTambahModalID" class="form">
                        @csrf
                        <div class="row mb-7">
                            <div class="col-md-4 fv-row">
                                <label class="required fs-6 fw-semibold mb-2">Tanggal Keluar</label>
                                <input type="date" class="form-control" name="date"
                                    value="{{ \Carbon\Carbon::today()->format('Y-m-d') }}" required />
                            </div>
                            <div class="col-md-4 fv-row">
                                <label class="required fs-6 fw-semibold mb-2">Tipe/Alasan Keluar</label>
                                <select name="type" class="form-select" data-control="select2" data-hide-search="true"
                                    data-dropdown-parent="#Modal_Tambah_Data" required>
                                    <option value="reject">Reject / Rusak / Hilang</option>
                                    <option value="online">Terjual Online</option>
                                    <option value="adjustment">Penyesuaian (Sample dll)</option>
                                </select>
                            </div>
                            <div class="col-md-4 fv-row">
                                <label class="fs-6 fw-semibold mb-2">Catatan Tambahan</label>
                                <input type="text" class="form-control" name="notes"
                                    placeholder="Contoh: Digigit tikus" />
                            </div>
                        </div>

                        <div class="separator border-danger my-10"></div>
                        <h4 class="fw-bold text-gray-800 mb-5">Pilih Barang yang Dikeluarkan (Berdasarkan Batch)</h4>

                        <div id="repeater_container">
                            <div class="item-row border border-dashed border-gray-300 rounded p-5 mb-5 bg-light-danger">
                                <div class="row">
                                    <div class="col-md-9">
                                        <label class="required fs-7 fw-semibold mb-2">Pilih Batch Spesifik</label>
                                        <select name="items[0][batch_id]" class="form-select form-select-sm select2-batch"
                                            required>
                                            <option value="">-- Cari Kode Batch atau Nama Sepatu --</option>
                                            @include('backend.inventory.goods_out._batch_options')
                                        </select>
                                    </div>
                                    <div class="col-md-2">
                                        <label class="required fs-7 fw-semibold mb-2">Qty Dibuang</label>
                                        <input type="number" class="form-control form-control-sm qty-input"
                                            name="items[0][qty]" min="1" value="1" required />
                                    </div>
                                    <div class="col-md-1 d-flex align-items-end">
                                        <button type="button" class="btn btn-icon btn-sm btn-danger btn-remove-row"
                                            disabled><i class="ki-outline ki-trash"></i></button>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <button type="button" class="btn btn-sm btn-light-danger mb-7" id="btn_add_repeater">
                            <i class="ki-outline ki-plus fs-2"></i> Tambah Item Lainnya
                        </button>

                        <div class="text-center pt-10">
                            <button type="button" class="btn btn-light me-3" data-bs-dismiss="modal">Batal</button>
                            <button type="submit" class="btn btn-danger" id="btn-add-data">Keluarkan dari Gudang!</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <div class="modal fade" id="Modal_Edit_Data" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered mw-500px">
            <div class="modal-content" id="EditRowModalBody">
            </div>
        </div>
    </div>

    <div class="modal fade" id="Modal_Detail_Data" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered mw-800px">
            <div class="modal-content">
                <div class="modal-header">
                    <h2 class="fw-bold">Rincian Dokumen Barang Keluar</h2>
                    <div class="btn btn-icon btn-sm btn-active-icon-primary" data-bs-dismiss="modal"><i
                            class="ki-outline ki-cross fs-1"></i></div>
                </div>
                <div class="modal-body scroll-y mx-5 mx-xl-15 my-7" id="DetailRowModalBody"></div>
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
            document.addEventListener('DOMContentLoaded', function() {

                var table = $('.table-goodsout').DataTable({
                    processing: true,
                    serverSide: true,
                    ajax: "{{ route('get-datagoodsout') }}",
                    columns: [{
                            data: 'DT_RowIndex',
                            name: 'DT_RowIndex',
                            orderable: false,
                            searchable: false
                        },
                        {
                            data: 'date',
                            name: 'date'
                        },
                        {
                            data: 'reference',
                            name: 'reference_no'
                        },
                        {
                            data: 'type',
                            name: 'type'
                        },
                        {
                            data: 'total_items',
                            name: 'total_items',
                            searchable: false
                        },
                        {
                            data: 'notes',
                            name: 'notes'
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

                // Inisialisasi Select2 Pertama
                $('.select2-batch').select2({
                    dropdownParent: $('#Modal_Tambah_Data')
                });

                // LOGIKA REPEATER FORM
                let itemIndex = 1;
                let batchOptions = `{!! addslashes(
                    str_replace("\n", '', view('backend.inventory.goods_out._batch_options', compact('activeBatches'))->render()),
                ) !!}`;

                $('#btn_add_repeater').click(function() {
                    let newRow = `
            <div class="item-row border border-dashed border-gray-300 rounded p-5 mb-5 bg-light-danger">
                <div class="row">
                    <div class="col-md-9">
                        <label class="required fs-7 fw-semibold mb-2">Pilih Batch Spesifik</label>
                        <select name="items[${itemIndex}][batch_id]" class="form-select form-select-sm select2-dynamic" required>
                            <option value="">-- Cari Kode Batch atau Nama Sepatu --</option>
                            ${batchOptions}
                        </select>
                    </div>
                    <div class="col-md-2">
                        <label class="required fs-7 fw-semibold mb-2">Qty Dibuang</label>
                        <input type="number" class="form-control form-control-sm qty-input" name="items[${itemIndex}][qty]" min="1" value="1" required/>
                    </div>
                    <div class="col-md-1 d-flex align-items-end">
                        <button type="button" class="btn btn-icon btn-sm btn-danger btn-remove-row"><i class="ki-outline ki-trash"></i></button>
                    </div>
                </div>
            </div>`;

                    $('#repeater_container').append(newRow);
                    $('.select2-dynamic').select2({
                        dropdownParent: $('#Modal_Tambah_Data')
                    });
                    itemIndex++;
                });

                // Hapus Baris
                $(document).on('click', '.btn-remove-row', function() {
                    $(this).closest('.item-row').remove();
                });

                // Event listener saat Pilih Batch (Otomatis set batas maksimal Qty sesuai sisa stok batch)
                $(document).on('change', '.select2-batch, .select2-dynamic', function() {
                    let maxQty = parseInt($(this).find(':selected').attr('data-max')) || 1;
                    let qtyInput = $(this).closest('.row').find('.qty-input');
                    qtyInput.attr('max', maxQty); // Cegah input lebih dari stok batch
                    if (parseInt(qtyInput.val()) > maxQty) {
                        qtyInput.val(maxQty);
                    }
                });

                // Buka Modal Tambah
                $('#btn_tambah_data').click(function() {
                    $('#FormTambahModalID')[0].reset();
                    $('.select2-batch').val(null).trigger('change');
                    $('[name="type"]').val('reject').trigger('change');

                    $('#repeater_container').children('.item-row:not(:first)').remove();
                    $('#Modal_Tambah_Data').modal('show');
                });

                // Submit Form Manual Out
                $('#FormTambahModalID').on('submit', function(e) {
                    e.preventDefault();
                    $('#btn-add-data').prop('disabled', true).text('Menyimpan...');

                    $.ajax({
                        url: "{{ route('goods-out.store') }}",
                        method: "POST",
                        data: $(this).serialize(),
                        success: function(res) {
                            $('#Modal_Tambah_Data').modal('hide');
                            Swal.fire("Berhasil!", res.success, "success").then(() => location
                                .reload()); // Reload agar Active Batches ter-update
                        },
                        error: function(err) {
                            Swal.fire("Gagal", err.responseJSON?.error || "Cek kembali form anda.",
                                "error");
                            $('#btn-add-data').prop('disabled', false).text(
                                'Keluarkan dari Gudang!');
                        }
                    });
                });

                // TOMBOL LIHAT DETAIL
                $('body').on('click', '.btn-detail', function() {
                    let id = $(this).data('id');
                    $('#DetailRowModalBody').html(
                        '<div class="text-center"><span class="spinner-border"></span></div>');
                    $('#Modal_Detail_Data').modal('show');
                    $.get("{{ url('admin') }}/goods-out/" + id, function(res) {
                        $('#DetailRowModalBody').html(res.html);
                    });
                });

                // TOMBOL EDIT INFO
                $('body').on('click', '.btn-edit', function() {
                    let id = $(this).data('id');
                    $.get("{{ url('admin') }}/goods-out/" + id + "/edit", function(res) {
                        $('#EditRowModalBody').html(res.html);
                        $('#EditRowModalBody select').select2();
                        $('#Modal_Edit_Data').modal('show');
                    });
                });

                // SUBMIT EDIT
                $('body').on('submit', '#FormEditModalID', function(e) {
                    e.preventDefault();
                    let id = $('#edit_goodsout_id').val();
                    $.ajax({
                        url: "{{ url('admin') }}/goods-out/" + id,
                        method: "POST",
                        data: $(this).serialize(),
                        success: function(res) {
                            $('#Modal_Edit_Data').modal('hide');
                            table.ajax.reload();
                            Swal.fire("Berhasil!", res.success, "success");
                        },
                        error: function(err) {
                            Swal.fire("Ditolak!", err.responseJSON?.error, "error");
                        }
                    });
                });

                // DELETE (MENGEMBALIKAN STOK)
                $('body').on('click', '.btn-delete', function() {
                    let id = $(this).data('id');
                    let ref = $(this).data('ref');
                    Swal.fire({
                        title: "Hapus Dokumen?",
                        html: "Membatalkan dokumen <b>" + ref +
                            "</b> akan <b class='text-success'>mengembalikan stok</b> ke dalam gudang.",
                        icon: "warning",
                        showCancelButton: true,
                        confirmButtonText: "Ya, Kembalikan Stok!",
                        cancelButtonText: "Batal",
                        customClass: {
                            confirmButton: "btn btn-danger",
                            cancelButton: "btn btn-secondary"
                        }
                    }).then((result) => {
                        if (result.isConfirmed) {
                            $.ajax({
                                url: "{{ url('admin') }}/goods-out/" + id,
                                type: "DELETE",
                                data: {
                                    _token: $('meta[name="csrf-token"]').attr('content')
                                },
                                success: function(res) {
                                    Swal.fire("Berhasil!", res.success, "success").then(
                                        () => location.reload());
                                },
                                error: function(err) {
                                    Swal.fire("Ditolak!", err.responseJSON?.error, "error");
                                }
                            });
                        }
                    });
                });
            });
        </script>
    @endpush
@endsection
