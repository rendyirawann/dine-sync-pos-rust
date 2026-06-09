@php
    // Kalkulasi Harga Diskon Item
    $finalPrice = $menu->price;
    if ($menu->discount_percent > 0) {
        $finalPrice = $menu->price - $menu->price * ($menu->discount_percent / 100);
    }
@endphp

<div class="card card-flush flex-row-fluid p-6 pb-5 mw-100 bg-hover-light cursor-pointer shadow-sm product-card-action position-relative"
    onclick="addToCart({{ $menu->id }}, '{{ addslashes($menu->name) }}', {{ $finalPrice }}, '{{ $menu->image ? asset('storage/menus/' . $menu->image) : asset('assets/media/svg/files/blank-image.svg') }}')">

    @if ($menu->discount_percent > 0)
        <span
            class="badge badge-danger position-absolute top-0 end-0 m-3 fs-5 px-3 py-2 shadow-sm z-index-1">-{{ $menu->discount_percent }}%</span>
    @endif

    <div class="card-body text-center p-0">
        <img src="{{ $menu->image ? asset('storage/menus/' . $menu->image) : asset('assets/media/svg/files/blank-image.svg') }}"
            class="rounded-3 mb-4 w-125px h-125px" style="object-fit: cover;" alt="Menu" />

        <div class="mb-2">
            <div class="text-center">
                <span class="fw-bold text-gray-800 fs-4">{{ $menu->name }}</span><br>
                <span class="badge badge-light-primary mt-2">{{ $menu->category->name ?? 'Lainnya' }}</span>
            </div>
        </div>

        <div class="d-flex flex-column justify-content-center align-items-center border-top pt-3 mt-auto">
            @if ($menu->discount_percent > 0)
                <span class="text-muted text-decoration-line-through fs-7">Rp
                    {{ number_format($menu->price, 0, ',', '.') }}</span>
            @endif
            <span class="text-success fw-bold fs-3">Rp {{ number_format($finalPrice, 0, ',', '.') }}</span>
        </div>
    </div>
</div>
