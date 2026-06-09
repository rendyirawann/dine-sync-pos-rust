<form id="FormEditModalID" class="form">
    @csrf @method('PUT')
    <div class="modal-header">
        <h2 class="fw-bold">Edit Info Barang Keluar</h2>
        <div class="btn btn-icon btn-sm btn-active-icon-primary" data-bs-dismiss="modal"><i
                class="ki-outline ki-cross fs-1"></i></div>
    </div>
    <div class="modal-body scroll-y mx-5 my-7">
        <input type="hidden" id="edit_goodsout_id" value="{{ $goodsOut->id }}">
        <div class="fv-row mb-7">
            <label class="required fs-6 fw-semibold mb-2">Tanggal Keluar</label>
            <input type="date" class="form-control" name="date" value="{{ $goodsOut->date }}" required />
        </div>
        <div class="fv-row mb-7">
            <label class="required fs-6 fw-semibold mb-2">Tipe/Alasan Keluar</label>
            <select name="type" class="form-select form-select-solid" required>
                <option value="reject" {{ $goodsOut->type == 'reject' ? 'selected' : '' }}>Reject / Rusak / Hilang
                </option>
                <option value="online" {{ $goodsOut->type == 'online' ? 'selected' : '' }}>Terjual Online</option>
                <option value="adjustment" {{ $goodsOut->type == 'adjustment' ? 'selected' : '' }}>Penyesuaian (Sample
                    dll)</option>
            </select>
        </div>
        <div class="fv-row mb-7">
            <label class="fs-6 fw-semibold mb-2">Catatan Tambahan</label>
            <textarea class="form-control form-control-solid" name="notes" rows="3">{{ $goodsOut->notes }}</textarea>
        </div>
        <div class="text-center pt-10">
            <button type="submit" class="btn btn-primary w-100" id="btn-edit-data">Simpan Perubahan</button>
        </div>
    </div>
</form>
