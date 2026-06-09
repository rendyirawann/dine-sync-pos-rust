<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Laporan Menu Terlaris</title>
    <style>
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
            font-size: 12px;
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

        .text-success {
            color: #28a745;
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
        }
    </style>
</head>

<body onload="window.print()">

    <div class="header">
        <div class="title">{{ $setting->store_name ?? 'DineSync POS' }}</div>
        <div class="subtitle">LAPORAN MENU TERLARIS & KINERJA PRODUK</div>
    </div>

    <div class="filter-info">
        <table>
            <tr>
                <td width="35%" class="bold">Rentang Tanggal</td>
                <td width="5%">:</td>
                <td>{{ $filterDate }}</td>
            </tr>
            <tr>
                <td class="bold">Kategori Menu</td>
                <td>:</td>
                <td>{{ $category }}</td>
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
                <th width="10%" class="text-center">Peringkat</th>
                <th width="35%">Nama Menu</th>
                <th width="20%">Kategori</th>
                <th width="15%" class="text-center">Total Terjual</th>
                <th width="20%" class="text-right">Total Omzet</th>
            </tr>
        </thead>
        <tbody>
            @forelse ($items as $index => $item)
                <tr>
                    <td class="text-center bold">#{{ $index + 1 }}</td>
                    <td class="bold">
                        {{ $item->menu_name }}
                        @if ($item->discount_percent > 0)
                            <span class="text-success" style="font-size: 10px; margin-left: 5px;">(Diskon
                                {{ $item->discount_percent }}%)</span>
                        @endif
                    </td>
                    <td>{{ $item->category_name ?? '-' }}</td>
                    <td class="text-center bold text-success">{{ number_format($item->total_qty, 0, ',', '.') }} Pcs
                    </td>
                    <td class="text-right">Rp {{ number_format($item->total_revenue, 0, ',', '.') }}</td>
                </tr>
            @empty
                <tr>
                    <td colspan="5" class="text-center" style="padding: 20px;">Tidak ada menu yang terjual pada
                        periode ini.</td>
                </tr>
            @endforelse
        </tbody>
    </table>

    <div class="summary-box">
        <table style="width: 100%; border: none;">
            <tr>
                <td style="border: none;">Total Porsi Terjual</td>
                <td style="border: none;" class="text-right bold">{{ number_format($totalItemsSold, 0, ',', '.') }} Pcs
                </td>
            </tr>
            <tr>
                <td style="border: none; padding-top: 10px;" class="bold">TOTAL PENDAPATAN ITEM</td>
                <td style="border: none; padding-top: 10px; font-size: 16px;" class="text-right bold text-success">
                    Rp {{ number_format($totalRevenue, 0, ',', '.') }}
                </td>
            </tr>
        </table>
    </div>

    <div style="clear: both;"></div>

</body>

</html>
