<input type="hidden" id="edit_variant_id" value="{{ $variant->id }}">

<div class="fv-row mb-7">
    <label class="required fs-6 fw-semibold mb-2">Pilih Produk Induk</label>
    <select name="product_id" class="form-select form-select-solid" data-control="select2"
        data-placeholder="Cari Merek/Model Produk">
        <option></option>
        @foreach ($products as $prod)
            <option value="{{ $prod->id }}" {{ $variant->product_id == $prod->id ? 'selected' : '' }}>
                {{ $prod->brand }} - {{ $prod->model_name }}
            </option>
        @endforeach
    </select>
    <span class="text-danger error-text product_id_error_edit mt-2"></span>
</div>

<div class="row mb-7">
    <div class="col-md-6 fv-row">
        <label class="required fs-6 fw-semibold mb-2">SKU (Barcode)</label>
        <input type="text" class="form-control text-uppercase" name="sku" value="{{ $variant->sku }}" />
        <span class="text-danger error-text sku_error_edit mt-2"></span>
    </div>
    <div class="col-md-3 fv-row">
        <label class="required fs-6 fw-semibold mb-2">Ukuran</label>
        <input type="text" class="form-control" name="size" value="{{ $variant->size }}" />
        <span class="text-danger error-text size_error_edit mt-2"></span>
    </div>
    <div class="col-md-3 fv-row">
        <label class="required fs-6 fw-semibold mb-2">Warna</label>
        <input type="text" class="form-control" name="color" value="{{ $variant->color }}" />
        <span class="text-danger error-text color_error_edit mt-2"></span>
    </div>
</div>

<div class="row mb-7">
    <div class="col-md-4 fv-row">
        <label class="fs-6 fw-semibold mb-2">Stok (Auto Batch)</label>
        <input type="number" class="form-control bg-secondary" name="stock" value="{{ $variant->stock }}" readonly />
    </div>
    <div class="col-md-4 fv-row">
        <label class="required fs-6 fw-semibold mb-2">Harga Beli Dasar</label>
        <div class="input-group">
            <span class="input-group-text">Rp</span>
            <input type="number" class="form-control" name="price_buy" value="{{ $variant->price_buy }}"
                min="0" />
        </div>
        <span class="text-danger error-text price_buy_error_edit mt-2"></span>
    </div>
    <div class="col-md-4 fv-row">
        <label class="required fs-6 fw-semibold mb-2">Harga Jual</label>
        <div class="input-group">
            <span class="input-group-text">Rp</span>
            <input type="number" class="form-control" name="price_sell" value="{{ $variant->price_sell }}"
                min="0" />
        </div>
        <span class="text-danger error-text price_sell_error_edit mt-2"></span>
    </div>
</div>
