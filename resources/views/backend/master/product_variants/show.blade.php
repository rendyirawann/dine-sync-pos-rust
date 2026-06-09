<div class="row mb-7">
    <div class="col-lg-12 mb-5">
        <div class="d-flex align-items-center bg-light-dark rounded p-5 mb-3">
            <i class="ki-outline ki-barcode fs-1 text-dark me-5"></i>
            <div class="flex-grow-1 me-2">
                <span class="text-muted fw-semibold d-block">SKU / Barcode Data</span>
                <span class="fs-2 text-dark fw-bold text-uppercase">{{ $variant->sku }}</span>
            </div>
        </div>
    </div>

    <div class="col-lg-6 mb-5">
        <label class="fw-semibold text-muted fs-6 mb-1">Produk Induk</label>
        <div class="fw-bold fs-5 text-gray-800">{{ $variant->product->brand ?? '-' }}
            {{ $variant->product->model_name ?? '-' }}</div>
    </div>

    <div class="col-lg-6 mb-5">
        <label class="fw-semibold text-muted fs-6 mb-1">Spesifikasi</label>
        <div class="fw-bold fs-6">
            <span class="badge badge-primary me-2">Size: {{ $variant->size }}</span>
            <span class="badge badge-info">Color: {{ $variant->color }}</span>
        </div>
    </div>
</div>

<div class="row border-top pt-5">
    <div class="col-lg-4 text-center border-end">
        <span class="text-muted fw-semibold d-block mb-1">Sisa Stok</span>
        <span class="fs-2 fw-bold text-{{ $variant->stock > 5 ? 'success' : 'danger' }}">{{ $variant->stock }}</span>
    </div>
    <div class="col-lg-4 text-center border-end">
        <span class="text-muted fw-semibold d-block mb-1">Harga Modal</span>
        <span class="fs-4 fw-bold text-gray-800">Rp {{ number_format($variant->price_buy, 0, ',', '.') }}</span>
    </div>
    <div class="col-lg-4 text-center">
        <span class="text-muted fw-semibold d-block mb-1">Harga Jual</span>
        <span class="fs-4 fw-bold text-success">Rp {{ number_format($variant->price_sell, 0, ',', '.') }}</span>
    </div>
</div>
