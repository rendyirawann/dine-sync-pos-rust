<input type="hidden" id="edit_product_id" value="{{ $product->id }}">

<div class="row mb-7">
    <div class="col-md-12 fv-row">
        <label class="required fs-6 fw-semibold mb-2">Kategori</label>
        <select name="category_id" class="form-select form-select-solid" data-control="select2"
            data-placeholder="Pilih Kategori">
            <option></option>
            @foreach ($categories as $cat)
                <option value="{{ $cat->id }}" {{ $product->category_id == $cat->id ? 'selected' : '' }}>
                    {{ $cat->name }}</option>
            @endforeach
        </select>
        <span class="text-danger error-text category_id_error_edit mt-2"></span>
    </div>
</div>

<div class="row mb-7">
    <div class="col-md-6 fv-row">
        <label class="required fs-6 fw-semibold mb-2">Merek (Brand)</label>
        <input type="text" class="form-control" name="brand" value="{{ $product->brand }}" />
        <span class="text-danger error-text brand_error_edit mt-2"></span>
    </div>
    <div class="col-md-6 fv-row">
        <label class="required fs-6 fw-semibold mb-2">Nama Model</label>
        <input type="text" class="form-control" name="model_name" value="{{ $product->model_name }}" />
        <span class="text-danger error-text model_name_error_edit mt-2"></span>
    </div>
</div>

<div class="fv-row mb-7">
    <label class="fs-6 fw-semibold mb-2">Deskripsi Produk</label>
    <textarea class="form-control" name="description" rows="3">{{ $product->description }}</textarea>
    <span class="text-danger error-text description_error_edit mt-2"></span>
</div>
