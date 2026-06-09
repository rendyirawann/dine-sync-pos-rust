<input type="hidden" id="edit_menu_id" value="{{ $menu->id }}">

<div class="row mb-7">
    <div class="col-md-6 fv-row">
        <label class="required fs-6 fw-semibold mb-2">Nama Menu</label>
        <input type="text" class="form-control" name="name" value="{{ $menu->name }}" />
        <span class="text-danger error-text name_error_edit mt-2"></span>
    </div>
    <div class="col-md-6 fv-row">
        <label class="required fs-6 fw-semibold mb-2">Kategori</label>
        <select name="category_id" class="form-select form-select-solid" data-placeholder="Pilih Kategori">
            <option></option>
            @foreach ($categories as $cat)
                <option value="{{ $cat->id }}" {{ $menu->category_id == $cat->id ? 'selected' : '' }}>
                    {{ $cat->name }}</option>
            @endforeach
        </select>
        <span class="text-danger error-text category_id_error_edit mt-2"></span>
    </div>
</div>

<div class="row mb-7">
    <div class="col-md-4 fv-row">
        <label class="required fs-6 fw-semibold mb-2">Harga (Rp)</label>
        <input type="number" class="form-control" name="price" value="{{ round($menu->price) }}" min="0"
            required />
        <span class="text-danger error-text price_error_edit mt-2"></span>
    </div>
    <div class="col-md-4 fv-row">
        <label class="fs-6 fw-semibold mb-2">Diskon (%)</label>
        <input type="number" class="form-control" name="discount_percent" value="{{ $menu->discount_percent ?? 0 }}"
            min="0" max="100" />
        <span class="text-danger error-text discount_percent_error_edit mt-2"></span>
    </div>
    <div class="col-md-4 fv-row">
        <label class="fs-6 fw-semibold mb-2 d-block">Ketersediaan</label>
        <div class="form-check form-switch form-check-custom form-check-solid mt-3">
            <input class="form-check-input w-40px h-20px" type="checkbox" name="is_available" value="1"
                id="status_edit" {{ $menu->is_available ? 'checked' : '' }} />
            <label class="form-check-label text-gray-700 fw-bold" for="status_edit">Tersedia</label>
        </div>
    </div>
</div>

<div class="fv-row mb-7">
    <label class="fs-6 fw-semibold mb-2">Deskripsi (Opsional)</label>
    <textarea class="form-control" name="description" rows="2">{{ $menu->description }}</textarea>
</div>

<div class="fv-row mb-7">
    <label class="fs-6 fw-semibold mb-2 d-block">Ganti Foto Menu (Biarkan kosong jika tidak ingin ganti)</label>
    <input type="file" class="form-control" name="image" accept=".png, .jpg, .jpeg" />
    <span class="text-danger error-text image_error_edit mt-2"></span>
    @if ($menu->image)
        <div class="mt-3">
            <span class="badge badge-light-info">Foto saat ini terpasang</span>
        </div>
    @endif
</div>
