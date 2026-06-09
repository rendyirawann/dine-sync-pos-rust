<input type="hidden" id="edit_table_id" value="{{ $table->id }}">

<div class="fv-row mb-7">
    <label class="required fs-6 fw-semibold mb-2">Nomor/Nama Meja</label>
    <input type="text" class="form-control" name="table_number" value="{{ $table->table_number }}" />
    <span class="text-danger error-text table_number_error_edit mt-2"></span>
</div>

<div class="fv-row mb-7">
    <label class="required fs-6 fw-semibold mb-2">Kapasitas (Orang)</label>
    <input type="number" class="form-control" name="capacity" value="{{ $table->capacity }}" min="1" />
    <span class="text-danger error-text capacity_error_edit mt-2"></span>
</div>

<div class="fv-row mb-7">
    <label class="required fs-6 fw-semibold mb-2">Status Meja</label>
    <select name="status" class="form-select form-select-solid">
        <option value="available" {{ $table->status == 'available' ? 'selected' : '' }}>Tersedia (Kosong)</option>
        <option value="occupied" {{ $table->status == 'occupied' ? 'selected' : '' }}>Terisi (Ada Pelanggan)</option>
    </select>
</div>
