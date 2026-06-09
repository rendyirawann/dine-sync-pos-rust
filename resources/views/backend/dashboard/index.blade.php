@extends('backend.layout.app')
@section('title', 'Dashboard Analytics')
@section('content')

    <div id="kt_app_content" class="app-content flex-column-fluid mt-5">
        <div id="kt_app_content_container" class="app-container container-xxl">

            <div class="row g-5 g-xl-10 mb-xl-10">
                <div class="col-md-3">
                    <div class="card bg-light-primary border-0 shadow-sm h-100">
                        <div class="card-body p-6">
                            <div class="fs-6 fw-semibold text-primary mb-2">Total Omzet (Bulan Ini)</div>
                            <div class="fs-2hx fw-bold text-gray-800">Rp
                                {{ number_format($summary['revenue'], 0, ',', '.') }}</div>
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <a href="javascript:void(0)" id="btn-show-hpp-detail" class="card bg-light-danger border-0 shadow-sm h-100 card-xl-stretch hoverable">
                        <div class="card-body p-6">
                            <div class="fs-6 fw-semibold text-danger mb-2">Total HPP (Modal Bahan)</div>
                            <div class="fs-2hx fw-bold text-gray-800">Rp
                                {{ number_format($summary['hpp'], 0, ',', '.') }}</div>
                            <div class="text-danger fw-semibold fs-7 mt-1"><i class="ki-outline ki-information-5 text-danger fs-7"></i> Klik untuk rincian</div>
                        </div>
                    </a>
                </div>
                <div class="col-md-3">
                    <div class="card bg-light-warning border-0 shadow-sm h-100">
                        <div class="card-body p-6">
                            <div class="fs-6 fw-semibold text-warning mb-2">Operasional (Expenses)</div>
                            <div class="fs-2hx fw-bold text-gray-800">Rp
                                {{ number_format($summary['expense'], 0, ',', '.') }}</div>
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="card bg-light-success border-0 shadow-sm h-100">
                        <div class="card-body p-6">
                            <div class="fs-6 fw-semibold text-success mb-2">Laba Bersih (Net Profit)</div>
                            <div class="fs-2hx fw-bold text-gray-800">Rp
                                {{ number_format($summary['net_profit'], 0, ',', '.') }}</div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="row g-5 g-xl-10 mb-xl-10 mt-5">
                <div class="col-xl-8">
                    <div class="card shadow-sm h-100">
                        <div class="card-header pt-5 border-0">
                            <h3 class="card-title align-items-start flex-column">
                                <span class="card-label fw-bold fs-3 mb-1">Performa Restoran Harian</span>
                                <span class="text-muted fw-semibold fs-7">Omzet Aktual vs Target (Bulan Ini)</span>
                            </h3>
                        </div>
                        <div class="card-body pt-2 pb-0 ps-0">
                            <div id="kt_sales_chart" style="height: 350px"></div>
                        </div>
                    </div>
                </div>

                <div class="col-xl-4">
                    <div class="card shadow-sm h-100">
                        <div class="card-header pt-5 border-0">
                            <h3 class="card-title align-items-start flex-column">
                                <span class="card-label fw-bold fs-3 mb-1">Menu Paling Laku</span>
                                <span class="text-muted fw-semibold fs-7">Top 5 Penjualan Terbanyak Bulan Ini</span>
                            </h3>
                        </div>
                        <div class="card-body pt-5">
                            @forelse($topProducts as $top)
                                <div class="d-flex flex-stack mb-6">
                                    <div class="d-flex align-items-center">
                                        <div class="symbol py-2 symbol-40px me-4">
                                            <span
                                                class="symbol-label bg-light-primary text-primary fw-bold">{{ $loop->iteration }}</span>
                                        </div>
                                        <div class="d-flex flex-column justify-content-center">
                                            <a href="#"
                                                class="fs-6 text-gray-800 text-hover-primary fw-bold mb-1">{{ $top->menu->name ?? 'Menu Dihapus' }}</a>
                                            <div class="fw-semibold text-gray-400 fs-8">
                                                {{ $top->menu->category->name ?? '-' }}</div>
                                        </div>
                                    </div>
                                    <div class="d-flex flex-column align-items-end">
                                        <div class="fs-5 fw-bolder text-gray-800">{{ $top->total_qty }} <span
                                                class="fs-8 fw-normal text-muted">Porsi</span></div>
                                        <div class="fs-7 fw-bold text-success">Rp
                                            {{ number_format($top->total_revenue, 0, ',', '.') }}</div>
                                    </div>
                                </div>
                            @empty
                                <div class="text-center text-muted py-10">Belum ada data pesanan bulan ini.</div>
                            @endforelse
                        </div>
                    </div>
                </div>
            </div>

            <div class="row mt-5">
                <div class="col-12">
                    <div class="card shadow-sm">
                        <div class="card-header border-0 pt-5">
                            <h3 class="card-title align-items-start flex-column">
                                <span class="card-label fw-bold fs-3 mb-1 text-danger"><i
                                        class="ki-outline ki-warning-2 fs-2 text-danger me-2"></i> Daftar Menu Habis</span>
                                <span class="text-muted fw-semibold fs-7">Menu yang saat ini tidak tersedia untuk
                                    dipesan</span>
                            </h3>
                            <div class="card-toolbar">
                                <a href="#" class="btn btn-sm btn-light-primary">Kelola Menu</a>
                            </div>
                        </div>
                        <div class="card-body py-3">
                            <div class="table-responsive">
                                <table class="table table-row-dashed table-row-gray-300 align-middle gs-0 gy-4">
                                    <thead>
                                        <tr class="fw-bold text-muted bg-light">
                                            <th class="ps-4 rounded-start">Kategori</th>
                                            <th>Nama Menu</th>
                                            <th class="text-end pe-4 rounded-end">Status</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @forelse($unavailableMenus as $menu)
                                            <tr>
                                                <td class="ps-4">
                                                    <span
                                                        class="badge badge-light-dark">{{ $menu->category->name ?? '-' }}</span>
                                                </td>
                                                <td>
                                                    <div class="fw-bold text-gray-800">{{ $menu->name }}</div>
                                                </td>
                                                <td class="text-end pe-4">
                                                    <span class="text-danger fw-bold"><i
                                                            class="ki-outline ki-cross-circle fs-5 text-danger"></i>
                                                        Habis</span>
                                                </td>
                                            </tr>
                                        @empty
                                            <tr>
                                                <td colspan="3" class="text-center text-muted py-5">Semua menu saat ini
                                                    tersedia! 🎉</td>
                                            </tr>
                                        @endforelse
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

        </div>
    </div>

    <!-- Modal Rincian HPP -->
    <div class="modal fade" id="modal_hpp_detail" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered mw-800px">
            <div class="modal-content">
                <div class="modal-header">
                    <h2 class="fw-bold">Rincian HPP (Bulan Ini)</h2>
                    <div class="btn btn-icon btn-sm btn-active-icon-primary" data-bs-dismiss="modal">
                        <i class="ki-outline ki-cross fs-1"></i>
                    </div>
                </div>
                <div class="modal-body py-10 px-lg-17">
                    <div class="table-responsive">
                        <table class="table align-middle table-row-dashed fs-6 gy-5" id="table-hpp-detail">
                            <thead>
                                <tr class="text-start text-gray-400 fw-bold fs-7 text-uppercase gs-0">
                                    <th>No</th>
                                    <th>Invoice & Waktu</th>
                                    <th>Rincian Menu & Bahan</th>
                                    <th class="text-end">Total HPP</th>
                                </tr>
                            </thead>
                            <tbody class="fw-semibold text-gray-600"></tbody>
                        </table>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-light" data-bs-dismiss="modal">Tutup</button>
                </div>
            </div>
        </div>
    </div>

@endsection

@push('scripts')
    <script>
        $(document).ready(function() {
            var chartData = @json($chartData);
            var element = document.getElementById('kt_sales_chart');

            if (!element) return;

            var options = {
                series: [{
                    name: 'Omzet Aktual',
                    type: 'column',
                    data: chartData.sales
                }, {
                    name: 'Target Penjualan',
                    type: 'line',
                    data: chartData.targets
                }],
                chart: {
                    height: 350,
                    type: 'line',
                    fontFamily: 'Inter, sans-serif',
                    toolbar: {
                        show: false
                    }
                },
                stroke: {
                    width: [0, 4],
                    curve: 'smooth'
                },
                dataLabels: {
                    enabled: false
                },
                labels: chartData.categories,
                xaxis: {
                    type: 'category',
                    axisBorder: {
                        show: false
                    },
                    axisTicks: {
                        show: false
                    },
                    labels: {
                        style: {
                            colors: '#a1a5b7',
                            fontSize: '12px'
                        }
                    }
                },
                yaxis: {
                    labels: {
                        formatter: function(value) {
                            return "Rp " + (value || 0).toLocaleString('id-ID');
                        },
                        style: {
                            colors: '#a1a5b7',
                            fontSize: '12px'
                        }
                    }
                },
                colors: ['#009ef7', '#f1416c'],
                fill: {
                    opacity: [0.85, 1]
                },
                legend: {
                    position: 'top',
                    horizontalAlign: 'right'
                },
                grid: {
                    borderColor: '#eff2f5',
                    strokeDashArray: 4,
                    yaxis: {
                        lines: {
                            show: true
                        }
                    }
                }
            };

            var chart = new ApexCharts(element, options);
            chart.render();

            // JS untuk Modal Rincian HPP
            let hppTable = null;
            $('#btn-show-hpp-detail').click(function() {
                $('#modal_hpp_detail').modal('show');
                
                if (!hppTable) {
                    hppTable = $('#table-hpp-detail').DataTable({
                        processing: true,
                        serverSide: true,
                        ajax: "{{ route('dashboard.get-hpp-details') }}",
                        columns: [
                            {data: 'DT_RowIndex', name: 'DT_RowIndex', orderable: false, searchable: false},
                            {data: 'invoice_info', name: 'invoice_no'},
                            {data: 'menu_breakdown', name: 'menu_breakdown', orderable: false},
                            {data: 'total_hpp', name: 'total_hpp', className: 'text-end'}
                        ]
                    });
                } else {
                    hppTable.ajax.reload();
                }
            });
        });
    </script>
@endpush
