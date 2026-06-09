@extends('backend.layout.app')
@section('title', 'Laporan Penjualan')
@section('content')

    <div id="kt_app_content" class="app-content flex-column-fluid mt-5">
        <div id="kt_app_content_container" class="app-container container-xxl">

            <div class="card card-flush mb-8">
                <div class="card-header pt-5">
                    <h3 class="card-title fw-bold text-gray-800 fs-2">Filter Laporan Penjualan</h3>
                </div>
                <div class="card-body">
                    <form id="form-filter">
                        <div class="row g-5">
                            <div class="col-md-4">
                                <label class="fs-6 fw-semibold mb-2">Rentang Waktu</label>
                                <input class="form-control form-control-solid" placeholder="Pilih Tanggal"
                                    id="kt_daterangepicker" />
                                <input type="hidden" name="start_date" id="start_date">
                                <input type="hidden" name="end_date" id="end_date">
                            </div>
                            <div class="col-md-3">
                                <label class="fs-6 fw-semibold mb-2">Metode Pembayaran</label>
                                <select name="payment_method" id="payment_method" class="form-select form-select-solid">
                                    <option value="all">Semua Metode</option>
                                    <option value="cash">Tunai (Cash)</option>
                                    <option value="midtrans">QRIS / Transfer (Midtrans)</option>
                                </select>
                            </div>
                            <div class="col-md-5 d-flex align-items-end gap-2">
                                <button type="submit" class="btn btn-primary flex-grow-1"><i
                                        class="ki-outline ki-magnifier fs-2"></i> Tampilkan Data</button>
                                <button type="button" id="btn-print-pdf" class="btn btn-danger flex-grow-1"><i
                                        class="ki-outline ki-printer fs-2"></i> Cetak Laporan</button>
                            </div>
                        </div>
                    </form>
                </div>
            </div>

            <div class="row g-5 mb-8">
                <div class="col-md-3">
                    <div class="bg-light-primary rounded p-6 border border-primary border-dashed h-100">
                        <span class="fs-6 fw-semibold text-primary d-block mb-1">Total Nota Terjual</span>
                        <span class="fs-1 fw-bolder text-gray-900" id="summary-orders">0 Nota</span>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="bg-light-danger rounded p-6 border border-danger border-dashed h-100">
                        <span class="fs-6 fw-semibold text-danger d-block mb-1">Total Diskon / Promo</span>
                        <span class="fs-1 fw-bolder text-gray-900" id="summary-discount">Rp 0</span>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="bg-light-warning rounded p-6 border border-warning border-dashed h-100">
                        <span class="fs-6 fw-semibold text-warning d-block mb-1">Total Modal (HPP)</span>
                        <span class="fs-1 fw-bolder text-gray-900" id="summary-hpp">Rp 0</span>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="bg-light-success rounded p-6 border border-success border-dashed h-100">
                        <span class="fs-6 fw-semibold text-success d-block mb-1">Total Pendapatan Bersih</span>
                        <span class="fs-1 fw-bolder text-gray-900" id="summary-revenue">Rp 0</span>
                    </div>
                </div>
            </div>

            <div class="card card-flush">
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table align-middle table-row-dashed fs-6 gy-5" id="table-sales">
                            <thead>
                                <tr class="text-start text-gray-500 fw-bold fs-7 text-uppercase gs-0">
                                    <th>No</th>
                                    <th>Tanggal & Waktu</th>
                                    <th>Invoice</th>
                                    <th>Pelanggan / Meja</th>
                                    <th>Metode</th>
                                    <th class="text-end">Potongan Diskon</th>
                                    <th class="text-end">HPP</th>
                                    <th class="text-end">Total Belanja</th>
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

    @push('scripts')
        <script>
            $(document).ready(function() {
                // 1. Inisialisasi DateRangePicker Metronic
                var start = moment(); // Default hari ini
                var end = moment();

                function cb(start, end) {
                    $('#kt_daterangepicker').val(start.format('DD MMM YYYY') + ' - ' + end.format('DD MMM YYYY'));
                    $('#start_date').val(start.format('YYYY-MM-DD'));
                    $('#end_date').val(end.format('YYYY-MM-DD'));
                }

                $('#kt_daterangepicker').daterangepicker({
                    startDate: start,
                    endDate: end,
                    ranges: {
                        'Hari Ini': [moment(), moment()],
                        'Kemarin': [moment().subtract(1, 'days'), moment().subtract(1, 'days')],
                        '7 Hari Terakhir': [moment().subtract(6, 'days'), moment()],
                        '30 Hari Terakhir': [moment().subtract(29, 'days'), moment()],
                        'Bulan Ini': [moment().startOf('month'), moment().endOf('month')],
                        'Bulan Lalu': [moment().subtract(1, 'month').startOf('month'), moment().subtract(1,
                            'month').endOf('month')]
                    },
                    locale: {
                        customRangeLabel: "Pilih Rentang"
                    }
                }, cb);

                cb(start, end); // Panggil saat halaman pertama dimuat

                // 2. Inisialisasi DataTables
                let table = $('#table-sales').DataTable({
                    processing: true,
                    serverSide: true,
                    ajax: {
                        url: "{{ route('reports.sales.data') }}",
                        data: function(d) {
                            d.start_date = $('#start_date').val();
                            d.end_date = $('#end_date').val();
                            d.payment_method = $('#payment_method').val();
                        },
                        dataSrc: function(json) {
                            $('#summary-revenue').text(json.totalRevenue);
                            $('#summary-discount').text(json.totalDiscount);
                            $('#summary-hpp').text(json.totalHpp);
                            $('#summary-orders').text(json.totalOrders + ' Nota');
                            return json.data;
                        }
                    },
                    columns: [{
                            data: 'DT_RowIndex',
                            name: 'DT_RowIndex',
                            orderable: false,
                            searchable: false
                        },
                        {
                            data: 'date',
                            name: 'created_at'
                        },
                        {
                            data: 'invoice',
                            name: 'invoice_no'
                        },
                        {
                            data: 'customer',
                            name: 'customer_name'
                        },
                        {
                            data: 'payment_method',
                            name: 'payment_method'
                        },
                        {
                            data: 'discount',
                            name: 'discount_amount',
                            className: 'text-end'
                        },
                        {
                            data: 'total_hpp',
                            name: 'total_hpp',
                            className: 'text-end',
                            searchable: false
                        },
                        {
                            data: 'grand_total',
                            name: 'grand_total',
                            className: 'text-end'
                        }
                    ]
                });

                // 3. Tombol Filter Ditekan
                $('#form-filter').on('submit', function(e) {
                    e.preventDefault();
                    table.draw();
                });

                // 4. Tombol Cetak PDF Ditekan
                $('#btn-print-pdf').on('click', function() {
                    let start_date = $('#start_date').val();
                    let end_date = $('#end_date').val();
                    let payment_method = $('#payment_method').val();

                    let url =
                        `{{ route('reports.sales.print') }}?start_date=${start_date}&end_date=${end_date}&payment_method=${payment_method}`;
                    window.open(url, '_blank');
                });
            });
        </script>
    @endpush
@endsection
