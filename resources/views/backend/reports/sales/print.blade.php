<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Cetak Laporan Penjualan</title>
    <style>
        /* CSS Khusus Format A4 Portrait */
        @page {
            size: A4;
            margin: 15mm;
        }

        body {
            font-family: Arial, Helvetica, sans-serif;
            font-size: 12px;
            color: #333;
        }

        .header {
            text-align: center;
            margin-bottom: 20px;
            border-bottom: 2px solid #000;
            padding-bottom: 10px;
        }

        .title {
            font-size: 20px;
            font-weight: bold;
            margin-bottom: 5px;
            text-transform: uppercase;
        }

        .subtitle {
            font-size: 12px;
            color: #555;
        }

        .filter-info {
            margin-bottom: 15px;
        }

        .filter-info table {
            width: 50%;
            border: none;
        }

        .filter-info td {
            padding: 3px 0;
            font-size: 12px;
        }

        .table-data {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 20px;
        }

        .table-data th,
        .table-data td {
            border: 1px solid #ddd;
            padding: 8px;
            font-size: 11px;
        }

        .table-data th {
            background-color: #f4f4f4;
            font-weight: bold;
            text-align: left;
        }

        .text-center {
            text-align: center;
        }

        .text-right {
            text-align: right;
        }

        .bold {
            font-weight: bold;
        }

        .text-danger {
            color: #d9534f;
        }

        .summary-box {
            float: right;
            width: 40%;
            padding: 15px;
            background-color: #f9f9f9;
            border: 1px solid #ddd;
            border-radius: 5px;
        }

        @media print {
            body {
                margin: 0;
            }

            .no-print {
                display: none;
            }
        }
    </style>
</head>

<body onload="window.print()">

    <div class="header">
        <div class="title">{{ $setting->store_name ?? 'DineSync POS' }}</div>
        <div class="subtitle">LAPORAN PENJUALAN KESELURUHAN (F&B)</div>
    </div>

    <div class="filter-info">
        <table>
            <tr>
                <td width="35%" class="bold">Rentang Tanggal</td>
                <td width="5%">:</td>
                <td>{{ $filterDate }}</td>
            </tr>
            <tr>
                <td class="bold">Metode Pembayaran</td>
                <td>:</td>
                <td>{{ $filterPayment }}</td>
            </tr>
            <tr>
                <td class="bold">Waktu Cetak</td>
                <td>:</td>
                <td>{{ \Carbon\Carbon::now()->translatedFormat('d F Y H:i:s') }}</td>
            </tr>
        </table>
    </div>

    <table class="table-data">
        <thead>
            <tr>
                <th width="5%" class="text-center">No</th>
                <th width="15%">Tanggal</th>
                <th width="15%">No. Invoice</th>
                <th width="20%">Pelanggan / Meja</th>
                <th width="10%" class="text-center">Metode</th>
                <th width="15%" class="text-right">Promo / Diskon</th>
                <th width="20%" class="text-right">Total Belanja</th>
            </tr>
        </thead>
        <tbody>
            @forelse ($orders as $index => $order)
                <tr>
                    <td class="text-center">{{ $index + 1 }}</td>
                    <td>{{ \Carbon\Carbon::parse($order->created_at)->format('d/m/Y H:i') }}</td>
                    <td class="bold">{{ $order->invoice_no }}</td>
                    <td>
                        {{ $order->customer_name }} <br>
                        <i
                            style="color: #666; font-size: 10px;">{{ $order->table ? 'Meja ' . $order->table->table_number : 'Walk-in' }}</i>
                    </td>
                    <td class="text-center" style="text-transform: uppercase;">{{ $order->payment_method }}</td>
                    <td class="text-right">
                        @if ($order->discount_amount > 0)
                            <span class="text-danger">- Rp
                                {{ number_format($order->discount_amount, 0, ',', '.') }}</span><br>
                            <i style="font-size: 9px; color:#666;">{{ $order->promo->name ?? 'Promo Item' }}</i>
                        @else
                            -
                        @endif
                    </td>
                    <td class="text-right bold">Rp {{ number_format($order->grand_total, 0, ',', '.') }}</td>
                </tr>
            @empty
                <tr>
                    <td colspan="7" class="text-center" style="padding: 20px;">Tidak ada data penjualan untuk filter
                        ini.</td>
                </tr>
            @endforelse
        </tbody>
    </table>

    <div class="summary-box">
        <table style="width: 100%; border: none;">
            <tr>
                <td style="border: none;">Total Transaksi (Nota)</td>
                <td style="border: none;" class="text-right bold">{{ number_format($totalOrders, 0, ',', '.') }}</td>
            </tr>
            <tr>
                <td style="border: none; padding-top: 5px;">Total Diskon Diberikan</td>
                <td style="border: none; padding-top: 5px; color: #d9534f;" class="text-right bold">- Rp
                    {{ number_format($totalDiscount, 0, ',', '.') }}</td>
            </tr>
            <tr>
                <td style="border: none; padding-top: 10px;" class="bold">PENDAPATAN BERSIH</td>
                <td style="border: none; padding-top: 10px; font-size: 16px;" class="text-right bold text-success">
                    Rp {{ number_format($totalRevenue, 0, ',', '.') }}
                </td>
            </tr>
        </table>
    </div>

    <div style="clear: both;"></div>

</body>

</html>
