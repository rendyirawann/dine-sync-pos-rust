@extends('backend.layout.app')
@section('title', 'Barang Masuk & Restock')
@section('content')

    <div id="kt_app_content" class="app-content flex-column-fluid mt-5">
        <div id="kt_app_content_container" class="app-container container-xxl">
            <div class="card card-flush">
                <div class="card-header align-items-center py-5 gap-2 gap-md-5">
                    <div class="card-title">
                        <h3 class="fw-bold text-gray-800 fs-2">Riwayat Barang Masuk</h3>
                    </div>
                    <div class="card-toolbar">
                        <button type="button" class="btn btn-sm btn-primary" id="btn_tambah_data">
                            <i class="ki-outline ki-plus-square fs-2"></i> Input Barang Masuk
                        </button>
                    </div>
                </div>
                <div class="card-body pt-0">
                    <table class="table align-middle table-row-dashed fs-6 gy-5 table-purchases">
                        <thead>
                            <tr class="text-start text-gray-400 fw-bold fs-7 text-uppercase gs-0">
                                <th class="w-10px pe-2">No</th>
                                <th>Tanggal</th>
                                <th>No. Referensi</th>
                                <th>Supplier</th>
                                <th>Total Item</th>
                                <th>Total Tagihan</th>
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
                    <h2 class="fw-bold">Input Barang Masuk Baru</h2>
                    <div class="btn btn-icon btn-sm btn-active-icon-primary" data-bs-dismiss="modal"><i
                            class="ki-outline ki-cross fs-1"></i></div>
                </div>
                <div class="modal-body scroll-y mx-5 mx-xl-15 my-7">
                    <form id="FormTambahModalID" class="form">
                        @csrf
                        <div class="row mb-7">
                            <div class="col-md-6 fv-row">
                                <label class="required fs-6 fw-semibold mb-2">Tanggal Pembelian</label>
                                <input type="date" class="form-control" name="purchase_date"
                                    value="{{ \Carbon\Carbon::today()->format('Y-m-d') }}" required />
                            </div>
                            <div class="col-md-6 fv-row">
                                <label class="fs-6 fw-semibold mb-2">Supplier (Pemasok)</label>
                                <select name="supplier_id" class="form-select form-select-solid" data-control="select2"
                                    data-dropdown-parent="#Modal_Tambah_Data" data-placeholder="Pilih Supplier"
                                    data-allow-clear="true">
                                    <option></option>
                                    @foreach ($suppliers as $sup)
                                        <option value="{{ $sup->id }}">{{ $sup->name }}</option>
                                    @endforeach
                                </select>
                            </div>
                        </div>

                        <div class="separator my-10"></div>
                        <h4 class="fw-bold text-gray-800 mb-5">Daftar Batch Barang Masuk</h4>

                        <div id="repeater_container">
                            <div class="item-row border border-dashed border-gray-300 rounded p-5 mb-5 bg-light">
                                <div class="row">
                                    <div class="col-md-5">
                                        <label class="required fs-7 fw-semibold mb-2">Pilih Varian Sepatu</label>
                                        <select name="items[0][variant_id]"
                                            class="form-select form-select-sm select2-variant" required>
                                            <option value="">-- Pilih --</option>
                                            @foreach ($variants as $var)
                                                <option value="{{ $var->id }}" data-price="{{ $var->price_buy }}">
                                                    {{ $var->product->brand }} {{ $var->product->model_name }} |
                                                    {{ $var->color }} | Sz: {{ $var->size }}
                                                </option>
                                            @endforeach
                                        </select>
                                    </div>
                                    <div class="col-md-2">
                                        <label class="required fs-7 fw-semibold mb-2">Qty Masuk</label>
                                        <input type="number" class="form-control form-control-sm qty-input"
                                            name="items[0][qty]" min="1" value="1" required />
                                    </div>
                                    <div class="col-md-4">
                                        <label class="required fs-7 fw-semibold mb-2">Harga Modal (Satuan)</label>
                                        <input type="number" class="form-control form-control-sm price-input"
                                            name="items[0][buy_price]" min="0" value="0" required />
                                    </div>
                                    <div class="col-md-1 d-flex align-items-end">
                                        <button type="button" class="btn btn-icon btn-sm btn-danger btn-remove-row"
                                            disabled><i class="ki-outline ki-trash"></i></button>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <button type="button" class="btn btn-sm btn-light-primary mb-7" id="btn_add_repeater">
                            <i class="ki-outline ki-plus fs-2"></i> Tambah Item Lainnya
                        </button>

                        <div class="d-flex flex-stack bg-success rounded-3 p-6 mb-7">
                            <div class="fs-4 fw-bold text-white">Grand Total Tagihan</div>
                            <div class="fs-2qx fw-bold text-white" id="grand_total_text">Rp 0</div>
                        </div>

                        <div class="text-center pt-10">
                            <button type="button" class="btn btn-light me-3" data-bs-dismiss="modal">Batal</button>
                            <button type="submit" class="btn btn-primary" id="btn-add-data">Simpan & Masukkan ke
                                Gudang</button>
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
                    <h2 class="fw-bold">Edit Info Transaksi</h2>
                    <div class="btn btn-icon btn-sm btn-active-icon-primary" data-bs-dismiss="modal"><i
                            class="ki-outline ki-cross fs-1"></i></div>
                </div>
                <div class="modal-body scroll-y mx-5 mx-xl-15 my-7">
                    <div class="alert alert-warning fs-7"><b>Catatan:</b> Untuk menjaga integritas stok, Anda hanya bisa
                        mengubah Tanggal dan Supplier.</div>
                    <form id="FormEditModalID" class="form">
                        @csrf @method('PUT')
                        <div id="EditRowModalBody"></div>
                        <div class="text-center pt-10">
                            <button type="submit" class="btn btn-primary w-100" id="btn-edit-data">Simpan
                                Perubahan</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <div class="modal fade" id="Modal_Detail_Data" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered mw-800px">
            <div class="modal-content">
                <div class="modal-header">
                    <h2 class="fw-bold">Detail Barang Masuk & Batch</h2>
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

                var table = $('.table-purchases').DataTable({
                    processing: true,
                    serverSide: true,
                    ajax: "{{ route('get-datapurchases') }}",
                    columns: [{
                            data: 'DT_RowIndex',
                            name: 'DT_RowIndex',
                            orderable: false,
                            searchable: false
                        },
                        {
                            data: 'purchase_date',
                            name: 'purchase_date'
                        },
                        {
                            data: 'reference',
                            name: 'reference_no'
                        },
                        {
                            data: 'supplier',
                            name: 'supplier.name'
                        },
                        {
                            data: 'total_items',
                            name: 'total_items',
                            searchable: false
                        },
                        {
                            data: 'total_cost',
                            name: 'total_cost'
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
                $('.select2-variant').select2({
                    dropdownParent: $('#Modal_Tambah_Data')
                });

                // LOGIKA REPEATER FORM
                let itemIndex = 1;
                let variantOptions = `{!! addslashes(
                    str_replace("\n", '', view('backend.inventory.purchases._variant_options', compact('variants'))->render()),
                ) !!}`;

                $('#btn_add_repeater').click(function() {
                    let newRow = `
            <div class="item-row border border-dashed border-gray-300 rounded p-5 mb-5 bg-light">
                <div class="row">
                    <div class="col-md-5">
                        <label class="required fs-7 fw-semibold mb-2">Pilih Varian Sepatu</label>
                        <select name="items[${itemIndex}][variant_id]" class="form-select form-select-sm select2-dynamic" required>
                            <option value="">-- Pilih --</option>
                            ${variantOptions}
                        </select>
                    </div>
                    <div class="col-md-2">
                        <label class="required fs-7 fw-semibold mb-2">Qty Masuk</label>
                        <input type="number" class="form-control form-control-sm qty-input" name="items[${itemIndex}][qty]" min="1" value="1" required/>
                    </div>
                    <div class="col-md-4">
                        <label class="required fs-7 fw-semibold mb-2">Harga Modal</label>
                        <input type="number" class="form-control form-control-sm price-input" name="items[${itemIndex}][buy_price]" min="0" value="0" required/>
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

                // Hapus Baris & Hitung Ulang
                $(document).on('click', '.btn-remove-row', function() {
                    $(this).closest('.item-row').remove();
                    calculateGrandTotal();
                });

                // AUTO-FILL HARGA MODAL SAAT VARIAN DIPILIH
                $(document).on('change', '.select2-variant, .select2-dynamic', function() {
                    // Gunakan .attr('data-price') agar lebih stabil dan terbaca oleh Select2
                    let defaultPrice = $(this).find(':selected').attr('data-price') || 0;

                    // Cari input 'Harga Modal' yang ada di baris (row) yang sama, lalu isi angkanya
                    $(this).closest('.row').find('.price-input').val(defaultPrice);

                    // Hitung ulang total
                    calculateGrandTotal();
                });
                $(document).on('input', '.qty-input, .price-input', function() {
                    calculateGrandTotal();
                });

                function calculateGrandTotal() {
                    let grandTotal = 0;
                    $('.item-row').each(function() {
                        let qty = parseFloat($(this).find('.qty-input').val()) || 0;
                        let price = parseFloat($(this).find('.price-input').val()) || 0;
                        grandTotal += (qty * price);
                    });
                    $('#grand_total_text').text(new Intl.NumberFormat('id-ID', {
                        style: 'currency',
                        currency: 'IDR',
                        minimumFractionDigits: 0
                    }).format(grandTotal));
                }

                // Buka Modal Tambah
                $('#btn_tambah_data').click(function() {
                    $('#FormTambahModalID')[0].reset();
                    $('[name="supplier_id"]').val(null).trigger('change');
                    $('.select2-variant').val(null).trigger('change');

                    // Hapus baris tambahan (sisakan baris 0)
                    $('#repeater_container').children('.item-row:not(:first)').remove();
                    calculateGrandTotal();

                    $('#Modal_Tambah_Data').modal('show');
                });

                // Submit Form Restock
                $('#FormTambahModalID').on('submit', function(e) {
                    e.preventDefault();
                    $('#btn-add-data').prop('disabled', true).text('Menyimpan...');

                    $.ajax({
                        url: "{{ route('purchases.store') }}",
                        method: "POST",
                        data: $(this).serialize(),
                        success: function(res) {
                            $('#Modal_Tambah_Data').modal('hide');
                            Swal.fire("Berhasil!", res.success, "success");
                            table.ajax.reload();
                        },
                        error: function(err) {
                            Swal.fire("Gagal", err.responseJSON?.error || "Cek kembali data anda.",
                                "error");
                        },
                        complete: function() {
                            $('#btn-add-data').prop('disabled', false).text(
                                'Simpan & Masukkan ke Gudang');
                        }
                    });
                });

                // TOMBOL LIHAT DETAIL & BATCH
                $('body').on('click', '.btn-detail', function() {
                    let id = $(this).data('id');
                    $('#DetailRowModalBody').html(
                        '<div class="text-center"><span class="spinner-border"></span></div>');
                    $('#Modal_Detail_Data').modal('show');
                    $.get("{{ url('admin') }}/purchases/" + id, function(res) {
                        $('#DetailRowModalBody').html(res.html);
                    });
                });

                // TOMBOL EDIT INFO
                $('body').on('click', '.btn-edit', function() {
                    let id = $(this).data('id');
                    $.get("{{ url('admin') }}/purchases/" + id + "/edit", function(res) {
                        $('#EditRowModalBody').html(res.html);
                        $('#EditRowModalBody select').select2({
                            dropdownParent: $('#Modal_Edit_Data')
                        });
                        $('#Modal_Edit_Data').modal('show');
                    });
                });

                // SUBMIT EDIT
                $('#FormEditModalID').on('submit', function(e) {
                    e.preventDefault();
                    let id = $('#edit_purchase_id').val();
                    $.ajax({
                        url: "{{ url('admin') }}/purchases/" + id,
                        method: "POST",
                        data: $(this).serialize(),
                        success: function(res) {
                            $('#Modal_Edit_Data').modal('hide');
                            table.ajax.reload();
                            Swal.fire("Berhasil!", res.success, "success");
                        }
                    });
                });

                // DELETE DENGAN VALIDASI STOK
                $('body').on('click', '.btn-delete', function() {
                    let id = $(this).data('id');
                    let ref = $(this).data('ref');
                    Swal.fire({
                        title: "Batalkan Transaksi?",
                        html: "Membatalkan PO <b>" + ref +
                            "</b> akan menarik (mengurangi) stok yang sudah masuk.<br><br><span class='text-danger'><b>Gagal jika ada barang yang sudah terjual.</b></span>",
                        icon: "warning",
                        showCancelButton: true,
                        confirmButtonText: "Ya, Tarik Stok!",
                        cancelButtonText: "Batal",
                        customClass: {
                            confirmButton: "btn btn-danger",
                            cancelButton: "btn btn-secondary"
                        }
                    }).then((result) => {
                        if (result.isConfirmed) {
                            $.ajax({
                                url: "{{ url('admin') }}/purchases/" + id,
                                type: "DELETE",
                                data: {
                                    _token: $('meta[name="csrf-token"]').attr('content')
                                },
                                success: function(res) {
                                    table.ajax.reload();
                                    Swal.fire("Dibatalkan!", res.success, "success");
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
