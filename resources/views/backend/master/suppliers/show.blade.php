<div class="row mb-7">
    <div class="col-lg-12">
        <label class="fw-semibold text-muted fs-6 mb-1">Nama Supplier</label>
        <div class="fw-bold fs-4 text-gray-800">{{ $supplier->name }}</div>
    </div>
</div>

<div class="row mb-7">
    <div class="col-lg-6">
        <label class="fw-semibold text-muted fs-6 mb-1">No. WhatsApp / Telepon</label>
        <div class="fw-bold fs-6 text-gray-800">
            @if ($supplier->phone)
                <a href="https://wa.me/{{ preg_replace('/[^0-9]/', '', $supplier->phone) }}" target="_blank"
                    class="text-success text-hover-primary">
                    <i class="ki-outline ki-whatsapp fs-5 text-success me-1"></i> {{ $supplier->phone }}
                </a>
            @else
                <span class="text-muted">Tidak ada data</span>
            @endif
        </div>
    </div>
    <div class="col-lg-6">
        <label class="fw-semibold text-muted fs-6 mb-1">Total Transaksi (PO)</label>
        <div class="fw-bold fs-6"><span class="badge badge-light-primary">{{ $supplier->purchases()->count() ?? 0 }}
                Transaksi</span></div>
    </div>
</div>

<div class="row mb-7">
    <div class="col-lg-12">
        <label class="fw-semibold text-muted fs-6 mb-1">Alamat Lengkap</label>
        <div class="fw-bold fs-6 text-gray-800" style="white-space: pre-wrap;">
            {{ $supplier->address ?? 'Tidak ada data alamat.' }}</div>
    </div>
</div>

<div class="row border-top pt-5">
    <div class="col-lg-6">
        <label class="fw-semibold text-muted fs-7 mb-1">Terdaftar Sejak</label>
        <div class="fw-bold fs-7 text-gray-600">
            {{ \Carbon\Carbon::parse($supplier->created_at)->locale('id')->translatedFormat('d F Y, H:i') }}
        </div>
    </div>
</div>
