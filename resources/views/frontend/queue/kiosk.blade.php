<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <meta name="author" content="Rendy Irawan">
    <meta name="description" content="Kiosk Antrian DineSync POS">
    <title>Ambil Antrian - KIOSK</title>
    <link rel="shortcut icon" href="{{ asset('assets/media/logos/dine-sync-pos2.png') }}" />
    <link rel="stylesheet" href="https://fonts.googleapis.com/css?family=Poppins:300,400,500,600,700" />
    <link href="{{ asset('assets/plugins/global/plugins.bundle.css') }}" rel="stylesheet" type="text/css" />
    <link href="{{ asset('assets/css/style.bundle.css') }}" rel="stylesheet" type="text/css" />
    <style>
        body {
            background-color: #f5f8fa;
            /* Abu-abu muda bersih */
            height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            font-family: 'Poppins', sans-serif;
        }

        .kiosk-card {
            background: #ffffff;
            border-radius: 25px;
            padding: 60px;
            width: 100%;
            max-width: 700px;
            box-shadow: 0 15px 50px rgba(0, 0, 0, 0.08);
            border: 1px solid #eff2f5;
        }

        /* Pastikan semua teks di dalam card berwarna gelap */
        .kiosk-card h1,
        .kiosk-card h3,
        .kiosk-card label,
        .kiosk-card p,
        .kiosk-card input {
            color: #181c32 !important;
            /* Hitam pekat Metronic */
        }

        .kiosk-card .text-muted {
            color: #7e8299 !important;
            /* Abu-abu tua untuk keterangan */
        }

        .form-control-solid {
            background-color: #f5f8fa !important;
            border-color: #f5f8fa !important;
            color: #181c32 !important;
            font-weight: 600;
        }

        .form-control-solid:focus {
            background-color: #eef3f7 !important;
        }

        .pax-stepper {
            background-color: #f5f8fa;
            border-radius: 15px;
            padding: 10px;
            border: 2px solid #eff2f5;
        }

        .btn-pax {
            width: 60px;
            height: 60px;
            border-radius: 12px;
        }
    </style>
</head>

<body>

    <div class="kiosk-card text-center">
        <div class="mb-10">
            <i class="ki-outline ki-shop fs-5x text-primary shadow-sm p-5 bg-light-primary rounded-circle"></i>
        </div>

        <h1 class="fw-bolder fs-2qx mb-3">Selamat Datang</h1>
        <p class="fs-4 text-muted mb-15">Silakan isi data Anda untuk mengambil nomor antrian.</p>

        <form id="formKiosk">
            @csrf
            <div class="fv-row mb-10 text-start">
                <label class="required fs-3 fw-bold mb-3">Nama Lengkap Anda</label>
                <input type="text" class="form-control form-control-lg form-control-solid fs-2 py-5"
                    id="customer_name" placeholder="Ketik nama Anda di sini..." required>
            </div>

            <div class="fv-row mb-15 text-start">
                <label class="required fs-3 fw-bold mb-3">Jumlah Orang (Pax)</label>
                <div class="d-flex align-items-center justify-content-between pax-stepper">
                    <button type="button" class="btn btn-icon btn-danger btn-pax" onclick="updatePax(-1)">
                        <i class="ki-outline ki-minus fs-1 text-white"></i>
                    </button>
                    <input type="text"
                        class="form-control border-0 text-center fs-3hx fw-bolder bg-transparent text-dark"
                        id="pax" value="2" readonly>
                    <button type="button" class="btn btn-icon btn-success btn-pax" onclick="updatePax(1)">
                        <i class="ki-outline ki-plus fs-1 text-white"></i>
                    </button>
                </div>
                <div class="text-center mt-3 text-muted fs-6">Sistem akan otomatis menentukan kategori meja (A/B/C)
                    berdasarkan jumlah orang.</div>
            </div>

            <button type="button" id="btnSubmit" class="btn btn-primary w-100 fs-1 fw-bolder py-8 rounded-4 shadow">
                <i class="ki-outline ki-printer fs-1 me-3 text-white"></i> CETAK NOMOR ANTRIAN
            </button>
        </form>
    </div>

    <script src="{{ asset('assets/plugins/global/plugins.bundle.js') }}"></script>
    <script>
        function updatePax(val) {
            let current = parseInt($('#pax').val());
            let result = current + val;
            if (result >= 1) $('#pax').val(result);
        }

        $('#btnSubmit').click(function() {
            let name = $('#customer_name').val().trim();
            let pax = $('#pax').val();
            if (!name) return Swal.fire({
                text: 'Nama wajib diisi!',
                icon: 'warning',
                buttonsStyling: false,
                confirmButtonText: "Ok, mengerti",
                customClass: {
                    confirmButton: "btn btn-primary"
                }
            });

            let btn = $(this);
            btn.prop('disabled', true).html(
                '<span class="spinner-border spinner-border-sm me-3"></span>Mencetak...');

            $.ajax({
                url: "{{ route('frontend.kiosk.take') }}",
                method: "POST",
                data: {
                    _token: '{{ csrf_token() }}',
                    customer_name: name,
                    pax: pax
                },
                success: function(res) {
                    Swal.fire({
                        title: res.queue_number,
                        text: `Antrian berhasil diambil atas nama ${res.customer_name}. Kategori meja Anda ditentukan berdasarkan jumlah orang (${pax}). Silakan tunggu panggilan.`,
                        icon: 'success',
                        confirmButtonText: 'Selesai',
                        buttonsStyling: false,
                        customClass: {
                            title: 'text-primary fs-5x fw-bolder mb-5',
                            confirmButton: "btn btn-primary btn-lg w-100 fs-3 py-5"
                        }
                    }).then(() => {
                        location.reload();
                    });
                },
                error: function() {
                    btn.prop('disabled', false).html(
                        '<i class="ki-outline ki-printer fs-1 me-3 text-white"></i> CETAK NOMOR ANTRIAN'
                        );
                    Swal.fire('Error', 'Gagal mengambil antrian sistem.', 'error');
                }
            });
        });
    </script>
</body>

</html>
