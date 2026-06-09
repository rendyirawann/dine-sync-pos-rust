<input type="hidden" name="hidden_id" id="hidden_id" value="{{ $user->id }}" />



<!--begin::Input group-->
<div class="fv-row mb-7">
    <!--begin::Label-->
    <label class="d-block fw-semibold fs-6 mb-5">Avatar</label>
    <!--end::Label-->
    <!--begin::Image placeholder-->
    <!--end::Image placeholder-->
    <!--begin::Image input-->
    <div class="image-input image-input-outline image-input-placeholder" data-kt-image-input="true">
        <!--begin::Preview existing avatar-->
        @if (empty($user->avatar))
            <!-- Jika avatar kosong, tampilkan gambar default -->
            <div class="symbol symbol-125px symbol-125">
                <img id="preview-image-before-upload" src="{{ URL::to('assets/media/svg/files/blank-image.svg') }}"
                    alt="" />
            </div>
        @else
            <div class="symbol symbol-125px symbol-125">
                <img id="preview-image-before-upload" src="{{ asset('storage/user/avatar/' . $user->avatar) }}"
                    alt="" />
            </div>
        @endif

        <!--end::Preview existing avatar-->
        <!--begin::Label-->
        <label class="btn btn-icon btn-circle btn-active-color-primary w-25px h-25px bg-body shadow"
            data-kt-image-input-action="change" data-bs-toggle="tooltip" title="Change avatar">
            <i class="bi bi-pencil-fill fs-7"></i>
            <!--begin::Inputs-->
            <input type="file" name="avatar" id="avatar" accept=".png, .jpg, .jpeg"
                value="{{ $user->avatar }}" />
            <!--end::Inputs-->
        </label>
        <!--end::Label-->
        <!--begin::Cancel-->
        <span class="btn btn-icon btn-circle btn-active-color-primary w-25px h-25px bg-body shadow"
            data-kt-image-input-action="cancel" data-bs-toggle="tooltip" title="Cancel avatar">
            <i class="bi bi-x fs-2"></i>
        </span>
        <!--end::Cancel-->
    </div>
    <!--end::Image input-->
    <!--begin::Hint-->
    <div class="form-text">Allowed file types: png, jpg, jpeg.</div>
    <!--end::Hint-->
    <span class="avatar-edit-invalid-feedback-changeavatar text-danger edit-show-validation-changeavatar"></span>
</div>
<!--end::Input group-->








<script type="text/javascript">
    $(document).ready(function(e) {


        $('#avatar').change(function() {

            let reader = new FileReader();

            reader.onload = (e) => {

                $('#preview-image-before-upload').attr('src', e.target.result);
            }

            reader.readAsDataURL(this.files[0]);

        });

    });
</script>
