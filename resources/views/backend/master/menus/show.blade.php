<div class="text-center mb-9">
    @if ($menu->image)
        <img src="{{ asset('storage/menus/' . $menu->image) }}" alt="Foto Menu" class="rounded mw-100 h-200px"
            style="object-fit: cover; width: 100%;">
    @else
        <div class="d-flex align-items-center justify-content-center bg-light rounded h-200px w-100">
            <span class="text-muted fs-5"><i class="ki-outline ki-picture fs-1 me-2"></i> Tidak ada foto</span>
        </div>
    @endif
</div>

<div class="d-flex flex-stack mb-5">
    <div class="d-flex flex-column">
        <h2 class="text-gray-900 fw-bolder fs-2 mb-1">{{ $menu->name }}</h2>
        <span class="badge badge-light-primary w-fit-content px-3 py-2">{{ $menu->category->name ?? '-' }}</span>
    </div>
    <div class="text-end">
        @if ($menu->discount_percent > 0)
            @php $discountedPrice = $menu->price - ($menu->price * ($menu->discount_percent / 100)); @endphp
            <div class="fs-6 text-muted text-decoration-line-through">Rp {{ number_format($menu->price, 0, ',', '.') }}
            </div>
            <div class="fs-2hx fw-bold text-success">
                Rp {{ number_format($discountedPrice, 0, ',', '.') }}
                <span class="badge badge-light-danger fs-5 align-top">-{{ $menu->discount_percent }}%</span>
            </div>
        @else
            <div class="fs-2hx fw-bold text-success">Rp {{ number_format($menu->price, 0, ',', '.') }}</div>
        @endif
    </div>
</div>

<div class="separator separator-dashed my-5"></div>

<div class="d-flex flex-column mb-5">
    <span class="fw-bold text-muted fs-6 mb-2">Status Penjualan</span>
    <div>
        @if ($menu->is_available)
            <span class="badge badge-success fs-5 px-4 py-2"><i class="ki-outline ki-check fs-4 me-1 text-white"></i>
                Menu Tersedia</span>
        @else
            <span class="badge badge-danger fs-5 px-4 py-2"><i class="ki-outline ki-cross fs-4 me-1 text-white"></i>
                Habis Terjual</span>
        @endif
    </div>
</div>

<div class="d-flex flex-column">
    <span class="fw-bold text-muted fs-6 mb-1">Deskripsi</span>
    <span class="fw-semibold text-gray-800 fs-6">{{ $menu->description ?: 'Tidak ada deskripsi.' }}</span>
</div>
