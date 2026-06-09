<input type="hidden" name="hidden_id" id="hidden_id" value="{{ $role->id }}" />

<div class="fv-row mb-7">
    <label class="required fs-6 fw-semibold mb-2">Role Name</label>
    <input type="text" name="name" id="name" class="form-control form-control-solid mb-3 mb-lg-0"
        placeholder="Role Name" value="{{ $role->name }}" />
    <span class="text-danger error-text name_error_edit"></span>
</div>
<div class="fv-row mb-7">

    <div class="d-flex justify-content-between align-items-center mb-2">
        <label class="required fs-6 fw-semibold">Permissions</label>

        <label class="form-check form-check-sm form-check-custom me-2">
            <input class="form-check-input" type="checkbox" id="checkAllPermissionsEdit" />
            <span class="form-check-label fw-bold text-primary">Pilih Semua</span>
        </label>
    </div>

    <span class="text-danger error-text permission_error_edit"></span>

    <div class="row g-9 mb-8">
        @foreach ($permission as $category => $categoryItems)
            <div class="col-md-4 fv-row">
                <label class="fs-6 fw-semibold mb-2 text-decoration-underline">{{ $category }}</label>
                <div>
                    @foreach ($categoryItems as $item)
                        <label class="form-check form-check-sm form-check-custom mb-2 me-5 me-lg-2">
                            <input class="form-check-input permission-checkbox-edit" type="checkbox" name="permission[]"
                                id="{{ $item->id }}" value="{{ $item->id }}"
                                {{ in_array($item->id, $rolePermissions) ? 'checked' : '' }} />
                            <span class="form-check-label">{{ $item->name }}</span>
                        </label>
                    @endforeach
                </div>
            </div>
        @endforeach
    </div>
</div>
<script>
    $(document).ready(function() {

        // 1. Logic Saat Tombol "Pilih Semua" Diklik
        $('#checkAllPermissionsEdit').change(function() {
            var isChecked = $(this).is(':checked');
            $('.permission-checkbox-edit').prop('checked', isChecked);
        });

        // 2. Logic Saat Salah Satu Permission Di-uncheck Manual
        // (Tombol "Pilih Semua" harus ikut mati jika ada satu yg mati)
        $('.permission-checkbox-edit').change(function() {
            var totalCheckboxes = $('.permission-checkbox-edit').length;
            var totalChecked = $('.permission-checkbox-edit:checked').length;

            if (totalCheckboxes === totalChecked) {
                $('#checkAllPermissionsEdit').prop('checked', true);
            } else {
                $('#checkAllPermissionsEdit').prop('checked', false);
            }
        });

        // 3. Cek Status Awal saat Modal Dibuka
        // Jika semua permission User sudah tercentang, nyalakan "Pilih Semua"
        var totalCheckboxes = $('.permission-checkbox-edit').length;
        var totalChecked = $('.permission-checkbox-edit:checked').length;

        if (totalCheckboxes > 0 && totalCheckboxes === totalChecked) {
            $('#checkAllPermissionsEdit').prop('checked', true);
        }
    });
</script>
