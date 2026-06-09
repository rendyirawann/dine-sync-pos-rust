@extends('backend.layout.app')
@section('title', 'Laporan Laba Rugi')
@section('content')

    <div id="kt_app_content" class="app-content flex-column-fluid mt-5">
        <div id="kt_app_content_container" class="app-container container-xxl">

            <div class="card card-flush mb-8">
                <div class="card-header pt-5">
                    <h3 class="card-title fw-bold text-gray-800 fs-2">Filter Laporan Laba Rugi (P&L)</h3>
                    <div class="card-toolbar">
                        <button type="button" class="btn btn-sm btn-danger" id="btn-print-pdf">
                            <i class="ki-outline ki-printer fs-2"></i> Cetak PDF
                        </button>
                    </div>
                </div>
                <div class="card-body">
                    <form id="form-filter">
                        <div class="row align-items-center">
                            <div class="col-md-4">
                                <label class="fs-6 fw-semibold mb-2">Pilih Rentang Waktu</label>
                                <input class="form-control form-control-solid form-control-lg" placeholder="Pilih Tanggal"
                                    id="kt_daterangepicker_1" />
                                <input type="hidden" name="start_date" id="start_date">
                                <input type="hidden" name="end_date" id="end_date">
                            </div>
                            <div class="col-md-2 mt-8">
                                <button type="submit" class="btn btn-primary btn-lg w-100"><i
                                        class="ki-outline ki-magnifier fs-2"></i> Hitung</button>
                            </div>
                        </div>
                    </form>
                </div>
            </div>

            <div id="result-container">
                <div class="card card-flush py-10 text-center">
                    <span class="spinner-border text-primary" role="status"></span>
                    <span class="text-muted mt-3">Menghitung kalkulasi Laba Rugi...</span>
                </div>
            </div>

        </div>
    </div>

    @push('scripts')
        <script>
            document.addEventListener('DOMContentLoaded', function() {

                // 1. Inisialisasi DateRangePicker
                var start = moment().startOf('month'); // Default bulan ini
                var end = moment().endOf('month');

                function cb(start, end) {
                    $('#kt_daterangepicker_1').val(start.format('DD/MM/YYYY') + ' - ' + end.format('DD/MM/YYYY'));
                    $('#start_date').val(start.format('YYYY-MM-DD'));
                    $('#end_date').val(end.format('YYYY-MM-DD'));
                }

                $('#kt_daterangepicker_1').daterangepicker({
                    startDate: start,
                    endDate: end,
                    ranges: {
                        'Hari Ini': [moment(), moment()],
                        'Bulan Ini': [moment().startOf('month'), moment().endOf('month')],
                        'Bulan Lalu': [moment().subtract(1, 'month').startOf('month'), moment().subtract(1,
                            'month').endOf('month')],
                        'Tahun Ini': [moment().startOf('year'), moment().endOf('year')]
                    }
                }, cb);
                cb(start, end);

                // 2. Fungsi Load Data via AJAX
                function loadData() {
                    $('#result-container').html(
                        '<div class="card card-flush py-10 text-center"><span class="spinner-border text-primary"></span></div>'
                        );

                    $.ajax({
                        url: "{{ route('reports.profit-loss.data') }}",
                        type: "GET",
                        data: {
                            start_date: $('#start_date').val(),
                            end_date: $('#end_date').val()
                        },
                        success: function(res) {
                            $('#result-container').html(res.html);
                        }
                    });
                }

                // Load pertama kali
                loadData();

                // 3. Saat form disubmit
                $('#form-filter').on('submit', function(e) {
                    e.preventDefault();
                    loadData();
                });

                // 4. Cetak PDF
                $('#btn-print-pdf').on('click', function() {
                    let start_date = $('#start_date').val();
                    let end_date = $('#end_date').val();
                    window.open(
                        `{{ route('reports.profit-loss.print') }}?start_date=${start_date}&end_date=${end_date}`,
                        '_blank');
                });
            });
        </script>
    @endpush
@endsection
