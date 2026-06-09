<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <title>Worksheet Stock Opname</title>
    <style>
        @page {
            size: A4;
            margin: 15mm;
        }

        body {
            font-family: Arial, sans-serif;
            font-size: 12px;
        }

        .title {
            text-align: center;
            font-size: 18px;
            font-weight: bold;
            margin-bottom: 20px;
            text-transform: uppercase;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 20px;
        }

        th,
        td {
            border: 1px solid #000;
            padding: 8px;
            text-align: left;
        }

        th {
            background-color: #f0f0f0;
            text-align: center;
        }

        .text-center {
            text-align: center;
        }

        .signature {
            margin-top: 50px;
            width: 100%;
        }

        .signature td {
            border: none;
            text-align: center;
            width: 33%;
        }
    </style>
</head>

<body onload="window.print()">
    <div class="title">LEMBAR KERJA STOCK OPNAME (WORKSHEET)<br><span
            style="font-size: 12px; font-weight: normal;">Tanggal Cetak: {{ date('d/m/Y H:i') }}</span></div>
    <table>
        <thead>
            <tr>
                <th width="5%">No</th>
                <th width="15%">SKU</th>
                <th width="45%">Nama Varian Sepatu</th>
                <th width="15%">Stok Komputer</th>
                <th width="20%">Cek Fisik (Tulis)</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($variants as $index => $var)
                <tr>
                    <td class="text-center">{{ $index + 1 }}</td>
                    <td class="text-center">{{ $var->sku }}</td>
                    <td>{{ $var->product->brand }} {{ $var->product->model_name }} (Sz: {{ $var->size }} |
                        {{ $var->color }})</td>
                    <td class="text-center" style="color: #666;">{{ $var->stock }}</td>
                    <td></td>
                </tr>
            @endforeach
        </tbody>
    </table>
    <table class="signature">
        <tr>
            <td>Dihitung Oleh (Gudang)<br><br><br><br>(____________________)</td>
            <td>Diperiksa Oleh (Admin)<br><br><br><br>(____________________)</td>
            <td>Mengetahui (Manager)<br><br><br><br>(____________________)</td>
        </tr>
    </table>
</body>

</html>
