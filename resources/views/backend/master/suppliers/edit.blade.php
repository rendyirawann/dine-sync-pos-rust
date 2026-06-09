<input type="hidden" id="edit_supplier_id" value="{{ $supplier->id }}">

<div class="fv-row mb-7">
    <label class="required fs-6 fw-semibold mb-2">Nama Supplier</label>
    <input type="text" class="form-control" name="name" value="{{ $supplier->name }}" />
    <span class="text-danger error-text name_error_edit mt-2"></span>
</div>

<div class="fv-row mb-7">
    <label class="fs-6 fw-semibold mb-2">No. WhatsApp / Telp</label>
    <input type="text" class="form-control" name="phone" value="{{ $supplier->phone }}" />
    <span class="text-danger error-text phone_error_edit mt-2"></span>
</div>

<div class="fv-row mb-7">
    <label class="fs-6 fw-semibold mb-2">Alamat Lengkap</label>
    <textarea class="form-control" name="address" rows="3">{{ $supplier->address }}</textarea>
    <span class="text-danger error-text address_error_edit mt-2"></span>
</div>
