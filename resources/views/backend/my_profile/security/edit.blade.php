<input type="hidden" name="hidden_id" id="hidden_id" value="{{ $user->id }}" />




<!--begin::Input group-->
<div class="row g-9 mb-5">
    <!--begin::Col-->
    <div class="col-md-6 fv-row">
        <!--begin::Label-->
        <label class="required fs-5 fw-bold mb-2">Email</label>
        <!--end::Label-->
        <!--begin::Input-->
        <input type="text" class="form-control border border-1 border-dark rounded" name="email" id="editEmail"
            placeholder="" value="{{ $user->email }}" readonly />
        <span class="text-danger error-text email_error_edit"></span>

        <!--end::Input-->
    </div>
    <!--end::Col-->
    <!--begin::Col-->
    <div class="col-md-6 fv-row">
        <div class="fv-row mb-0">
            <label for="currentpassword" class="form-label fs-6 fw-bolder mb-2">Current Password</label>
            <input class="form-control border border-1 border-dark rounded mb-2" autocomplete="new-password"
                id="password" type="password" name="current_password" autocomplete="new-password" />
            <span class="text-danger error-text current_password_error_edit"></span>
            <div class="form-check form-switch form-check-custom form-check-solid me-10">
                <input class="form-check-input h-20px w-30px" type="checkbox" value=""
                    id="flexSwitch_current_password" />
                <label class="form-check-label" for="flexSwitch_current_password">
                    Show Password
                </label>
            </div>
        </div>
    </div>
    <!--end::Col-->
</div>
<!--end::Input group-->

<!--begin::Input group-->
<div class="row g-9 mb-5">
    <!--begin::Col-->
    <div class="col-md-6 fv-row">
        <div class="fv-row mb-0">
            <label for="newpassword" class="form-label fs-6 fw-bolder mb-3">New Password</label>
            <input class="form-control  border border-1 border-dark rounded mb-2" id="new_password" type="password"
                name="new_password" autocomplete="new-password" />
            <span class="text-danger error-text new_password_error_edit"></span>
            <div class="form-check form-switch form-check-custom form-check-solid me-10">
                <input class="form-check-input h-20px w-30px" type="checkbox" value=""
                    id="flexSwitch_new_password" />
                <label class="form-check-label" for="flexSwitch_new_password">
                    Show Password
                </label>
            </div>
            <div class="form-text mb-8">Password must be at least 8 character and contain symbols</div>
        </div>
    </div>
    <!--end::Col-->
    <!--begin::Col-->
    <div class="col-md-6 fv-row">
        <div class="fv-row mb-0">
            <label for="confirmpassword" class="form-label fs-6 fw-bolder mb-3">Confirm New Password</label>
            <input class="form-control  border border-1 border-dark rounded mb-2" id="new_confirm_password"
                type="password" name="new_confirm_password" autocomplete="new-password" />
            <span class="text-danger error-text new_confirm_password_error_edit"></span>
            <div class="form-check form-switch form-check-custom form-check-solid me-10">
                <input class="form-check-input h-20px w-30px" type="checkbox" value=""
                    id="flexSwitch_new_confirm_password" />
                <label class="form-check-label" for="flexSwitch_new_confirm_password">
                    Show Password
                </label>
            </div>
            <div class="form-text mb-8">Password must be at least 8 character and contain symbols</div>
        </div>
    </div>
    <!--end::Col-->
</div>
<!--end::Input group-->


<script>
    const flexSwitch_current_password = document.querySelector("#flexSwitch_current_password");
    const password = document.querySelector("#password");

    flexSwitch_current_password.addEventListener("click", function() {
        // toggle the type attribute
        const type = password.getAttribute("type") === "password" ? "text" : "password";
        password.setAttribute("type", type);

        // toggle the icon
    });


    const flexSwitch_new_password = document.querySelector("#flexSwitch_new_password");
    const new_password = document.querySelector("#new_password");

    flexSwitch_new_password.addEventListener("click", function() {
        // toggle the type attribute
        const type = new_password.getAttribute("type") === "password" ? "text" : "password";
        new_password.setAttribute("type", type);

        // toggle the icon
    });

    const flexSwitch_new_confirm_password = document.querySelector("#flexSwitch_new_confirm_password");
    const new_confirm_password = document.querySelector("#new_confirm_password");

    flexSwitch_new_confirm_password.addEventListener("click", function() {
        // toggle the type attribute
        const type = new_confirm_password.getAttribute("type") === "password" ? "text" : "password";
        new_confirm_password.setAttribute("type", type);

        // toggle the icon
    });
</script>
