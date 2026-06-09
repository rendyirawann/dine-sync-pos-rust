@extends('backend.layout.app')
@section('title', 'Kasir F&B')
@section('content')

    <div id="kt_app_content" class="app-content flex-column-fluid mt-5">
        <div id="kt_app_content_container" class="app-container container-xxl">
            <div class="d-flex justify-content-between align-items-center mb-6">
                <h1 class="text-gray-900 fw-bold fs-2"><i class="ki-outline ki-shop fs-1 me-2"></i> Peta Meja Restoran</h1>

                <div class="d-flex gap-4">
                    <div class="d-flex align-items-center">
                        <div class="w-15px h-15px bg-success rounded me-2"></div>
                        <span class="fw-semibold">Kosong <span
                                class="badge badge-light-success ms-1">{{ $emptyCount }}</span></span>
                    </div>
                    <div class="d-flex align-items-center">
                        <div class="w-15px h-15px bg-warning rounded me-2"></div>
                        <span class="fw-semibold">Belum Bayar <span
                                class="badge badge-light-warning ms-1">{{ $unpaidCount }}</span></span>
                    </div>
                    <div class="d-flex align-items-center">
                        <div class="w-15px h-15px bg-danger rounded me-2"></div>
                        <span class="fw-semibold">Lunas <span
                                class="badge badge-light-danger ms-1">{{ $paidCount }}</span></span>
                    </div>
                </div>
            </div>

            <div class="row g-5">
                @foreach ($tables as $table)
                    <div class="col-xl-2 col-lg-3 col-md-4 col-6">
                        @php
                            if ($table->status == 'available') {
                                $bgColor = 'bg-light-success';
                                $borderColor = 'border-success';
                                $textColor = 'text-success';
                                $statusText = 'KOSONG';
                            } else {
                                if (isset($table->payment_status) && $table->payment_status == 'unpaid') {
                                    $bgColor = 'bg-light-warning';
                                    $borderColor = 'border-warning';
                                    $textColor = 'text-warning';
                                    $statusText = 'BELUM BAYAR';
                                } else {
                                    $bgColor = 'bg-light-danger';
                                    $borderColor = 'border-danger';
                                    $textColor = 'text-danger';
                                    $statusText = 'TERISI (LUNAS)';
                                }
                            }
                        @endphp

                        <div class="card card-custom hover-elevate-up shadow-sm border border-2 {{ $borderColor }} {{ $bgColor }} cursor-pointer table-card"
                            data-id="{{ $table->id }}" data-status="{{ $table->status }}"
                            data-number="{{ $table->table_number }}">
                            <div class="card-body p-5 text-center">
                                <i class="ki-outline ki-abstract-14 fs-3x {{ $textColor }} mb-3"></i>
                                <div class="fs-4 fw-bolder text-gray-800">{{ $table->table_number }}</div>
                                <div class="fs-8 fw-bold {{ $textColor }} mt-1 text-uppercase">{{ $statusText }}
                                </div>
                                <div class="fs-8 text-muted mt-2"><i class="ki-outline ki-profile-user fs-7"></i> Kapasitas:
                                    {{ $table->capacity }}</div>
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>

        </div>
    </div>

    <div class="modal fade" id="Modal_Table_Action" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered mw-650px">
            <div class="modal-content">
                <div class="modal-header">
                    <h2 class="fw-bold" id="modal_title">Detail Meja</h2>
                    <div class="btn btn-icon btn-sm btn-active-icon-primary" data-bs-dismiss="modal"><i
                            class="ki-outline ki-cross fs-1"></i></div>
                </div>

                <div class="modal-body mx-5 my-3" id="Modal_Content_Area">
                </div>

            </div>
        </div>
    </div>

    <div class="modal fade" id="Modal_Payment_Susulan" tabindex="-1" aria-hidden="true" style="z-index: 1060;">
        <div class="modal-dialog modal-dialog-centered mw-500px">
            <div class="modal-content">
                <div class="modal-header">
                    <h2 class="fw-bold">Lanjutkan Pembayaran</h2>
                    <div class="btn btn-icon btn-sm btn-active-icon-primary" data-bs-dismiss="modal"><i
                            class="ki-outline ki-cross fs-1"></i></div>
                </div>
                <div class="modal-body mx-5 my-7">
                    <input type="hidden" id="susulan_order_id">
                    <input type="hidden" id="susulan_grand_total">

                    <div class="text-center mb-5">
                        <span class="fs-5 text-muted">Sisa Tagihan</span>
                        <div class="fs-1 fw-bold text-danger" id="susulan-total-text">Rp 0</div>
                    </div>

                    <div class="fv-row mb-7">
                        <label class="required fs-6 fw-semibold mb-2">Metode Pembayaran</label>
                        <select id="susulan_payment_method" class="form-select form-select-solid">
                            <option value="cash">💵 Tunai (Cash)</option>
                            <option value="midtrans">📱 QRIS / Transfer (Midtrans)</option>
                        </select>
                    </div>

                    <div id="susulan_cash_area" class="fv-row mb-7">
                        <label class="required fs-6 fw-semibold mb-2">Uang Diterima (Rp)</label>
                        <input type="number" class="form-control form-control-solid" id="susulan_pay_amount"
                            placeholder="0">
                        <div class="mt-3"><span class="fs-6 text-gray-700 fw-bold">Kembalian: <span
                                    id="susulan_change_amount" class="text-danger">Rp 0</span></span></div>
                    </div>

                    <div class="text-center pt-5 border-top mt-5">
                        <button type="button" class="btn btn-primary w-100" id="btn-process-susulan">Proses
                            Pembayaran</button>
                    </div>
                </div>
            </div>
        </div>
    </div>

    @push('scripts')
        <script src="https://app.sandbox.midtrans.com/snap/snap.js" data-client-key="{{ env('MIDTRANS_CLIENT_KEY') }}">
        </script>
        <script>
            $(document).ready(function() {
                // Format Rupiah
                const formatRupiah = (number) => new Intl.NumberFormat('id-ID', {
                    style: 'currency',
                    currency: 'IDR',
                    minimumFractionDigits: 0
                }).format(number);

                $('.table-card').on('click', function() {
                    let id = $(this).data('id');
                    let status = $(this).data('status');
                    let tableNumber = $(this).data('number');

                    $('#modal_title').text('Loading...');
                    $('#Modal_Content_Area').html(
                        '<div class="text-center py-10"><span class="spinner-border text-primary"></span></div>'
                    );
                    $('#Modal_Table_Action').modal('show');

                    $.ajax({
                        url: "{{ url('admin') }}/kasir/table-detail/" + id,
                        type: "GET",
                        success: function(res) {
                            if (res.status === 'available') {
                                $('#modal_title').text('Mulai Pesanan - ' + tableNumber);
                                let formHtml = `
                                <div class="text-center mb-5">
                                    <i class="ki-outline ki-shop fs-5x text-success mb-3"></i>
                                    <h3 class="fs-3 text-gray-800">Meja Ini Masih Kosong</h3>
                                </div>
                                
                                <div class="fv-row mb-5">
                                    <label class="required fs-6 fw-semibold mb-2">Tipe Pesanan</label>
                                    <select id="order_type" class="form-select form-select-solid">
                                        <option value="dine_in">🍽️ Dine In (Makan di Tempat)</option>
                                        <option value="take_away">🛍️ Take Away (Bawa Pulang)</option>
                                        <option value="reservation">📅 Reservasi (Booking)</option>
                                    </select>
                                </div>

                                <div class="fv-row mb-7">
                                    <label class="required fs-6 fw-semibold mb-2">Nama Pelanggan</label>
                                    <input type="text" class="form-control form-control-lg form-control-solid" id="customer_name" placeholder="Misal: Budi / Kak Rina">
                                </div>

                                <button type="button" class="btn btn-primary w-100 btn-lg" onclick="
                                    let cName = $('#customer_name').val().trim();
                                    let oType = $('#order_type').val(); // Ambil value tipe
                                    if(cName === '') {
                                        Swal.fire('Oops!', 'Nama Pelanggan wajib diisi sebelum memesan!', 'warning');
                                        return false;
                                    }
                                    $(this).prop('disabled', true).html('<span class=\\'spinner-border spinner-border-sm me-2\\'></span> Memuat Keranjang...'); 
                                    
                                    // Lempar nama & tipe pesanan ke URL
                                    window.location.href='{{ url('admin') }}/kasir/order/${id}?customer=' + encodeURIComponent(cName) + '&type=' + oType;
                                ">Buka Menu Pesanan</button>
                            `;
                                $('#Modal_Content_Area').html(formHtml);
                            } else if (res.status === 'occupied') {
                                $('#modal_title').text('Detail Pesanan - ' + tableNumber);
                                $('#Modal_Content_Area').html(res.html);
                            }
                        }
                    });
                });

                // ==========================================
                // LOGIKA PEMBAYARAN SUSULAN (DARI MODAL DETAIL)
                // ==========================================

                // Saat tombol bayar di modal detail di klik
                window.openPaymentSusulan = function(orderId, grandTotal) {
                    $('#Modal_Table_Action').modal('hide');
                    $('#susulan_order_id').val(orderId);
                    $('#susulan_grand_total').val(grandTotal);
                    $('#susulan-total-text').text(formatRupiah(grandTotal));
                    $('#susulan_pay_amount').val('');
                    $('#susulan_change_amount').text('Rp 0');
                    setTimeout(() => {
                        $('#Modal_Payment_Susulan').modal('show');
                    }, 400); // Jeda transisi modal
                }

                // Kalkulator kembalian susulan
                $('#susulan_pay_amount').on('keyup', function() {
                    let pay = parseInt($(this).val()) || 0;
                    let total = parseInt($('#susulan_grand_total').val()) || 0;
                    let change = pay - total;
                    $('#susulan_change_amount').text(formatRupiah(change < 0 ? 0 : change));
                });

                // Toggle Metode Susulan
                $('#susulan_payment_method').on('change', function() {
                    if ($(this).val() == 'midtrans') $('#susulan_cash_area').slideUp();
                    else $('#susulan_cash_area').slideDown();
                });

                // Submit Pembayaran Susulan
                $('#btn-process-susulan').on('click', function() {
                    let orderId = $('#susulan_order_id').val();
                    let payMethod = $('#susulan_payment_method').val();
                    let total = parseInt($('#susulan_grand_total').val());
                    let payAmt = parseInt($('#susulan_pay_amount').val()) || 0;

                    if (payMethod == 'cash' && payAmt < total) {
                        Swal.fire('Uang Kurang!', 'Nominal uang tidak cukup.', 'warning');
                        return;
                    }

                    $(this).prop('disabled', true).html(
                        '<span class="spinner-border spinner-border-sm"></span> Memproses...');

                    $.ajax({
                        url: "{{ route('kasir.pay-existing') }}",
                        method: "POST",
                        data: {
                            _token: '{{ csrf_token() }}',
                            order_id: orderId,
                            payment_method: payMethod
                        },
                        success: function(res) {
                            $('#Modal_Payment_Susulan').modal('hide');

                            // FUNGSI BARU: Pop-up Tanya Cetak Struk
                            const showPrintDialog = (orderId) => {
                                Swal.fire({
                                    title: 'Pembayaran Lunas!',
                                    text: 'Apakah Anda ingin mencetak struk?',
                                    icon: 'success',
                                    showCancelButton: true,
                                    confirmButtonColor: '#009ef7',
                                    cancelButtonColor: '#f1416c',
                                    confirmButtonText: '<i class="ki-outline ki-printer fs-4 me-2 text-white"></i> Cetak Struk',
                                    cancelButtonText: 'Selesai',
                                    allowOutsideClick: false
                                }).then((result) => {
                                    if (result.isConfirmed) {
                                        window.open('{{ url('admin') }}/kasir/print/' + orderId,
                                            '_blank', 'width=400,height=600');
                                    }
                                    location.reload(); // Refresh peta meja
                                });
                            };

                            if (res.type === 'cash') {
                                showPrintDialog(res.order_id);
                            } else if (res.type === 'midtrans') {
                                document.body.style.overflow = 'hidden';
                                snap.pay(res.snap_token, {
                                    onSuccess: function(result) {
                                        document.body.style.overflow = 'auto';
                                        Swal.fire({
                                            title: 'Memverifikasi...',
                                            allowOutsideClick: false,
                                            didOpen: () => {
                                                Swal.showLoading();
                                            }
                                        });
                                        setTimeout(() => {
                                            showPrintDialog(res.order_id);
                                        }, 3000);
                                    },
                                    onPending: function() {
                                        document.body.style.overflow = 'auto';
                                        Swal.fire('Menunggu',
                                            'Selesaikan pembayaran di HP Anda.',
                                            'info').then(() => {
                                            location.reload();
                                        });
                                    },
                                    onError: function() {
                                        document.body.style.overflow = 'auto';
                                        Swal.fire('Gagal', 'Pembayaran ditolak.',
                                            'error');
                                    },
                                    onClose: function() {
                                        document.body.style.overflow = 'auto';
                                        Swal.fire('Tertunda',
                                            'Popup tertutup. Meja tetap Kuning.',
                                            'warning').then(() => {
                                            location.reload();
                                        });
                                    }
                                });
                            }
                        }
                    });
                });

                // ==========================================
                // LOGIKA KOSONGKAN MEJA
                // ==========================================
                window.clearTable = function(tableId) {
                    $('#Modal_Table_Action').modal('hide'); // Tutup modal detail

                    Swal.fire({
                        title: 'Kosongkan Meja?',
                        text: "Pastikan pelanggan sudah selesai dan meninggalkan meja. Meja akan kembali berwarna Hijau.",
                        icon: 'warning',
                        showCancelButton: true,
                        confirmButtonColor: '#d33',
                        cancelButtonColor: '#3085d6',
                        confirmButtonText: 'Ya, Kosongkan!',
                        cancelButtonText: 'Batal'
                    }).then((result) => {
                        if (result.isConfirmed) {
                            // Tampilkan loading
                            Swal.fire({
                                title: 'Memproses...',
                                allowOutsideClick: false,
                                didOpen: () => {
                                    Swal.showLoading();
                                }
                            });

                            $.ajax({
                                url: "{{ url('admin') }}/kasir/clear-table/" + tableId,
                                method: "POST",
                                data: {
                                    _token: '{{ csrf_token() }}'
                                },
                                success: function(res) {
                                    if (res.success) {
                                        Swal.fire('Berhasil!', res.message, 'success').then(
                                            () => {
                                                location.reload();
                                            });
                                    }
                                },
                                error: function() {
                                    Swal.fire('Error', 'Gagal mengosongkan meja.', 'error');
                                }
                            });
                        } else {
                            // Jika dibatalkan, buka lagi modal detailnya
                            $('#Modal_Table_Action').modal('show');
                        }
                    });
                }
            });
        </script>
    @endpush
@endsection
