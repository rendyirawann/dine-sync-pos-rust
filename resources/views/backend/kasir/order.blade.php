@extends('backend.layout.app')
@section('title', 'Pilih Menu F&B')
@section('content')

    <div id="kt_app_content" class="app-content flex-column-fluid mt-5">
        <div id="kt_app_content_container" class="app-container container-xxl">

            <div class="d-flex align-items-center justify-content-between bg-white rounded p-5 mb-7 shadow-sm">
                <div class="d-flex align-items-center">
                    <a href="{{ route('kasir.index') }}" class="btn btn-icon btn-light me-4"><i
                            class="ki-outline ki-arrow-left fs-2"></i></a>
                    <div>
                        <h2 class="mb-1">Pesanan Baru: <span class="text-primary">{{ $table->table_number }}</span></h2>
                        <span class="text-muted fw-bold">Pelanggan: {{ $customer_name }}</span>
                        <span
                            class="badge badge-light-primary ms-2 text-uppercase">{{ str_replace('_', ' ', $order_type) }}</span>
                    </div>
                </div>
                <input type="hidden" id="table_id" value="{{ $table->id }}">
                <input type="hidden" id="customer_name" value="{{ $customer_name }}">
                <input type="hidden" id="order_type" value="{{ $order_type }}">
            </div>

            <div class="d-flex flex-column flex-xl-row">
                <div class="d-flex flex-row-fluid me-xl-9 mb-10 mb-xl-0">
                    <div class="card card-flush card-p-0 bg-transparent border-0 w-100">
                        <div class="card-body">
                            <ul class="nav nav-pills d-flex nav-pills-custom gap-3 mb-6"
                                style="overflow-x: auto; flex-wrap: nowrap;">
                                <li class="nav-item mb-3 me-0">
                                    <a class="nav-link nav-link-border-solid btn btn-outline btn-flex btn-active-color-primary flex-column flex-stack pt-9 pb-7 page-bg show active"
                                        data-bs-toggle="pill" href="#tab_semua" style="width: 138px;height: 140px">
                                        <div class="nav-icon mb-3"><i class="ki-outline ki-shop fs-1 text-primary"></i>
                                        </div>
                                        <div><span class="text-gray-800 fw-bold fs-4 d-block">Semua</span></div>
                                    </a>
                                </li>
                                @foreach ($categories as $cat)
                                    <li class="nav-item mb-3 me-0">
                                        <a class="nav-link nav-link-border-solid btn btn-outline btn-flex btn-active-color-primary flex-column flex-stack pt-9 pb-7 page-bg"
                                            data-bs-toggle="pill" href="#tab_cat_{{ $cat->id }}"
                                            style="width: 138px;height: 140px">
                                            <div class="nav-icon mb-3"><i class="ki-outline ki-coffee fs-1"></i></div>
                                            <div><span class="text-gray-800 fw-bold fs-4 d-block">{{ $cat->name }}</span>
                                            </div>
                                        </a>
                                    </li>
                                @endforeach
                            </ul>

                            <div class="tab-content">
                                <div class="tab-pane fade show active" id="tab_semua">
                                    <div class="d-flex flex-wrap d-grid gap-5 gap-xxl-9">
                                        @foreach ($menus as $menu)
                                            @include('backend.kasir._menu_card', ['menu' => $menu])
                                        @endforeach
                                    </div>
                                </div>

                                @foreach ($categories as $cat)
                                    <div class="tab-pane fade" id="tab_cat_{{ $cat->id }}">
                                        <div class="d-flex flex-wrap d-grid gap-5 gap-xxl-9">
                                            @foreach ($menus->where('category_id', $cat->id) as $menu)
                                                @include('backend.kasir._menu_card', ['menu' => $menu])
                                            @endforeach
                                            @if ($menus->where('category_id', $cat->id)->count() == 0)
                                                <div class="text-muted fst-italic">Kategori ini belum ada menu.</div>
                                            @endif
                                        </div>
                                    </div>
                                @endforeach
                            </div>
                        </div>
                    </div>
                </div>

                <div class="flex-row-auto w-xl-450px">
                    <div class="card card-flush bg-body shadow-sm">
                        <div class="card-header pt-5">
                            <h3 class="card-title fw-bold text-gray-800 fs-2qx">Bill Pesanan</h3>
                        </div>
                        <div class="card-body pt-0">

                            <div class="table-responsive mb-8"
                                style="max-height: 400px; overflow-y: auto; overflow-x: hidden;">
                                <table class="table align-middle gs-0 gy-4 my-0">
                                    <tbody id="cart-items">
                                        <tr>
                                            <td colspan="3" class="text-center text-muted fst-italic py-10">Keranjang
                                                masih kosong.</td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>

                            <div class="fv-row mb-6">
                                <label class="fs-6 fw-bold mb-2 text-gray-800"><i
                                        class="ki-outline ki-discount fs-3 text-danger me-1"></i> Gunakan Promo /
                                    Diskon</label>
                                <select id="promo_select" class="form-select form-select-solid">
                                    <option value="">-- Tidak Pakai Promo --</option>
                                    @foreach ($promos as $promo)
                                        <option value="{{ $promo->id }}" data-type="{{ $promo->discount_type }}"
                                            data-value="{{ $promo->discount_value }}">
                                            {{ $promo->name }}
                                            ({{ $promo->discount_type == 'percentage' ? $promo->discount_value . '%' : 'Rp ' . number_format($promo->discount_value, 0, ',', '.') }})
                                        </option>
                                    @endforeach
                                </select>
                            </div>

                            <div class="d-flex flex-stack bg-light-primary rounded-3 p-6 mb-4">
                                <div class="fs-6 fw-bold text-primary">Subtotal</div>
                                <div class="fs-5 fw-bold text-primary text-end" id="summary-subtotal">Rp 0</div>
                            </div>

                            <div class="d-flex flex-stack bg-light-danger rounded-3 p-6 mb-4 d-none" id="discount-area">
                                <div class="fs-6 fw-bold text-danger">Diskon Promo</div>
                                <div class="fs-5 fw-bold text-danger text-end" id="summary-discount">- Rp 0</div>
                            </div>

                            <div class="d-flex flex-stack bg-light-warning rounded-3 p-6 mb-7">
                                <div class="fs-6 fw-bold text-warning" id="tax-label">Pajak
                                    ({{ $setting->tax_rate ?? 0 }}%)
                                </div>
                                <div class="fs-5 fw-bold text-warning text-end" id="summary-tax">Rp 0</div>
                            </div>
                            <div class="d-flex flex-stack bg-success rounded-3 p-6 mb-7 shadow-sm">
                                <div class="fs-6 fw-bold text-white"><span class="d-block fs-2qx lh-1 mt-2">Grand
                                        Total</span></div>
                                <div class="fs-6 fw-bold text-white text-end"><span class="d-block fs-2qx lh-1 mt-2"
                                        id="summary-total">Rp 0</span></div>
                            </div>

                            <button type="button"
                                onclick="$('#Modal_Payment').modal('show'); $('#modal-grand-total').text(formatRupiah(grandTotalRaw));"
                                id="btn-checkout" class="btn btn-primary fs-2 fw-bold w-100 py-4" disabled>
                                <i class="ki-outline ki-wallet fs-2 me-2"></i> Bayar & Proses
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="modal fade" id="Modal_Payment" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered mw-500px">
            <div class="modal-content">
                <div class="modal-header">
                    <h2 class="fw-bold">Pembayaran Pesanan</h2>
                    <div class="btn btn-icon btn-sm btn-active-icon-primary" data-bs-dismiss="modal"><i
                            class="ki-outline ki-cross fs-1"></i></div>
                </div>
                <div class="modal-body mx-5 my-7">
                    <div class="text-center mb-5">
                        <span class="fs-5 text-muted">Total Tagihan</span>
                        <div class="fs-1 fw-bold text-success" id="modal-grand-total">Rp 0</div>
                    </div>

                    <div class="fv-row mb-7">
                        <label class="required fs-6 fw-semibold mb-2">Pilih Metode Pembayaran</label>
                        <select id="payment_method" class="form-select form-select-solid">
                            <option value="pay_later">🍽️ Bayar Nanti (Pay Later)</option>
                            <option value="cash">💵 Tunai (Cash)</option>
                            <option value="midtrans">📱 QRIS / Transfer (Midtrans)</option>
                        </select>
                    </div>

                    <div id="cash_input_area" class="fv-row mb-7">
                        <label class="required fs-6 fw-semibold mb-2">Nominal Uang Diterima (Rp)</label>
                        <input type="number" class="form-control form-control-solid" id="pay_amount" placeholder="0">
                        <div class="mt-3">
                            <span class="fs-6 text-gray-700 fw-bold">Kembalian: <span id="change_amount"
                                    class="text-danger">Rp 0</span></span>
                        </div>
                    </div>

                    <div class="text-center pt-5 border-top mt-5">
                        <button type="button" class="btn btn-light me-3" data-bs-dismiss="modal">Batal</button>
                        <button type="button" class="btn btn-primary" id="btn-process-payment">Proses
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
            var cart = [];
            var subtotalRaw = 0;
            var taxRaw = 0;
            var grandTotalRaw = 0;
            var taxRateDecimal = {{ $setting->tax_rate ?? 0 }} / 100;

            const formatRupiah = (number) => new Intl.NumberFormat('id-ID', {
                style: 'currency',
                currency: 'IDR',
                minimumFractionDigits: 0,
                maximumFractionDigits: 0 // 🔥 Pastikan tidak ada koma desimal sama sekali
            }).format(number);

            // 1. Tambah Menu ke Keranjang
            window.addToCart = function(id, name, price, img) {
                let existingItem = cart.find(item => item.id === id);

                if (existingItem) {
                    existingItem.qty += 1;
                    existingItem.subtotal = existingItem.qty * existingItem.price;
                } else {
                    cart.push({
                        id: id,
                        name: name,
                        price: price,
                        qty: 1,
                        subtotal: price,
                        note: '',
                        img: img
                    });
                }
                renderCart();
            }

            // 2. Update Qty
            window.updateQty = function(id, action) {
                let itemIndex = cart.findIndex(item => item.id === id);
                if (itemIndex > -1) {
                    if (action === 'increase') cart[itemIndex].qty += 1;
                    else if (action === 'decrease') {
                        if (cart[itemIndex].qty > 1) cart[itemIndex].qty -= 1;
                        else cart.splice(itemIndex, 1);
                    }
                    if (cart[itemIndex]) cart[itemIndex].subtotal = cart[itemIndex].qty * cart[itemIndex].price;
                    renderCart();
                }
            }

            // 3. Render HTML Keranjang (YANG DIPERBAIKI)
            window.renderCart = function() {
                let cartHtml = '';
                subtotalRaw = 0;

                if (cart.length === 0) {
                    cartHtml = '<tr><td class="text-center text-muted fst-italic py-10">Keranjang masih kosong.</td></tr>';
                    $('#btn-checkout').prop('disabled', true);
                } else {
                    $('#btn-checkout').prop('disabled', false);

                    cart.forEach(item => {
                        subtotalRaw += parseInt(item.subtotal);
                        cartHtml += `
                        <tr class="border-bottom border-gray-200">
                            <td class="pt-4 pb-2">
                                <div class="d-flex align-items-center mb-3">
                                    <img src="${item.img}" class="w-50px h-50px rounded-3 me-3" style="object-fit:cover;" />
                                    <div class="d-flex flex-column flex-grow-1">
                                        <span class="fw-bold text-gray-800 fs-5">${item.name}</span>
                                        <span class="fw-bold text-success fs-6">${formatRupiah(item.price)}</span>
                                    </div>
                                </div>
                                <div class="d-flex justify-content-between align-items-center mb-2">
                                    <div class="d-flex align-items-center border border-gray-300 rounded p-1">
                                        <button type="button" class="btn btn-icon btn-sm btn-light btn-active-light-danger w-25px h-25px" onclick="updateQty(${item.id}, 'decrease')"><i class="ki-outline ki-minus fs-5"></i></button>
                                        <input type="text" class="form-control border-0 text-center px-0 fs-5 fw-bold w-30px h-25px" readonly value="${item.qty}" />
                                        <button type="button" class="btn btn-icon btn-sm btn-light btn-active-light-success w-25px h-25px" onclick="updateQty(${item.id}, 'increase')"><i class="ki-outline ki-plus fs-5"></i></button>
                                    </div>
                                    <span class="fw-bolder text-gray-900 fs-4">${formatRupiah(item.subtotal)}</span>
                                </div>
                                <input type="text" class="form-control form-control-sm form-control-solid fs-8 mb-2 note-input" data-id="${item.id}" placeholder="Catatan: Pedas, pisah es, dll..." value="${item.note}">
                            </td>
                        </tr>`;
                    });
                }

                // HITUNG DISKON DARI DROPDOWN
                let discountAmount = 0;
                let promoOption = $('#promo_select').find(':selected');

                if (promoOption.val()) {
                    let type = promoOption.data('type');
                    let val = parseFloat(promoOption.data('value'));
                    // 🔥 Tambahkan Math.round() di sini
                    if (type === 'percentage') discountAmount = Math.round(subtotalRaw * (val / 100));
                    else discountAmount = val;
                }

                // Kalkulasi Akhir
                let netSubtotal = subtotalRaw - discountAmount;
                if (netSubtotal < 0) netSubtotal = 0;

                // 🔥 Tambahkan Math.round() pada pajak
                taxRaw = Math.round(netSubtotal * taxRateDecimal);
                grandTotalRaw = netSubtotal + taxRaw;

                // Render DOM Update
                $('#cart-items').html(cartHtml);
                $('#summary-subtotal').text(formatRupiah(subtotalRaw));
                $('#summary-tax').text(formatRupiah(taxRaw));
                $('#summary-total').text(formatRupiah(grandTotalRaw));

                if (discountAmount > 0) {
                    $('#discount-area').removeClass('d-none');
                    $('#summary-discount').text('- ' + formatRupiah(discountAmount));
                } else {
                    $('#discount-area').addClass('d-none');
                }

                // Re-bind event listener untuk input catatan (penting!)
                $('.note-input').off('change').on('change', function() {
                    let itemId = $(this).data('id');
                    let val = $(this).val();
                    let idx = cart.findIndex(i => i.id === itemId);
                    if (idx > -1) cart[idx].note = val;
                });
            }; // <-- AKHIR FUNGSI RENDERCART

            // TRIGGER KETIKA DROPDOWN PROMO DIUBAH
            $('#promo_select').on('change', function() {
                renderCart();
            });

            // 4. Kalkulator Kembalian Cash
            $('#pay_amount').on('keyup', function() {
                let pay = parseInt($(this).val()) || 0;
                let change = pay - grandTotalRaw;
                if (change < 0) change = 0;
                $('#change_amount').text(formatRupiah(change));
            });

            // 5. Toggle Metode Pembayaran
            $('#payment_method').on('change', function() {
                if ($(this).val() == 'cash') $('#cash_input_area').slideDown();
                else $('#cash_input_area').slideUp();
            });
            $('#cash_input_area').hide();

            // 6. Submit Pembayaran (Checkout)
            $('#btn-process-payment').on('click', function() {
                let payMethod = $('#payment_method').val();
                let payAmount = parseInt($('#pay_amount').val()) || 0;

                if (payMethod == 'cash' && payAmount < grandTotalRaw) {
                    Swal.fire('Uang Kurang!', 'Nominal uang tunai tidak cukup.', 'warning');
                    return;
                }

                let payload = {
                    _token: '{{ csrf_token() }}',
                    table_id: $('#table_id').val(),
                    promo_id: $('#promo_select').val(),
                    customer_name: $('#customer_name').val(),
                    order_type: $('#order_type').val(),
                    payment_method: payMethod,
                    cart: cart
                };

                $(this).prop('disabled', true).html(
                    '<span class="spinner-border spinner-border-sm"></span> Memproses...');

                $.ajax({
                    url: "{{ route('kasir.store') }}",
                    method: "POST",
                    data: payload,
                    success: function(res) {
                        $('#Modal_Payment').modal('hide');

                        const showPrintDialog = (orderId) => {
                            Swal.fire({
                                title: 'Pembayaran Lunas!',
                                text: 'Apakah Anda ingin mencetak struk?',
                                icon: 'success',
                                showCancelButton: true,
                                confirmButtonColor: '#009ef7',
                                cancelButtonColor: '#f1416c',
                                confirmButtonText: '<i class="ki-outline ki-printer fs-4 me-2 text-white"></i> Cetak Struk',
                                cancelButtonText: 'Lewati & Kembali',
                                allowOutsideClick: false
                            }).then((result) => {
                                if (result.isConfirmed) {
                                    window.open('{{ url('admin') }}/kasir/print/' + orderId, '_blank',
                                        'width=400,height=600');
                                }
                                window.location.href = "{{ route('kasir.index') }}";
                            });
                        };

                        if (res.type === 'pay_later') {
                            Swal.fire({
                                    title: 'Berhasil!',
                                    text: res.message,
                                    icon: 'success',
                                    allowOutsideClick: false
                                })
                                .then(() => {
                                    window.location.href = "{{ route('kasir.index') }}";
                                });
                        } else if (res.type === 'cash') {
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
                                    Swal.fire('Menunggu', 'Selesaikan pembayaran di HP Anda.',
                                        'info').then(() => {
                                        window.location.href =
                                            "{{ route('kasir.index') }}";
                                    });
                                },
                                onError: function() {
                                    document.body.style.overflow = 'auto';
                                    Swal.fire('Gagal', 'Pembayaran ditolak.', 'error');
                                },
                                onClose: function() {
                                    document.body.style.overflow = 'auto';
                                    Swal.fire('Tertunda',
                                        'Popup tertutup. Meja akan berstatus Belum Bayar (Kuning).',
                                        'warning').then(() => {
                                        window.location.href =
                                            "{{ route('kasir.index') }}";
                                    });
                                }
                            });
                        }
                    },
                    error: function(err) {
                        Swal.fire('Gagal!', 'Terjadi kesalahan sistem.', 'error');
                        $('#btn-process-payment').prop('disabled', false).text('Proses Pembayaran');
                    }
                });
            });
        </script>
    @endpush
@endsection
