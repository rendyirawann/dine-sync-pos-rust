<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="author" content="Rendy Irawan">
    <title>Cetak QR Code - {{ $table->table_number }}</title>
    <link rel="shortcut icon" href="{{ asset('assets/media/logos/dine-sync-pos2.png') }}" />
    <style>
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            text-align: center;
            background-color: #f4f4f4;
            padding-top: 50px;
        }

        .card {
            background: #fff;
            padding: 40px;
            border-radius: 20px;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.1);
            display: inline-block;
            border: 4px solid #009ef7;
            /* Warna biru Metronic */
            max-width: 400px;
        }

        h1 {
            margin: 0 0 5px 0;
            font-size: 42px;
            color: #181c32;
            text-transform: uppercase;
        }

        p {
            margin: 0 0 30px 0;
            font-size: 16px;
            color: #a1a5b7;
        }

        .qr-wrapper {
            padding: 15px;
            background: #fff;
            border: 2px dashed #e4e6ef;
            border-radius: 15px;
            display: inline-block;
            margin-bottom: 20px;
        }

        .brand {
            font-weight: 800;
            font-size: 24px;
            color: #009ef7;
            letter-spacing: 2px;
        }

        /* Mode saat dicetak ke kertas */
        @media print {
            body {
                background: #fff;
                padding: 0;
            }

            .card {
                box-shadow: none;
                border-color: #000;
            }

            .brand {
                color: #000;
            }
        }
    </style>
</head>

<body onload="window.print()">

    <div class="card">
        <h1>{{ $table->table_number }}</h1>
        <p>Silakan scan QR Code di bawah ini<br>untuk memesan menu Anda.</p>

        <div class="qr-wrapper">
            {!! $qrcode !!}
        </div>

        <div class="brand">DINE SYNC</div>
    </div>

</body>

</html>
