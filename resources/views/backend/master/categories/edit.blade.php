<input type="hidden" id="edit_category_id" value="{{ $category->id }}">

<div class="d-flex flex-column mb-7 fv-row">
    <label class="d-flex align-items-center fs-6 fw-semibold form-label mb-2">
        <span class="required">Nama Kategori</span>
    </label>
    <input type="text" class="form-control" name="name" value="{{ $category->name }}" />
    <span class="text-danger error-text name_error_edit mt-2"></span>
</div>

<div class="d-flex flex-column mb-7 fv-row">
    <label class="d-flex align-items-center fs-6 fw-semibold form-label mb-2">
        <span>Slug (Auto-Generated)</span>
    </label>
    <input type="text" class="form-control bg-light" value="{{ $category->slug }}" readonly disabled />
    <div class="form-text mt-2">Slug akan otomatis menyesuaikan perubahan Nama Kategori.</div>
</div>
