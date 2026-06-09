<div class="row mb-7">
    <div class="col-lg-6">
        <label class="fw-semibold text-muted fs-6 mb-1">Nama Kategori</label>
        <div class="fw-bold fs-5 text-gray-800">{{ $category->name }}</div>
    </div>
    <div class="col-lg-6">
        <label class="fw-semibold text-muted fs-6 mb-1">Slug</label>
        <div class="fw-bold fs-5"><span class="badge badge-light-primary">{{ $category->slug }}</span></div>
    </div>
</div>

<div class="row">
    <div class="col-lg-6">
        <label class="fw-semibold text-muted fs-6 mb-1">Tanggal Dibuat</label>
        <div class="fw-bold fs-6 text-gray-800">
            {{ \Carbon\Carbon::parse($category->created_at)->locale('id')->translatedFormat('d F Y, H:i') }}
        </div>
    </div>
    <div class="col-lg-6">
        <label class="fw-semibold text-muted fs-6 mb-1">Terakhir Diupdate</label>
        <div class="fw-bold fs-6 text-gray-800">
            {{ \Carbon\Carbon::parse($category->updated_at)->locale('id')->translatedFormat('d F Y, H:i') }}
        </div>
    </div>
</div>
