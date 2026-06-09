<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Struk #{{ $order->invoice_no }}</title>
    <style>
        /* CSS Khusus Printer Thermal (Kertas 58mm) */
        @page {
            margin: 0;
        }

        body {
            margin: 0;
            padding: 0;
            font-family: 'Courier New', Courier, monospace;
            font-size: 12px;
            color: #000;
            background-color: #fff;
        }

        .ticket {
            width: 58mm;
            max-width: 58mm;
            padding: 5px;
            margin: 0 auto;
        }

        table {
            width: 100%;
            border-collapse: collapse;
        }

        th,
        td {
            padding: 2px 0;
            vertical-align: top;
        }

        .text-center {
            text-align: center;
        }

        .text-right {
            text-align: right;
        }

        .text-left {
            text-align: left;
        }

        .bold {
            font-weight: bold;
        }

        .border-top {
            border-top: 1px dashed #000;
        }

        .border-bottom {
            border-bottom: 1px dashed #000;
        }

        .mb-1 {
            margin-bottom: 5px;
        }

        .mt-1 {
            margin-top: 5px;
        }

        /* Sembunyikan elemen background layar, hanya tampil saat print */
        @media print {
            body {
                margin: 0cm;
            }
        }
    </style>
</head>

<body onload="window.print()">

    <div class="ticket">
        <div class="text-center bold" style="font-size: 16px;">{{ $setting->store_name }}</div>
        <div class="text-center mb-1">
            {!! nl2br(e($setting->address)) !!}<br>
            Telp: {{ $setting->phone }}
        </div>

        <div class="border-bottom mb-1"></div>

        <table>
            <tr>
                <td width="30%">No</td>
                <td width="5%">:</td>
                <td>{{ $order->invoice_no }}</td>
            </tr>
            <tr>
                <td>Tgl</td>
                <td>:</td>
                <td>{{ \Carbon\Carbon::parse($order->created_at)->format('d/m/Y H:i') }}</td>
            </tr>
            <tr>
                <td>Meja</td>
                <td>:</td>
                <td>{{ $order->table->table_number ?? 'Walk-in' }}</td>
            </tr>
            @if ($order->customer_name)
                <tr>
                    <td>Plg</td>
                    <td>:</td>
                    <td>{{ $order->customer_name }}</td>
                </tr>
            @endif
        </table>

        <div class="border-bottom mt-1 mb-1"></div>

        <table>
            @foreach ($order->details as $item)
                <tr>
                    <td colspan="3" class="bold">
                        {{ $item->menu->name ?? 'Menu Dihapus' }}
                    </td>
                </tr>
                <tr>
                    <td width="20%">{{ $item->qty }} x</td>
                    <td width="40%">{{ number_format($item->price, 0, ',', '.') }}</td>
                    <td width="40%" class="text-right">{{ number_format($item->subtotal, 0, ',', '.') }}</td>
                </tr>
            @endforeach
        </table>

        <div class="border-top mt-1 mb-1"></div>

        <table>
            <tr>
                <td>Subtotal</td>
                <td class="text-right">Rp {{ number_format($order->subtotal, 0, ',', '.') }}</td>
            </tr>
            <tr>
                <td>Pajak ({{ $setting->tax_rate }}%)</td>
                <td class="text-right">Rp {{ number_format($order->tax, 0, ',', '.') }}</td>
            </tr>
            <tr>
                <td class="bold mt-1">TOTAL</td>
                <td class="text-right bold mt-1" style="font-size: 14px;">Rp
                    {{ number_format($order->grand_total, 0, ',', '.') }}</td>
            </tr>
            <tr>
                <td>Metode</td>
                <td class="text-right text-uppercase">{{ $order->payment_method ?? 'CASH' }}</td>
            </tr>
        </table>

        <div class="border-top mt-1 mb-1"></div>

        <div class="text-center mt-1" style="font-size: 11px;">
            Terima Kasih atas Kunjungan Anda!<br>
            Silakan datang kembali.
        </div>
        <br><br>
    </div>

</body>

</html>
