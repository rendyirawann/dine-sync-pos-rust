<input type="hidden" id="edit_expense_id" value="{{ $expense->id }}">
<div class="fv-row mb-7">
    <label class="required fs-6 fw-semibold mb-2">Tanggal Pengeluaran</label>
    <input type="date" class="form-control" name="date" value="{{ $expense->date }}" />
</div>
<div class="fv-row mb-7">
    <label class="required fs-6 fw-semibold mb-2">Kategori Pengeluaran</label>
    <input type="text" class="form-control" name="category" value="{{ $expense->category }}" />
</div>
<div class="fv-row mb-7">
    <label class="required fs-6 fw-semibold mb-2">Nominal Uang Keluar</label>
    <div class="input-group">
        <span class="input-group-text border-danger bg-light-danger text-danger fw-bold">Rp</span>
        <input type="number" class="form-control border-danger fw-bold" name="amount" value="{{ $expense->amount }}"
            min="0" />
    </div>
</div>
<div class="fv-row mb-7">
    <label class="fs-6 fw-semibold mb-2">Keterangan / Catatan</label>
    <textarea class="form-control" name="notes" rows="3">{{ $expense->notes }}</textarea>
</div>
