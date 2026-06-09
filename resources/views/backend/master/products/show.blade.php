<div class="row mb-7">
    <div class="col-lg-12 mb-4">
        <label class="fw-semibold text-muted fs-6 mb-1">Merek & Model</label>
        <div class="fw-bold fs-3 text-gray-800">{{ $product->brand }} - {{ $product->model_name }}</div>
    </div>
    <div class="col-lg-12">
        <label class="fw-semibold text-muted fs-6 mb-1">Kategori</label>
        <div class="fw-bold fs-5">
            <span class="badge badge-light-primary">{{ $product->category->name ?? 'Tidak ada Kategori' }}</span>
        </div>
    </div>
</div>

<div class="row border-top pt-5 mb-7">
    <div class="col-lg-6">
        <div class="border border-gray-300 border-dashed rounded py-3 px-4 mb-3">
            <div class="fs-6 text-gray-800 fw-bold">{{ $product->variants->count() }} Tipe</div>
            <div class="fw-semibold text-muted">Jumlah Varian Terdaftar</div>
        </div>
    </div>
    <div class="col-lg-6">
        <div class="border border-success border-dashed bg-light-success rounded py-3 px-4 mb-3">
            <div class="fs-6 text-success fw-bold">{{ $product->total_stock }} Item</div>
            <div class="fw-semibold text-success opacity-75">Total Keseluruhan Stok</div>
        </div>
    </div>
</div>

<div class="row">
    <div class="col-lg-12">
        <label class="fw-semibold text-muted fs-6 mb-1">Deskripsi Lengkap</label>
        <div class="fw-bold fs-6 text-gray-800" style="white-space: pre-wrap;">
            {{ $product->description ?? 'Tidak ada deskripsi.' }}</div>
    </div>
</div>
