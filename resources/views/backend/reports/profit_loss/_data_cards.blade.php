<div class="row g-5 g-xl-8 mb-10">
    <div class="col-xl-4">
        <div class="card shadow-sm border-0 h-100">
            <div class="card-body d-flex align-items-center p-8">
                <div
                    class="d-flex align-items-center justify-content-center bg-light-primary rounded-circle w-65px h-65px me-5">
                    <svg width="32" height="32" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                        <path opacity="0.3"
                            d="M21 16H3C2.4 16 2 15.6 2 15V14C2 13.4 2.4 13 3 13H21C21.6 13 22 13.4 22 14V15C22 15.6 21.6 16 21 16ZM14 20V19C14 18.4 13.6 18 13 18H11C10.4 18 10 18.4 10 19V20C10 20.6 10.4 21 11 21H13C13.6 21 14 20.6 14 20Z"
                            fill="#009ef7" />
                        <path
                            d="M21 9H3C2.4 9 2 8.6 2 8V5C2 3.9 2.9 3 4 3H20C21.1 3 22 3.9 22 5V8C22 8.6 21.6 9 21 9ZM21 13H3C2.4 13 2 12.6 2 12V11C2 10.4 2.4 10 3 10H21C21.6 10 22 10.4 22 11V12C22 12.6 21.6 13 21 13Z"
                            fill="#009ef7" />
                    </svg>
                </div>
                <div class="d-flex flex-column">
                    <span class="fs-6 fw-semibold text-gray-500 mb-1">Total Omzet Penjualan</span>
                    <span class="fs-2hx fw-bold text-gray-800">Rp
                        {{ number_format($data['totalRevenue'], 0, ',', '.') }}</span>
                </div>
            </div>
        </div>
    </div>

    <div class="col-xl-4">
        <div class="card shadow-sm border-0 h-100">
            <div class="card-body d-flex align-items-center p-8">
                <div
                    class="d-flex align-items-center justify-content-center bg-light-warning rounded-circle w-65px h-65px me-5">
                    <svg width="32" height="32" viewBox="0 0 24 24" fill="none"
                        xmlns="http://www.w3.org/2000/svg">
                        <path opacity="0.3"
                            d="M21.25 18.525L13.05 21.825C12.35 22.125 11.65 22.125 10.95 21.825L2.75 18.525C1.75 18.125 1.75 16.525 2.75 16.125L10.95 12.825C11.65 12.525 12.35 12.525 13.05 12.825L21.25 16.125C22.25 16.525 22.25 18.125 21.25 18.525Z"
                            fill="#f1bc00" />
                        <path
                            d="M11.05 11.025L2.84998 7.725C1.84998 7.325 1.84998 5.725 2.84998 5.325L11.05 2.025C11.75 1.725 12.45 1.725 13.15 2.025L21.35 5.325C22.35 5.725 22.35 7.325 21.35 7.725L13.05 11.025C12.45 11.325 11.65 11.325 11.05 11.025Z"
                            fill="#f1bc00" />
                    </svg>
                </div>
                <div class="d-flex flex-column">
                    <span class="fs-6 fw-semibold text-gray-500 mb-1">Total Harga Pokok (HPP)</span>
                    <span class="fs-2hx fw-bold text-gray-800">Rp
                        {{ number_format($data['totalCogs'], 0, ',', '.') }}</span>
                </div>
            </div>
        </div>
    </div>

    <div class="col-xl-4">
        <div class="card shadow-sm border-0 h-100">
            <div class="card-body d-flex align-items-center p-8">
                <div
                    class="d-flex align-items-center justify-content-center bg-light-danger rounded-circle w-65px h-65px me-5">
                    <svg width="32" height="32" viewBox="0 0 24 24" fill="none"
                        xmlns="http://www.w3.org/2000/svg">
                        <rect opacity="0.3" x="2" y="2" width="20" height="20" rx="10" fill="#f1416c" />
                        <rect x="11" y="11" width="2" height="6" rx="1" fill="#f1416c" />
                        <rect x="15" y="7" width="2" height="10" rx="1" fill="#f1416c" />
                        <rect x="7" y="14" width="2" height="3" rx="1" fill="#f1416c" />
                    </svg>
                </div>
                <div class="d-flex flex-column">
                    <span class="fs-6 fw-semibold text-gray-500 mb-1">Total Biaya Operasional</span>
                    <span class="fs-2hx fw-bold text-gray-800">Rp
                        {{ number_format($data['totalExpense'], 0, ',', '.') }}</span>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="card shadow-sm border-0 mb-8">
    <div class="card-header border-bottom-0 pt-8 pb-3">
        <h3 class="card-title align-items-start flex-column">
            <span class="card-label fw-bold text-gray-800 fs-3">Pernyataan Laba Rugi (P&L Statement)</span>
            <span class="text-gray-500 mt-1 fw-semibold fs-7">Periode: {{ $data['start']->format('d M Y') }} s/d
                {{ $data['end']->format('d M Y') }}</span>
        </h3>
    </div>
    <div class="card-body pt-0 pb-8">
        <div class="table-responsive">
            <table class="table align-middle table-row-dashed fs-5 gy-5 mb-0">
                <tbody>
                    <tr>
                        <td class="text-gray-700 fw-semibold ps-0">Pendapatan Penjualan Kasir</td>
                        <td class="text-end fw-bold text-gray-800 pe-0">Rp
                            {{ number_format($data['totalRevenue'], 0, ',', '.') }}</td>
                    </tr>
                    <tr>
                        <td class="text-gray-700 fw-semibold ps-0">Harga Pokok Penjualan (HPP)</td>
                        <td class="text-end fw-bold text-gray-500 pe-0">(Rp
                            {{ number_format($data['totalCogs'], 0, ',', '.') }})</td>
                    </tr>
                    <tr class="bg-light">
                        <td class="fw-bolder text-gray-800 ps-4 py-4 rounded-start">Laba Kotor (Gross Profit)</td>
                        <td
                            class="text-end fw-bolder {{ $data['grossProfit'] >= 0 ? 'text-success' : 'text-danger' }} pe-4 py-4 rounded-end">
                            Rp {{ number_format($data['grossProfit'], 0, ',', '.') }}
                        </td>
                    </tr>
                    <tr>
                        <td class="text-gray-700 fw-semibold ps-0 pt-7">Biaya Operasional (Expenses)</td>
                        <td class="text-end fw-bold text-gray-500 pe-0 pt-7">(Rp
                            {{ number_format($data['totalExpense'], 0, ',', '.') }})</td>
                    </tr>
                    <tr>
                        <td class="fw-bolder fs-3 text-gray-900 ps-0 pb-0 pt-7 border-bottom-0">LABA BERSIH (NET PROFIT)
                        </td>
                        <td
                            class="text-end fw-bolder fs-2tx {{ $data['netProfit'] >= 0 ? 'text-success' : 'text-danger' }} pe-0 pb-0 pt-7 border-bottom-0">
                            Rp {{ number_format($data['netProfit'], 0, ',', '.') }}
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>
</div>
