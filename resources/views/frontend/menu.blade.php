<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport"
        content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no, viewport-fit=cover">
    <title>Menu - {{ $setting->store_name ?? 'DineSync POS' }}</title>
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <script src="https://app.sandbox.midtrans.com/snap/snap.js" data-client-key="{{ env('MIDTRANS_CLIENT_KEY') }}"></script>

    <link rel="stylesheet" href="https://fonts.googleapis.com/css?family=Inter:300,400,500,600,700" />
    <link href="{{ asset('assets/plugins/global/plugins.bundle.css') }}" rel="stylesheet" type="text/css" />
    <link href="{{ asset('assets/css/style.bundle.css') }}" rel="stylesheet" type="text/css" />

    <style>
        /* 🔥 BUMBU RAHASIA 2: Background abu-abu penuh untuk layar lebar */
        html,
        body {
            margin: 0;
            padding: 0;
            width: 100%;
            height: 100%;
            overflow-x: hidden;
            background-color: #f5f8fa;
            font-family: 'Inter', sans-serif;
            -webkit-text-size-adjust: 100%;
            -webkit-tap-highlight-color: transparent;
        }

        .mobile-container {
            width: 100%;
            min-height: 100vh;
            min-height: 100dvh;
            display: flex;
            flex-direction: column;
            background: #f5f8fa;
            position: relative;
            padding-bottom: 90px;
        }

        /* HEADER FULL WIDTH, KONTEN TENGAH */
        .sticky-header {
            position: sticky;
            top: 0;
            z-index: 99;
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(10px);
            border-bottom: 1px solid #e4e6ef;
            width: 100%;
        }

        .header-content {
            width: 100%;
            max-width: 480px;
            /* Batas tengah untuk iPad/Desktop */
            margin: 0 auto;
            padding: calc(15px + env(safe-area-inset-top)) 20px 15px 20px;
        }

        /* KATEGORI FULL WIDTH, SCROLL TENGAH */
        .category-wrapper {
            width: 100%;
            background: #ffffff;
            border-bottom: 1px solid #e4e6ef;
            box-shadow: 0 4px 10px rgba(0, 0, 0, 0.02);
        }

        .category-scroll {
            width: 100%;
            max-width: 480px;
            /* Batas tengah */
            margin: 0 auto;
            display: flex;
            overflow-x: auto;
            gap: 10px;
            padding: 12px 20px;
            scrollbar-width: none;
            /* Firefox */
        }

        .category-scroll::-webkit-scrollbar {
            display: none;
            /* Chrome/Safari */
        }

        .cat-pill {
            white-space: nowrap;
            padding: 8px 18px;
            border-radius: 50px;
            background: #f1f1f4;
            color: #7e8299;
            font-weight: 600;
            font-size: 13px;
            text-decoration: none;
            transition: all 0.3s ease;
            border: 1px solid transparent;
        }

        .cat-pill.active {
            background: #e8f4ff;
            color: #009ef7;
            border-color: #009ef7;
            box-shadow: 0 4px 10px rgba(0, 158, 247, 0.15);
        }

        /* WRAPPER KONTEN MENU TENGAH */
        .content-wrapper {
            width: 100%;
            max-width: 480px;
            /* Batas tengah Grid Menu */
            margin: 0 auto;
            display: flex;
            flex-direction: column;
            flex-grow: 1;
        }

        .menu-grid {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 15px;
            padding: 20px;
        }

        .menu-card {
            background: #ffffff;
            border: 1px solid #f1f1f4;
            border-radius: 12px;
            overflow: hidden;
            display: flex;
            flex-direction: column;
            position: relative;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.03);
            transition: transform 0.2s;
        }

        .menu-card:active {
            transform: scale(0.98);
        }

        .menu-img {
            width: 100%;
            height: 120px;
            object-fit: cover;
        }

        .badge-discount {
            position: absolute;
            top: 8px;
            right: 8px;
            background: #f1416c;
            color: #fff;
            padding: 4px 8px;
            border-radius: 6px;
            font-size: 10px;
            font-weight: bold;
            z-index: 2;
            box-shadow: 0 2px 5px rgba(241, 65, 108, 0.3);
        }

        /* FLOATING BOTTOM CART BAR */
        .floating-cart {
            position: fixed;
            bottom: calc(15px + env(safe-area-inset-bottom));
            left: 50%;
            transform: translateX(-50%);
            width: 100%;
            max-width: 480px;
            /* Terkunci di tengah Layar */
            padding: 0 20px;
            z-index: 100;
        }

        .cart-bar {
            background: #181c32;
            border-radius: 16px;
            padding: 12px 20px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            color: #fff;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.2);
            cursor: pointer;
            transition: transform 0.2s ease;
        }

        .cart-bar:active {
            transform: scale(0.98);
        }

        /* MODAL KERANJANG CUSTOM */
        .modal.bottom-sheet .modal-dialog {
            margin: auto;
            max-width: 480px;
            /* Agar modal tidak selebar layar di iPad */
            display: flex;
            align-items: flex-end;
            min-height: 100%;
        }

        .modal.bottom-sheet .modal-content {
            border-radius: 20px 20px 0 0;
            max-height: 85vh;
            overflow-y: auto;
            width: 100%;
        }

        .qty-btn {
            width: 28px;
            height: 28px;
            display: flex;
            justify-content: center;
            align-items: center;
            border-radius: 6px;
            background: #f1f1f4;
            border: none;
        }

        .qty-btn:active {
            transform: scale(0.9);
        }
    </style>
</head>

<body>

    <div class="mobile-container">

        <div class="sticky-header">
            <div class="header-content d-flex justify-content-between align-items-center">
                <div>
                    <h2 class="fs-3 fw-bolder mb-0">{{ $setting->store_name ?? 'DineSync' }}</h2>
                    <span class="fs-8 text-muted">Hai, {{ $customerName }} (Meja {{ $table->table_number }})</span>
                </div>

                <div class="d-flex align-items-center gap-3">
                    <button class="btn btn-icon btn-sm btn-light-primary position-relative shadow-sm rounded-circle"
                        data-bs-toggle="modal" data-bs-target="#modalHistory">
                        <i class="ki-outline ki-receipt-square fs-2"></i>
                        @if (count($activeOrders) > 0)
                            <span
                                class="position-absolute top-0 start-100 translate-middle badge badge-circle badge-danger w-15px h-15px fs-9">{{ count($activeOrders) }}</span>
                        @endif
                    </button>

                    <div class="symbol symbol-40px symbol-circle shadow-sm">
                        <div
                            class="symbol-label bg-light-primary text-primary fs-3 fw-bolder border border-primary border-dashed">
                            {{ strtoupper(substr($customerName, 0, 1)) }}
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="category-wrapper">
            <div class="category-scroll">
                <a href="#cat-all" class="cat-pill active">Semua</a>
                @foreach ($categories as $cat)
                    <a href="#cat-{{ $cat->id }}" class="cat-pill">{{ $cat->name }}</a>
                @endforeach
            </div>
        </div>

        <div class="content-wrapper">
            <div class="menu-grid" id="menu-container">
                @foreach ($menus as $menu)
                    @php
                        $finalPrice = $menu->price;
                        if ($menu->discount_percent > 0) {
                            $finalPrice = $menu->price - $menu->price * ($menu->discount_percent / 100);
                        }
                    @endphp

                    <div class="menu-card menu-item-card" data-category="{{ $menu->category_id ?? 'nocat' }}">
                        @if ($menu->discount_percent > 0)
                            <div class="badge-discount">-{{ $menu->discount_percent }}%</div>
                        @endif

                        <img src="{{ $menu->image ? asset('storage/menus/' . $menu->image) : asset('assets/media/svg/files/blank-image.svg') }}"
                            class="menu-img" alt="Menu">

                        <div class="p-3 d-flex flex-column flex-grow-1">
                            <span class="fw-bolder text-gray-800 fs-6 lh-sm mb-1">{{ $menu->name }}</span>

                            <div class="mt-auto pt-2">
                                @if ($menu->discount_percent > 0)
                                    <div class="text-muted text-decoration-line-through fs-8">Rp
                                        {{ number_format($menu->price, 0, ',', '.') }}</div>
                                @endif
                                <div class="text-primary fw-bolder fs-5 mb-3">Rp
                                    {{ number_format($finalPrice, 0, ',', '.') }}</div>

                                <button class="btn btn-sm btn-light-primary w-100 py-2 fw-bold"
                                    onclick="addToCart({{ $menu->id }}, '{{ addslashes($menu->name) }}', {{ $finalPrice }}, '{{ $menu->image ? asset('storage/menus/' . $menu->image) : asset('assets/media/svg/files/blank-image.svg') }}')">
                                    + Tambah
                                </button>
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>
        </div>

        <div class="floating-cart d-none" id="floatingCart">
            <div class="cart-bar" data-bs-toggle="modal" data-bs-target="#modalCart">
                <div class="d-flex align-items-center">
                    <div class="bg-white text-dark fw-bolder rounded-circle d-flex justify-content-center align-items-center me-3"
                        style="width: 30px; height: 30px;" id="floatingQty">0</div>
                    <div class="d-flex flex-column">
                        <span class="fs-8 text-gray-400">Total Harga</span>
                        <span class="fw-bolder fs-5 lh-1" id="floatingTotal">Rp 0</span>
                    </div>
                </div>
                <div>
                    <span class="fw-bold me-1">Keranjang</span>
                    <i class="ki-outline ki-arrow-right fs-4"></i>
                </div>
            </div>
        </div>

    </div>

    <div class="modal fade bottom-sheet" id="modalCart" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header border-0 pb-0">
                    <h3 class="modal-title fw-bolder">Keranjang Pesanan</h3>
                    <div class="btn btn-icon btn-sm btn-active-light-danger ms-2" data-bs-dismiss="modal"
                        aria-label="Close">
                        <i class="ki-outline ki-cross fs-1"></i>
                    </div>
                </div>

                <div class="modal-body py-4">
                    <div id="cartItemList" class="mb-5 border-bottom pb-4"
                        style="max-height: 40vh; overflow-y: auto; overflow-x: hidden;"></div>

                    <div class="mb-5">
                        <label class="fs-7 fw-bold mb-2 text-gray-700">Punya Kode Promo?</label>
                        <select id="promoSelect" class="form-select form-select-solid form-select-sm">
                            <option value="">-- Pilih Promo (Jika Ada) --</option>
                            @foreach ($promos as $promo)
                                <option value="{{ $promo->id }}" data-type="{{ $promo->discount_type }}"
                                    data-value="{{ $promo->discount_value }}">
                                    {{ $promo->name }}
                                    ({{ $promo->discount_type == 'percentage' ? $promo->discount_value . '%' : 'Rp ' . number_format($promo->discount_value, 0, ',', '.') }})
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <div class="bg-light rounded p-4 mb-4">
                        <div class="d-flex justify-content-between mb-2">
                            <span class="text-gray-600 fs-7">Subtotal</span>
                            <span class="fw-bold fs-7" id="summarySubtotal">Rp 0</span>
                        </div>
                        <div class="d-flex justify-content-between mb-2 d-none" id="discountRow">
                            <span class="text-danger fs-7">Diskon Promo</span>
                            <span class="fw-bold text-danger fs-7" id="summaryDiscount">- Rp 0</span>
                        </div>
                        <div class="d-flex justify-content-between mb-2">
                            <span class="text-gray-600 fs-7">Pajak ({{ $setting->tax_rate ?? 0 }}%)</span>
                            <span class="fw-bold fs-7" id="summaryTax">Rp 0</span>
                        </div>
                        <div class="separator separator-dashed my-3"></div>
                        <div class="d-flex justify-content-between align-items-center">
                            <span class="fw-bolder text-gray-800 fs-5">Total Pembayaran</span>
                            <span class="fw-bolder text-primary fs-3" id="summaryGrandTotal">Rp 0</span>
                        </div>
                    </div>

                    <button type="button" class="btn btn-primary w-100 py-4 fw-bolder fs-4" id="btnCheckout">
                        Pesan & Bayar Sekarang
                    </button>
                </div>
            </div>
        </div>
    </div>

    <div class="modal fade bottom-sheet" id="modalHistory" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content bg-light">
                <div class="modal-header border-0 pb-0 bg-white rounded-top-4">
                    <h3 class="modal-title fw-bolder mt-3">Pesanan Saya</h3>
                    <div class="btn btn-icon btn-sm btn-active-light-danger ms-2 mt-3" data-bs-dismiss="modal"
                        aria-label="Close">
                        <i class="ki-outline ki-cross fs-1"></i>
                    </div>
                </div>

                <div class="modal-body py-4 bg-white" style="max-height: 75vh; overflow-y: auto; overflow-x: hidden;">
                    @forelse($activeOrders as $ord)
                        <div class="card border border-gray-200 shadow-sm mb-5">
                            <div class="card-header min-h-40px px-4 py-2 border-bottom">
                                <div class="card-title m-0 d-flex justify-content-between w-100 align-items-center">
                                    <span class="text-gray-500 fs-8 fw-bold">#{{ $ord->invoice_no }}</span>
                                    @if ($ord->payment_status == 'paid')
                                        <span class="badge badge-light-success fs-8">LUNAS</span>
                                    @else
                                        <span class="badge badge-light-warning fs-8">BELUM BAYAR</span>
                                    @endif
                                </div>
                            </div>
                            <div class="card-body p-4">
                                @foreach ($ord->details as $det)
                                    <div class="d-flex justify-content-between align-items-start mb-2">
                                        <div class="d-flex flex-column">
                                            <span class="fs-7 fw-bold text-gray-800">{{ $det->qty }}x
                                                {{ $det->menu->name ?? 'Menu' }}</span>
                                            @if ($det->notes)
                                                <span class="fs-8 text-muted fst-italic">{{ $det->notes }}</span>
                                            @endif
                                        </div>
                                        <span class="fs-7 fw-bold text-gray-600">Rp
                                            {{ number_format($det->subtotal, 0, ',', '.') }}</span>
                                    </div>
                                @endforeach

                                <div class="separator separator-dashed my-3"></div>
                                <div class="d-flex justify-content-between align-items-center">
                                    <span class="fw-bold text-gray-700 fs-7">Total Tagihan</span>
                                    <span class="fw-bolder text-primary fs-5">Rp
                                        {{ number_format($ord->grand_total, 0, ',', '.') }}</span>
                                </div>
                            </div>
                        </div>
                    @empty
                        <div class="text-center py-10">
                            <i class="ki-outline ki-document fs-5x text-muted mb-3"></i>
                            <div class="fs-5 text-gray-600 fw-bold">Belum ada pesanan</div>
                            <div class="fs-7 text-muted mt-1">Ayo pesan menu favoritmu sekarang!</div>
                        </div>
                    @endforelse
                </div>
            </div>
        </div>
    </div>

    <script src="{{ asset('assets/plugins/global/plugins.bundle.js') }}"></script>
    <script src="{{ asset('assets/js/scripts.bundle.js') }}"></script>

    <script>
        // Logika Keranjang Pelanggan
        var cart = [];
        var subtotalRaw = 0;
        var taxRate = {{ $setting->tax_rate ?? 0 }} / 100;

        const formatRp = (num) => new Intl.NumberFormat('id-ID', {
            style: 'currency',
            currency: 'IDR',
            minimumFractionDigits: 0,
            maximumFractionDigits: 0
        }).format(num);

        function addToCart(id, name, price, img) {
            let item = cart.find(i => i.id === id);
            if (item) {
                item.qty += 1;
                item.subtotal = item.qty * item.price;
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

            toastr.options = {
                "positionClass": "toast-top-center",
                "timeOut": "1500"
            };
            toastr.success(name + " ditambahkan!");
        }

        function updateQty(id, action) {
            let idx = cart.findIndex(i => i.id === id);
            if (idx > -1) {
                if (action === 'plus') cart[idx].qty += 1;
                else if (action === 'minus') {
                    if (cart[idx].qty > 1) cart[idx].qty -= 1;
                    else cart.splice(idx, 1);
                }
                if (cart[idx]) cart[idx].subtotal = cart[idx].qty * cart[idx].price;
                renderCart();
            }
        }

        function updateNote(id, el) {
            let item = cart.find(i => i.id === id);
            if (item) item.note = el.value;
        }

        function renderCart() {
            let totalQty = 0;
            subtotalRaw = 0;
            let html = '';

            if (cart.length === 0) {
                $('#floatingCart').addClass('d-none');
                html = '<div class="text-center text-muted fst-italic py-5">Keranjang masih kosong</div>';
                $('#btnCheckout').prop('disabled', true);
            } else {
                $('#floatingCart').removeClass('d-none');
                $('#btnCheckout').prop('disabled', false);

                cart.forEach(item => {
                    totalQty += item.qty;
                    subtotalRaw += item.subtotal;

                    html += `
                    <div class="d-flex align-items-start mb-4 pe-2">
                        <img src="${item.img}" class="w-50px h-50px rounded me-3" style="object-fit:cover;">
                        <div class="flex-grow-1">
                            <div class="d-flex justify-content-between align-items-start">
                                <span class="fw-bold text-gray-800">${item.name}</span>
                                <span class="fw-bold text-primary">${formatRp(item.subtotal)}</span>
                            </div>
                            <div class="d-flex justify-content-between align-items-center mt-2">
                                <input type="text" class="form-control form-control-sm form-control-transparent border-bottom w-150px p-0 fs-8" placeholder="Catatan (opsional)..." value="${item.note}" onchange="updateNote(${item.id}, this)">
                                
                                <div class="d-flex align-items-center gap-2">
                                    <button class="qty-btn text-danger" onclick="updateQty(${item.id}, 'minus')"><i class="ki-outline ki-minus fs-6"></i></button>
                                    <span class="fw-bolder px-2">${item.qty}</span>
                                    <button class="qty-btn text-success" onclick="updateQty(${item.id}, 'plus')"><i class="ki-outline ki-plus fs-6"></i></button>
                                </div>
                            </div>
                        </div>
                    </div>`;
                });
            }

            let discountRaw = 0;
            let selectedPromo = $('#promoSelect').find(':selected');
            if (selectedPromo.val()) {
                let type = selectedPromo.data('type');
                let val = parseFloat(selectedPromo.data('value'));
                if (type === 'percentage') discountRaw = Math.round(subtotalRaw * (val / 100));
                else discountRaw = val;
            }

            let netSubtotal = subtotalRaw - discountRaw;
            if (netSubtotal < 0) netSubtotal = 0;

            let taxValue = Math.round(netSubtotal * taxRate);
            let grandTotal = netSubtotal + taxValue;

            $('#cartItemList').html(html);
            $('#floatingQty').text(totalQty);
            $('#floatingTotal').text(formatRp(grandTotal));

            $('#summarySubtotal').text(formatRp(subtotalRaw));
            $('#summaryTax').text(formatRp(taxValue));
            $('#summaryGrandTotal').text(formatRp(grandTotal));

            if (discountRaw > 0) {
                $('#discountRow').removeClass('d-none');
                $('#summaryDiscount').text('- ' + formatRp(discountRaw));
            } else {
                $('#discountRow').addClass('d-none');
            }
        }

        $('#promoSelect').change(renderCart);

        // 🔥 BONUS: Fitur Filter Kategori Instan Tanpa Loading
        $('.cat-pill').click(function(e) {
            e.preventDefault();
            $('.cat-pill').removeClass('active');
            $(this).addClass('active');

            let catId = $(this).attr('href').replace('#cat-', '');

            if (catId === 'all') {
                $('.menu-item-card').fadeIn(200); // Tampilkan semua
            } else {
                $('.menu-item-card').hide(); // Sembunyikan semua
                $('.menu-item-card[data-category="' + catId + '"]').fadeIn(200); // Tampilkan yg sesuai
            }
        });

        // Tombol Checkout & Pembayaran
        $('#btnCheckout').click(function() {
            $('#modalCart').modal('hide');

            Swal.fire({
                title: 'Pilih Metode Pembayaran',
                text: 'Bagaimana Anda ingin menyelesaikan pembayaran ini?',
                icon: 'question',
                showDenyButton: true,
                showCancelButton: true,
                confirmButtonText: '<i class="ki-outline ki-wallet fs-4 me-2 text-white"></i> Bayar Online (QRIS)',
                denyButtonText: '<i class="ki-outline ki-shop fs-4 me-2 text-white"></i> Bayar di Kasir',
                cancelButtonText: 'Batal',
                customClass: {
                    confirmButton: 'btn btn-primary',
                    denyButton: 'btn btn-success',
                    cancelButton: 'btn btn-light'
                }
            }).then((result) => {
                if (result.isConfirmed) {
                    processCheckout('midtrans'); // Bayar pakai HP
                } else if (result.isDenied) {
                    processCheckout('pay_later'); // Bayar nanti di kasir
                } else {
                    $('#modalCart').modal('show');
                }
            });
        });

        function processCheckout(paymentMethod) {
            Swal.fire({
                title: 'Memproses Pesanan...',
                allowOutsideClick: false,
                didOpen: () => {
                    Swal.showLoading();
                }
            });

            $.ajax({
                url: "{{ route('frontend.checkout', $table->uuid) }}",
                method: "POST",
                headers: {
                    'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
                },
                data: {
                    cart: cart,
                    promo_id: $('#promoSelect').val(),
                    payment_method: paymentMethod
                },
                success: function(res) {
                    if (res.type === 'pay_later') {
                        // Langsung ke halaman sukses
                        window.location.href = res.redirect;
                    } else if (res.type === 'midtrans') {
                        Swal.close();
                        // Buka Pop-Up Midtrans Snap
                        snap.pay(res.snap_token, {
                            onSuccess: function(result) {
                                window.location.href =
                                    "{{ route('frontend.success', $table->uuid) }}";
                            },
                            onPending: function(result) {
                                window.location.href =
                                    "{{ route('frontend.success', $table->uuid) }}";
                            },
                            onError: function(result) {
                                Swal.fire('Gagal!', 'Pembayaran Anda ditolak.', 'error');
                            },
                            onClose: function() {
                                // Jika pop-up ditutup tanpa bayar, arahkan ke halaman sukses, status akan jadi "Belum Bayar" (Kuning) di kasir
                                Swal.fire('Tertunda',
                                    'Menunggu pembayaran. Silakan hubungi kasir jika ingin mengubah metode bayar.',
                                    'warning').then(() => {
                                    window.location.href =
                                        "{{ route('frontend.success', $table->uuid) }}";
                                });
                            }
                        });
                    }
                },
                error: function(err) {
                    Swal.fire('Gagal!', 'Terjadi kesalahan saat memproses pesanan.', 'error');
                }
            });
        }
    </script>
</body>

</html>
