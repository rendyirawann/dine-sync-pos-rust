@extends('backend.layout.app')
@section('title', 'Laporan Menu Terlaris')
@section('content')

    <div id="kt_app_content" class="app-content flex-column-fluid mt-5">
        <div id="kt_app_content_container" class="app-container container-xxl">

            <div class="card card-flush mb-8">
                <div class="card-header pt-5">
                    <h3 class="card-title fw-bold text-gray-800 fs-2"><i
                            class="ki-outline ki-chart-simple-3 fs-1 me-2 text-primary"></i> Filter Laporan Menu</h3>
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
                                <label class="fs-6 fw-semibold mb-2">Filter Kategori</label>
                                <select name="category_id" id="category_id" class="form-select form-select-solid"
                                    data-control="select2">
                                    <option value="all">Semua Kategori</option>
                                    @foreach ($categories as $cat)
                                        <option value="{{ $cat->id }}">{{ $cat->name }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-5 d-flex align-items-end gap-2">
                                <button type="submit" class="btn btn-primary flex-grow-1"><i
                                        class="ki-outline ki-magnifier fs-2"></i> Tampilkan</button>
                                <button type="button" id="btn-print-pdf" class="btn btn-danger flex-grow-1"><i
                                        class="ki-outline ki-printer fs-2"></i> Cetak PDF</button>
                            </div>
                        </div>
                    </form>
                </div>
            </div>

            <div class="row g-5 mb-8">
                <div class="col-md-4">
                    <div class="bg-light-warning rounded p-6 border border-warning border-dashed d-flex align-items-center">
                        <i class="ki-outline ki-coffee fs-3x text-warning me-5"></i>
                        <div>
                            <span class="fs-6 fw-semibold text-warning d-block mb-1">Total Porsi Terjual</span>
                            <span class="fs-1 fw-bolder text-gray-900" id="summary-qty">0 Porsi</span>
                        </div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="bg-light-danger rounded p-6 border border-danger border-dashed d-flex align-items-center">
                        <i class="ki-outline ki-chart-line fs-3x text-danger me-5"></i>
                        <div>
                            <span class="fs-6 fw-semibold text-danger d-block mb-1">Total Modal (HPP)</span>
                            <span class="fs-1 fw-bolder text-gray-900" id="summary-hpp">Rp 0</span>
                        </div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="bg-light-success rounded p-6 border border-success border-dashed d-flex align-items-center">
                        <i class="ki-outline ki-wallet fs-3x text-success me-5"></i>
                        <div>
                            <span class="fs-6 fw-semibold text-success d-block mb-1">Total Omzet Menu</span>
                            <span class="fs-1 fw-bolder text-gray-900" id="summary-revenue">Rp 0</span>
                        </div>
                    </div>
                </div>
            </div>

            <div class="card card-flush">
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table align-middle table-row-dashed fs-6 gy-5" id="table-items">
                            <thead>
                                <tr class="text-start text-gray-500 fw-bold fs-7 text-uppercase gs-0">
                                    <th class="w-50px">Rank</th>
                                    <th>Nama Menu</th>
                                    <th>Kategori</th>
                                    <th class="text-center">Terjual (Porsi)</th>
                                    <th class="text-end">Total HPP</th>
                                    <th class="text-end">Total Omzet</th>
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
                var start = moment();
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
                        'Bulan Ini': [moment().startOf('month'), moment().endOf('month')]
                    },
                    locale: {
                        customRangeLabel: "Pilih Rentang"
                    }
                }, cb);

                cb(start, end);

                let table = $('#table-items').DataTable({
                    processing: true,
                    serverSide: true,
                    order: [
                        [3, 'desc']
                    ], // Urutkan berdasarkan kolom ke-4 (Total Qty) secara descending (Terlaris di atas)
                    ajax: {
                        url: "{{ route('reports.items.data') }}",
                        data: function(d) {
                            d.start_date = $('#start_date').val();
                            d.end_date = $('#end_date').val();
                            d.category_id = $('#category_id').val();
                        },
                        dataSrc: function(json) {
                             $('#summary-qty').text(json.totalItemsSold);
                             $('#summary-revenue').text(json.totalRevenue);
                             $('#summary-hpp').text(json.totalHpp);
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
                            data: 'menu_name',
                            name: 'menus.name'
                        },
                        {
                            data: 'category_name',
                            name: 'categories.name'
                        },
                        {
                            data: 'total_qty',
                            name: 'total_qty',
                            className: 'text-center'
                        },
                        {
                            data: 'total_hpp',
                            name: 'total_hpp',
                            className: 'text-end'
                        },
                        {
                            data: 'total_revenue',
                            name: 'total_revenue',
                            className: 'text-end'
                        }
                    ]
                });

                $('#form-filter').on('submit', function(e) {
                    e.preventDefault();
                    table.draw();
                });

                $('#btn-print-pdf').on('click', function() {
                    let start_date = $('#start_date').val();
                    let end_date = $('#end_date').val();
                    let category_id = $('#category_id').val();
                    let url =
                        `{{ route('reports.items.print') }}?start_date=${start_date}&end_date=${end_date}&category_id=${category_id}`;
                    window.open(url, '_blank');
                });
            });
        </script>
    @endpush
@endsection
