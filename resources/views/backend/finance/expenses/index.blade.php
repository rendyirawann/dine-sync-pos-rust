@extends('backend.layout.app')
@section('title', 'Operasional Harian')
@section('content')
    <div id="kt_app_content" class="app-content flex-column-fluid mt-5">
        <div id="kt_app_content_container" class="app-container container-xxl">

            <div class="card card-flush mb-10">
                <div class="card-header pt-5">
                    <h3 class="card-title fw-bold text-gray-800 fs-2">Pengaturan Operasional Hari Ini</h3>
                    <div class="card-toolbar">
                        <button type="button" class="btn btn-sm btn-primary" data-bs-toggle="modal"
                            data-bs-target="#Modal_Set_Budget">
                            <i class="ki-outline ki-setting-2 fs-2"></i> Atur Target & Budget
                        </button>
                    </div>
                </div>
                <div class="card-body pt-5">
                    <div class="row">
                        <div class="col-md-6 mb-5">
                            <div class="border border-success border-dashed rounded py-4 px-5 bg-light-success">
                                <span class="fs-6 text-success fw-bold">Target Penjualan Hari Ini</span>
                                <div class="fs-1 fw-bold text-dark mt-2">Rp
                                    {{ number_format($target->amount ?? 0, 0, ',', '.') }}</div>
                            </div>
                        </div>
                        <div class="col-md-6 mb-5">
                            <div class="border border-primary border-dashed rounded py-4 px-5 bg-light-primary">
                                <span class="fs-6 text-primary fw-bold">Batas Pengeluaran (Budget) Hari Ini</span>
                                <div class="fs-1 fw-bold text-dark mt-2">Rp
                                    {{ number_format($budget->amount ?? 0, 0, ',', '.') }}</div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="card card-flush">
                <div class="card-header align-items-center py-5 gap-2 gap-md-5">
                    <div class="card-title">
                        <h3 class="card-title fw-bold text-gray-800 fs-3">Riwayat Pengeluaran Kasir</h3>
                    </div>
                    <div class="card-toolbar">
                        <button type="button" class="btn btn-sm btn-danger" id="btn_tambah_data">
                            <i class="ki-outline ki-minus-square fs-2"></i> Catat Pengeluaran
                        </button>
                    </div>
                </div>
                <div class="card-body pt-0">
                    <table class="table align-middle table-row-dashed fs-6 gy-5 table-expenses" id="table-expenses">
                        <thead>
                            <tr class="text-start text-gray-400 fw-bold fs-7 text-uppercase gs-0">
                                <th class="w-10px pe-2">No</th>
                                <th class="min-w-100px">Tanggal</th>
                                <th class="min-w-200px">Judul / Deskripsi</th>
                                <th class="min-w-150px">Nominal (Rp)</th>
                                <th class="min-w-100px">Dicatat Oleh</th>
                                <th class="text-end min-w-100px">Aksi</th>
                            </tr>
                        </thead>
                        <tbody class="fw-semibold text-gray-600"></tbody>
                    </table>
                </div>
            </div>
            {{--  --}}
            <div class="card card-flush mt-10">
                <div class="card-header align-items-center py-5 gap-2 gap-md-5">
                    <div class="card-title">
                        <h3 class="card-title fw-bold text-gray-800 fs-3">Riwayat Target & Penggunaan Budget</h3>
                    </div>
                </div>
                <div class="card-body pt-0">
                    <table class="table align-middle table-row-dashed fs-6 gy-5 table-budgets" id="table-budgets">
                        <thead>
                            <tr class="text-start text-gray-400 fw-bold fs-7 text-uppercase gs-0">
                                <th class="w-10px pe-2">No</th>
                                <th class="min-w-150px">Tanggal</th>
                                <th class="min-w-250px">Performa Target (Income)</th>
                                <th class="min-w-250px">Penggunaan Budget (Expense)</th>
                            </tr>
                        </thead>
                        <tbody class="fw-semibold text-gray-600"></tbody>
                    </table>
                </div>
            </div>
            {{--  --}}
        </div>
    </div>

    <div class="modal fade" id="Modal_Set_Budget" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered mw-500px">
            <div class="modal-content">
                <div class="modal-header">
                    <h2 class="fw-bold">Atur Target & Budget Harian</h2>
                    <div class="btn btn-icon btn-sm btn-active-icon-primary" data-bs-dismiss="modal"><i
                            class="ki-outline ki-cross fs-1"></i></div>
                </div>
                <div class="modal-body scroll-y mx-5 mx-xl-15 my-7">
                    <form id="FormSetBudget" class="form">
                        @csrf
                        <div class="fv-row mb-7">
                            <label class="required fs-6 fw-semibold mb-2">Tanggal Berlakunya Target</label>
                            <input type="date" class="form-control" name="date"
                                value="{{ \Carbon\Carbon::today()->format('Y-m-d') }}" />
                        </div>
                        <div class="fv-row mb-7">
                            <label class="required fs-6 fw-semibold text-success mb-2">Target Penjualan (Rp)</label>
                            <input type="number" class="form-control border-success" name="target"
                                value="{{ $target->amount ?? 0 }}" min="0" />
                        </div>
                        <div class="fv-row mb-7">
                            <label class="required fs-6 fw-semibold text-primary mb-2">Batas Pengeluaran/Budget (Rp)</label>
                            <input type="number" class="form-control border-primary" name="budget"
                                value="{{ $budget->amount ?? 0 }}" min="0" />
                        </div>
                        <div class="text-center pt-15">
                            <button type="submit" class="btn btn-primary w-100" id="btn-save-budget">Simpan
                                Pengaturan</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <div class="modal fade" id="Modal_Tambah_Data" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered mw-650px">
            <div class="modal-content">
                <div class="modal-header">
                    <h2 class="fw-bold text-danger">Catat Pengeluaran Baru</h2>
                    <div class="btn btn-icon btn-sm btn-active-icon-primary" data-bs-dismiss="modal"><i
                            class="ki-outline ki-cross fs-1"></i></div>
                </div>
                <div class="modal-body scroll-y mx-5 mx-xl-15 my-7">
                    <form id="FormTambahModalID" class="form">
                        @csrf
                        <div class="fv-row mb-7">
                            <label class="required fs-6 fw-semibold mb-2">Tanggal Pengeluaran</label>
                            <input type="date" class="form-control" name="date"
                                value="{{ \Carbon\Carbon::today()->format('Y-m-d') }}" />
                        </div>
                        <div class="fv-row mb-7">
                            <label class="required fs-6 fw-semibold mb-2">Kategori Pengeluaran</label>
                            <input type="text" class="form-control" name="category"
                                placeholder="Contoh: Bahan Baku, Listrik, Gaji" />
                        </div>
                        <div class="fv-row mb-7">
                            <label class="required fs-6 fw-semibold mb-2">Nominal Uang Keluar</label>
                            <div class="input-group">
                                <span class="input-group-text bg-light-danger text-danger fw-bold border-danger">Rp</span>
                                <input type="number" class="form-control border-danger fw-bold" name="amount"
                                    value="0" min="0" />
                            </div>
                        </div>
                        <div class="fv-row mb-7">
                            <label class="fs-6 fw-semibold mb-2">Keterangan / Catatan (Opsional)</label>
                            <textarea class="form-control" name="notes" rows="3" placeholder="Masukkan catatan lebih lanjut"></textarea>
                        </div>
                        <div class="text-center pt-15">
                            <button type="submit" class="btn btn-danger w-100" id="btn-add-data">Simpan
                                Pengeluaran</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <div class="modal fade" id="Modal_Edit_Data" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered mw-650px">
            <div class="modal-content" id="edit-modal-content">
                <div class="modal-header">
                    <h2 class="fw-bold">Edit Pengeluaran</h2>
                    <div class="btn btn-icon btn-sm btn-active-icon-primary" data-bs-dismiss="modal"><i
                            class="ki-outline ki-cross fs-1"></i></div>
                </div>
                <div class="modal-body scroll-y mx-5 mx-xl-15 my-7">
                    <form id="FormEditModalID" class="form">
                        @csrf @method('PUT')
                        <div id="EditRowModalBody"></div>
                        <div class="text-center pt-15">
                            <button type="submit" class="btn btn-primary w-100" id="btn-edit-data">Simpan
                                Perubahan</button>
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
                    <h2 class="fw-bold">Detail Pengeluaran</h2>
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
                // Init DataTable
                var table = $('.table-expenses').DataTable({
                    processing: true,
                    serverSide: true,
                    ajax: "{{ route('get-dataexpenses') }}",
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
                            data: 'title',
                            name: 'category'
                        },
                        {
                            data: 'amount',
                            name: 'amount'
                        },
                        {
                            data: 'user',
                            name: 'user.name'
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

                var tableBudgets = $('.table-budgets').DataTable({
                    processing: true,
                    serverSide: true,
                    order: false,
                    ajax: "{{ route('get-databudgets') }}",
                    columns: [{
                            data: 'DT_RowIndex',
                            name: 'DT_RowIndex',
                            orderable: false,
                            searchable: false
                        },
                        {
                            data: 'date_formatted',
                            name: 'date'
                        },
                        {
                            data: 'target_info',
                            name: 'target'
                        },
                        {
                            data: 'budget_info',
                            name: 'budget'
                        }
                    ]
                });

                // 1. Submit Form Set Budget & Target
                $('#FormSetBudget').on('submit', function(e) {
                    e.preventDefault();
                    $('#btn-save-budget').prop('disabled', true).text('Menyimpan...');

                    $.ajax({
                        url: "{{ route('set-daily-budget') }}",
                        method: "POST",
                        data: $(this).serialize(),
                        success: function(res) {
                            $('#Modal_Set_Budget').modal('hide');
                            Swal.fire("Berhasil!", res.success, "success").then(() => location
                                .reload());
                        },
                        error: function(err) {
                            Swal.fire("Gagal", "Terjadi kesalahan", "error");
                            $('#btn-save-budget').prop('disabled', false).text('Simpan Pengaturan');
                        }
                    });
                });

                // 2. Submit Tambah Pengeluaran
                $('#btn_tambah_data').click(function() {
                    $('#FormTambahModalID')[0].reset();
                    $('#Modal_Tambah_Data').modal('show');
                });

                $('#FormTambahModalID').on('submit', function(e) {
                    e.preventDefault();
                    $('#btn-add-data').prop('disabled', true).text('Menyimpan...');

                    $.ajax({
                        url: "{{ route('expenses.store') }}",
                        method: "POST",
                        data: $(this).serialize(),
                        success: function(res) {
                            $('#Modal_Tambah_Data').modal('hide');
                            Swal.fire("Berhasil!", res.success, "success").then(() => location
                                .reload()); // Reload agar UI Sidebar & Budget ikut update
                        },
                        error: function(err) {
                            Swal.fire("Gagal", "Silakan isi form dengan benar.", "error");
                            $('#btn-add-data').prop('disabled', false).text('Simpan Pengeluaran');
                        }
                    });
                });

                // 3. Tampil Edit Form
                $('body').on('click', '.btn-edit', function() {
                    let id = $(this).data('id');
                    $.get("{{ url('admin') }}/expenses/" + id + "/edit", function(res) {
                        $('#EditRowModalBody').html(res.html);
                        $('#Modal_Edit_Data').modal('show');
                    });
                });

                // 4. Submit Edit Pengeluaran
                $('#FormEditModalID').on('submit', function(e) {
                    e.preventDefault();
                    let id = $('#edit_expense_id').val();
                    $('#btn-edit-data').prop('disabled', true).text('Menyimpan...');

                    $.ajax({
                        url: "{{ url('admin') }}/expenses/" + id,
                        method: "POST", // Spoofed using @method('PUT')
                        data: $(this).serialize(),
                        success: function(res) {
                            $('#Modal_Edit_Data').modal('hide');
                            Swal.fire("Berhasil!", res.success, "success").then(() => location
                                .reload());
                        },
                        error: function() {
                            Swal.fire("Gagal", "Silakan cek kembali form anda.", "error");
                            $('#btn-edit-data').prop('disabled', false).text('Simpan Perubahan');
                        }
                    });
                });

                // 5. Tampil Detail
                $('body').on('click', '.btn-detail', function() {
                    let id = $(this).data('id');
                    $('#DetailRowModalBody').html(
                        '<div class="text-center"><span class="spinner-border"></span></div>');
                    $('#Modal_Detail_Data').modal('show');

                    $.get("{{ url('admin') }}/expenses/" + id, function(res) {
                        $('#DetailRowModalBody').html(res.html);
                    });
                });

                // 6. Delete Pengeluaran
                $('body').on('click', '.btn-delete', function() {
                    let id = $(this).data('id');
                    let name = $(this).data('name');
                    Swal.fire({
                        title: "Hapus Catatan?",
                        text: "Yakin ingin menghapus pengeluaran '" + name + "'?",
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
                                url: "{{ url('admin') }}/expenses/" + id,
                                type: "DELETE",
                                data: {
                                    _token: $('meta[name="csrf-token"]').attr('content')
                                },
                                success: function(res) {
                                    Swal.fire("Terhapus!", res.success, "success").then(
                                        () => location.reload());
                                }
                            });
                        }
                    });
                });

            });
        </script>
    @endpush
@endsection
