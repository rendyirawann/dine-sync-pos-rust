<!DOCTYPE html>
<html>
<head>
    <title>Laporan Stock Opname</title>
    <style>
        body { font-family: sans-serif; font-size: 12px; }
        .header { text-align: center; margin-bottom: 20px; }
        .table { width: 100%; border-collapse: collapse; margin-top: 10px; }
        .table th, .table td { border: 1px solid #ddd; padding: 8px; text-align: left; }
        .table th { background-color: #f2f2f2; }
        .text-danger { color: red; }
        .text-success { color: green; }
        .footer { margin-top: 30px; text-align: right; }
    </style>
</head>
<body>
    <div class="header">
        <h2>LAPORAN STOCK OPNAME</h2>
        <p>Tanggal: {{ date('d F Y', strtotime($opname->date)) }}</p>
        <p>Petugas: {{ $opname->user->name }}</p>
    </div>

    <table class="table">
        <thead>
            <tr>
                <th>No</th>
                <th>Bahan Baku</th>
                <th>Stok Sistem</th>
                <th>Stok Fisik</th>
                <th>Selisih</th>
            </tr>
        </thead>
        <tbody>
            @foreach($opname->details as $detail)
            <tr>
                <td>{{ $loop->iteration }}</td>
                <td>{{ $detail->ingredient->name }} ({{ $detail->ingredient->unit }})</td>
                <td>{{ number_format($detail->system_qty, 2) }}</td>
                <td>{{ number_format($detail->physical_qty, 2) }}</td>
                <td class="{{ $detail->difference < 0 ? 'text-danger' : ($detail->difference > 0 ? 'text-success' : '') }}">
                    {{ ($detail->difference > 0 ? '+' : '') . number_format($detail->difference, 2) }}
                </td>
            </tr>
            @endforeach
        </tbody>
    </table>

    <div class="footer">
        <p>Dicetak pada: {{ date('d/m/Y H:i') }}</p>
        <br><br><br>
        <p>( {{ $opname->user->name }} )</p>
    </div>
</body>
</html>
