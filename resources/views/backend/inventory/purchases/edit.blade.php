<input type="hidden" id="edit_purchase_id" value="{{ $purchase->id }}">

<div class="fv-row mb-7">
    <label class="required fs-6 fw-semibold mb-2">Tanggal Pembelian</label>
    <input type="date" class="form-control" name="purchase_date" value="{{ $purchase->purchase_date }}" required />
</div>
<div class="fv-row mb-7">
    <label class="fs-6 fw-semibold mb-2">Supplier</label>
    <select name="supplier_id" class="form-select form-select-solid" data-placeholder="Pilih Supplier"
        data-allow-clear="true">
        <option></option>
        @foreach ($suppliers as $sup)
            <option value="{{ $sup->id }}" {{ $purchase->supplier_id == $sup->id ? 'selected' : '' }}>
                {{ $sup->name }}</option>
        @endforeach
    </select>
</div>
