<div class="d-flex flex-column gap-5">
    <div class="d-flex flex-column">
        <span class="fw-bold text-muted fs-6">Nomor / Nama Meja</span>
        <span class="fw-bolder text-gray-800 fs-3">{{ $table->table_number }}</span>
    </div>

    <div class="d-flex flex-column">
        <span class="fw-bold text-muted fs-6">Kapasitas</span>
        <span class="fw-bolder text-gray-800 fs-4">
            <i class="ki-outline ki-profile-user fs-4 me-1"></i> {{ $table->capacity }} Orang
        </span>
    </div>

    <div class="d-flex flex-column mb-3">
        <span class="fw-bold text-muted fs-6 mb-2">Status Saat Ini</span>
        <div>
            @if ($table->status == 'available')
                <span class="badge badge-light-success fs-5 px-4 py-2"><i
                        class="ki-outline ki-check fs-4 me-1 text-success"></i> Tersedia (Kosong)</span>
            @else
                <span class="badge badge-light-danger fs-5 px-4 py-2"><i
                        class="ki-outline ki-cross fs-4 me-1 text-danger"></i> Terisi (Ada Pelanggan)</span>
            @endif
        </div>
    </div>
</div>
