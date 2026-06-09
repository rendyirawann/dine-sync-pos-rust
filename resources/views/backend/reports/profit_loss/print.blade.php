<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Pernyataan Laba Rugi</title>
    <style>
        @page {
            size: A4;
            margin: 25mm;
        }

        body {
            font-family: 'Helvetica Neue', Helvetica, Arial, sans-serif;
            font-size: 11pt;
            color: #333;
            line-height: 1.6;
        }

        .header {
            border-bottom: 2px solid #2c3e50;
            padding-bottom: 15px;
            margin-bottom: 30px;
            display: flex;
            justify-content: space-between;
            align-items: flex-end;
        }

        .company-name {
            font-size: 20pt;
            font-weight: bold;
            color: #2c3e50;
            margin: 0;
        }

        .report-title {
            font-size: 14pt;
            color: #7f8c8d;
            text-transform: uppercase;
            margin: 5px 0 0 0;
        }

        .period {
            font-size: 10pt;
            color: #7f8c8d;
            text-align: right;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 20px;
        }

        th,
        td {
            padding: 10px 0;
            border-bottom: 1px solid #ecf0f1;
            text-align: left;
        }

        .text-right {
            text-align: right;
        }

        .section-title {
            font-weight: bold;
            color: #2c3e50;
            background-color: #f8f9fa;
            padding: 10px;
            border-bottom: 2px solid #bdc3c7;
            margin-top: 20px;
        }

        .item-row td {
            padding-left: 15px;
            color: #555;
        }

        .total-row td {
            font-weight: bold;
            color: #2c3e50;
            border-top: 2px solid #bdc3c7;
            border-bottom: 2px solid #bdc3c7;
            padding: 12px 10px;
            background-color: #f8f9fa;
        }

        /* Highlight Grand Total */
        .grand-total td {
            font-weight: bold;
            font-size: 14pt;
            color: #fff;
            background-color: #2c3e50;
            padding: 15px 10px;
            border: none;
        }

        .grand-total.is-loss td {
            background-color: #e74c3c;
        }

        /* Merah elegan jika rugi */

        .text-danger {
            color: #e74c3c !important;
        }

        .footer {
            margin-top: 50px;
            text-align: center;
            font-size: 8pt;
            color: #95a5a6;
            border-top: 1px solid #ecf0f1;
            padding-top: 10px;
        }
    </style>
</head>

<body onload="window.print()">

    <div class="header">
        <div style="float: left;">
            <h1 class="company-name">SOLE SYNC POS</h1>
            <h2 class="report-title">Pernyataan Laba Rugi</h2>
        </div>
        <div class="period" style="float: right;">
            <strong>Periode Laporan:</strong><br>
            {{ $data['start']->format('d M Y') }} - {{ $data['end']->format('d M Y') }}
        </div>
        <div style="clear: both;"></div>
    </div>

    <table>
        <tr>
            <td colspan="2" class="section-title">PENDAPATAN</td>
        </tr>
        <tr class="item-row">
            <td>Pendapatan Penjualan (Omzet Kasir)</td>
            <td class="text-right">Rp {{ number_format($data['totalRevenue'], 0, ',', '.') }}</td>
        </tr>
        <tr class="item-row">
            <td>Harga Pokok Penjualan (HPP)</td>
            <td class="text-right text-danger">(Rp {{ number_format($data['totalCogs'], 0, ',', '.') }})</td>
        </tr>
        <tr class="total-row">
            <td>TOTAL LABA KOTOR (GROSS PROFIT)</td>
            <td class="text-right">Rp {{ number_format($data['grossProfit'], 0, ',', '.') }}</td>
        </tr>

        <tr>
            <td colspan="2" style="border:none; height: 15px;"></td>
        </tr>

        <tr>
            <td colspan="2" class="section-title">BIAYA OPERASIONAL</td>
        </tr>
        @if (count($data['expensesList']) > 0)
            @foreach ($data['expensesList'] as $exp)
                <tr class="item-row">
                    <td>{{ \Carbon\Carbon::parse($exp->expense_date)->format('d/m/Y') }} - {{ $exp->title }}</td>
                    <td class="text-right">(Rp {{ number_format($exp->amount, 0, ',', '.') }})</td>
                </tr>
            @endforeach
        @else
            <tr class="item-row">
                <td colspan="2" style="color: #7f8c8d; font-style: italic;">Tidak ada catatan pengeluaran
                    operasional.</td>
            </tr>
        @endif
        <tr class="total-row">
            <td>TOTAL BIAYA OPERASIONAL</td>
            <td class="text-right text-danger">(Rp {{ number_format($data['totalExpense'], 0, ',', '.') }})</td>
        </tr>

        <tr>
            <td colspan="2" style="border:none; height: 30px;"></td>
        </tr>

        <tr class="grand-total {{ $data['netProfit'] < 0 ? 'is-loss' : '' }}">
            <td>LABA BERSIH (NET PROFIT)</td>
            <td class="text-right">Rp {{ number_format($data['netProfit'], 0, ',', '.') }}</td>
        </tr>
    </table>

    <div class="footer">
        Dokumen ini dihasilkan secara otomatis oleh sistem akuntansi Sole Sync POS pada
        {{ \Carbon\Carbon::now()->format('d M Y H:i:s') }}
    </div>

</body>

</html>
