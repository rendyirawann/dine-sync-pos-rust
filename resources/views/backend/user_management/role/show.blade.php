<div class="fv-row mb-7">
    <label class="fs-6 fw-semibold mb-2">Role Name</label>
    <input type="text" class="form-control form-control-solid" value="{{ $role->name }}" readonly disabled />
</div>

<div class="fv-row mb-7">
    <label class="fs-6 fw-semibold mb-2">Guard Name</label>
    <input type="text" class="form-control form-control-solid" value="{{ $role->guard_name }}" readonly disabled />
</div>

<div class="fv-row mb-7">
    <label class="fs-6 fw-semibold mb-2">Permissions List</label>
    <div class="card card-flush border border-gray-300">
        <div class="card-body p-5">
            <div class="row">
                @forelse($permissions as $category => $categoryItems)
                    <div class="col-md-6 mb-5">
                        <h5 class="text-decoration-underline text-primary mb-3">{{ $category }}</h5>
                        <div class="d-flex flex-column gap-2">
                            @foreach ($categoryItems as $item)
                                <div class="d-flex align-items-center">
                                    <i class="ki-outline ki-check-circle fs-4 text-success me-2"></i>
                                    <span class="text-gray-700 fw-medium">{{ $item->name }}</span>
                                </div>
                            @endforeach
                        </div>
                    </div>
                @empty
                    <div class="col-12 text-center">
                        <span class="text-muted fst-italic">Tidak ada permission yang diberikan.</span>
                    </div>
                @endforelse
            </div>
        </div>
    </div>
</div>
