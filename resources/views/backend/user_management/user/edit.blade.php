<!---->
<input type="hidden" name="hidden_id" id="hidden_id" value="{{ $user->id }}" />
<!--begin::Input group-->
<div class="fv-row mb-7">


    <!--begin::Label-->
    <label for="editavatar" class="d-block fw-semibold fs-6 mb-5">Avatar</label>
    <!--end::Label-->
    <!--begin::Image placeholder-->

    <!--end::Image placeholder-->
    <!--begin::Image input-->
    <div class="image-input image-input-outline image-input-placeholder" data-kt-image-input="true">
        <!--begin::Preview existing avatar-->

        <div class="symbol symbol-125px symbol-125">
            @if (empty($user->avatar))
                <img id="preview-image-before-upload" src="{{ asset('assets/media/svg/files/blank-image.svg') }}"
                    alt="preview image" />
            @else
                <img id="preview-image-before-upload" src="{{ asset('storage/user/avatar/' . $user->avatar) }}"
                    alt="preview image" />
            @endif
        </div>

        <!--end::Preview existing avatar-->
        <!--begin::Label-->
        <label for="editavatar" class="btn btn-icon btn-circle btn-active-color-primary w-25px h-25px bg-body shadow"
            data-kt-image-input-action="change" data-bs-toggle="tooltip" title="Change avatar">
            <i class="bi bi-pencil-fill fs-7"></i>
            <!--begin::Inputs-->
            <input type="file" name="avatar" id="editavatar" accept=".png, .jpg, .jpeg"
                value="{{ $user->avatar }}" /> <!--end::Inputs-->
        </label>
        <!--end::Label-->
        <!--begin::Cancel-->
        <span class="btn btn-icon btn-circle btn-active-color-primary w-25px h-25px bg-body shadow"
            data-kt-image-input-action="cancel" data-bs-toggle="tooltip" title="Cancel avatar">
            <i class="bi bi-x fs-2"></i>
        </span>
        <!--end::Cancel-->
        <!--begin::Remove-->
        <span class="btn btn-icon btn-circle btn-active-color-primary w-25px h-25px bg-body shadow"
            data-kt-image-input-action="remove" data-bs-toggle="tooltip" title="Remove avatar">
            <i class="ki-outline ki-cross fs-2"></i>
        </span>
        <!--end::Remove-->
    </div>
    <!--end::Image input-->
    <!--begin::Hint-->
    <div class="form-text">Allowed file types: png, jpg, jpeg.</div>
    <span class="text-danger error-text avatar_error_edit"></span> <!--end::Hint-->
</div>
<!--end::Input group-->

<!--begin::Input group-->
<div class="fv-row mb-7">
    <!--begin::Label-->
    <label class="required fw-semibold fs-6 mb-2">Full Name</label>
    <!--end::Label-->
    <!--begin::Input-->
    <input type="text" name="name" id="Editname" value="{{ $user->name }}"
        class="form-control form-control-solid mb-3 mb-lg-0" placeholder="Full name" />
    <span class="text-danger error-text name_error_edit"></span> <!--end::Input-->
</div>
<!--end::Input group-->




<!--begin::Input group-->
<div class="fv-row mb-7">
    <!--begin::Label-->
    <label for="Editemail" class="required fw-semibold fs-6 mb-2">Email</label>
    <!--end::Label-->
    <!--begin::Input-->
    <input type="email" name="email" id="Editemail" value="{{ $user->email }}"
        class="form-control form-control-solid mb-3 mb-lg-0" placeholder="example@domain.com" />
    <span class="text-danger error-text email_error_edit"></span> <!--end::Input-->
</div>
<!--end::Input group-->
<!--begin::Input group-->
<div class="fv-row mb-7">
    <!--begin::Label-->
    <label for="Editpassword" class="required fw-semibold fs-6 mb-2">Password</label>
    <!--end::Label-->
    <!--begin::Input-->
    <input type="password" name="password" id="Editpassword" class="form-control form-control-solid mb-3 mb-lg-0"
        placeholder="Password" />
    <span class="text-danger error-text password_error_edit"></span> <!--end::Input-->
</div>
<!--end::Input group-->
<!--begin::Input group-->
<div class="fv-row mb-7">
    <!--begin::Label-->
    <label for="Editpassword_confirmation" class="required fw-semibold fs-6 mb-2">Confirm Password</label>
    <!--end::Label-->
    <!--begin::Input-->
    <input type="password" name="password_confirmation" id="Editpassword_confirmation" autocomplete="new-password"
        class="form-control form-control-solid mb-3 mb-lg-0" placeholder="Confirm Password" />
    <span class="text-danger error-text password_confirmation_error_edit"></span> <!--end::Input-->
</div>
<!--end::Input group-->





<!--begin::Input group-->
<div class="mb-7">
    <!--begin::Label-->
    <label for="Editroles" class="required fw-semibold fs-6 mb-2">Role</label>
    <!--end::Label-->
    <!--begin::Input-->
    <select class="form-control form-control-solid mb-3 mb-lg-0" name="roles[]" id="Editroles">
        <option selected="selected" disabled>Pilih Role</option>
        @foreach ($roles as $item)
            <option value="{{ $item->name }}"{{ in_array($item->name, $userRole) ? 'selected' : '' }}>
                {{ $item->name }}</option>
        @endforeach
    </select>
    <!--end::Input-->
    <span class="text-danger error-text roles_error_edit"></span> <!--end::Input-->
</div>


<!--end::Input group-->




<script type="text/javascript">
    $(document).ready(function(e) {

        $('#editavatar').change(function() {
            let reader = new FileReader();
            reader.onload = (e) => {
                $('#preview-image-before-upload').attr('src', e.target.result);
            }
            reader.readAsDataURL(this.files[0]);
        });
    });
</script>
