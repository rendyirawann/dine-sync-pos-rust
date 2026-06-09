<div class="row mb-7">
    <div class="col-lg-12">
        <div class="d-flex align-items-center bg-light-danger rounded p-5 mb-3">
            <i class="ki-outline ki-wallet fs-1 text-danger me-5"></i>
            <div class="flex-grow-1 me-2">
                <span class="text-muted fw-semibold d-block">Nominal Uang Keluar</span>
                <span class="fs-1 text-danger fw-bold">Rp {{ number_format($expense->amount, 0, ',', '.') }}</span>
            </div>
        </div>
    </div>
</div>

<div class="row mb-7">
    <div class="col-lg-6">
        <label class="fw-semibold text-muted fs-6 mb-1">Tanggal</label>
        <div class="fw-bold fs-5 text-gray-800">
            {{ \Carbon\Carbon::parse($expense->date)->translatedFormat('d F Y') }}</div>
    </div>
    <div class="col-lg-6">
        <label class="fw-semibold text-muted fs-6 mb-1">Dicatat Oleh</label>
        <div class="fw-bold fs-5">
            <span class="badge badge-light-primary"><i class="ki-outline ki-user fs-6 me-1"></i>
                {{ $expense->user->name ?? 'Admin' }}</span>
        </div>
    </div>
</div>

<div class="row mb-7 border-top pt-5">
    <div class="col-lg-12">
        <label class="fw-semibold text-muted fs-6 mb-1">Kategori Pengeluaran</label>
        <div class="fw-bold fs-4 text-gray-800">{{ $expense->category }}</div>
    </div>
</div>

<div class="row">
    <div class="col-lg-12">
        <label class="fw-semibold text-muted fs-6 mb-1">Catatan Lengkap</label>
        <div class="fw-bold fs-6 text-gray-700 bg-light p-4 rounded" style="white-space: pre-wrap;">
            {{ $expense->notes ?? 'Tidak ada catatan.' }}</div>
    </div>
</div>
