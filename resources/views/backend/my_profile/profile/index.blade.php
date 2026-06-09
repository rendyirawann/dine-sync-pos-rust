@extends('backend.my_profile.index')
@section('title', 'My Profile')
@section('mp')

    <div class="card mb-5 mb-xl-10 shadow-sm border border-gray-300" id="kt_profile_details_view">
        <!--begin::Card header-->
        <div class="card-header cursor-pointer border-bottom border-gray-300">
            <!--begin::Card title-->
            <div class="card-title m-0 ">
                <h3 class="fw-bold m-0">Profile Details</h3>
            </div>
            <!--end::Card title-->
            <!--begin::Action-->
            <a class="btn btn-sm btn-primary align-self-center" id="EditProfile" data-id="{{ Auth::user()->id }}">Edit
                Profile</a>
            <!--end::Action-->
        </div>
        <!--begin::Card header-->
        <!--begin::Card body-->
        <div class="card-body p-9">
            <!--begin::Row-->
            <div class="row mb-7">
                <!--begin::Label-->
                <label class="col-lg-4 fw-semibold text-muted">Full Name</label>
                <!--end::Label-->
                <!--begin::Col-->
                <div class="col-lg-8">
                    <span
                        class="fw-bold fs-6 text-gray-800 profile-name">{{ ucwords(strtolower(Auth::user()->name)) }}</span>
                </div>
                <!--end::Col-->
            </div>
            <!--end::Row-->
            <!--begin::Input group-->
            <div class="row mb-7">
                <!--begin::Label-->
                <label class="col-lg-4 fw-semibold text-muted">WhatsApp</label>
                <!--end::Label-->
                <!--begin::Col-->
                <div class="col-lg-8 fv-row">
                    <span class="fw-semibold text-gray-800 fs-6 profile-no-wa">{{ Auth::user()->no_wa }}</span>
                </div>
                <!--end::Col-->
            </div>
            <!--end::Input group-->

            <!--begin::Input group-->
            <div class="row mb-7">
                <!--begin::Label-->
                <label class="col-lg-4 fw-semibold text-muted">Email</label>
                <!--end::Label-->
                <!--begin::Col-->
                <div class="col-lg-8 fv-row">
                    <span class="fw-semibold text-gray-800 fs-6 profile-email">{{ Auth::user()->email }}</span>
                </div>
                <!--end::Col-->
            </div>
            <!--end::Input group-->


        </div>
        <!--end::Card body-->
    </div>



    <!-- Edit Article Modal -->
    <div class="modal fade" id="Modal_Edit_Data" data-bs-backdrop="static" data-bs-focus="false" data-bs-keyboard="false"
        tabindex="-1" aria-labelledby="staticBackdropLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered mw-750px">
            <div class="modal-content" id="edit-modal-content">
                <div class="modal-header border-bottom border-gray-300" id="kt_modal_edit_role_header">
                    <h2 class="fw-bold">Edit Profile</h2>
                    <!--begin::Close-->
                    <div class="btn btn-icon btn-sm btn-active-icon-primary " data-bs-dismiss="modal">
                        <i class="ki-outline ki-cross fs-1 text-dark"></i>
                    </div>
                    <!--end::Close-->
                </div>
                <div class="modal-body px-5 my-7">
                    <form id="FormEditModalID" class="form" enctype="multipart/form-data">
                        @method('PUT') @csrf
                        <div class="d-flex flex-column scroll-y px-5 px-lg-10" id="kt_modal_edit_role_scroll"
                            data-kt-scroll="true" data-kt-scroll-activate="true" data-kt-scroll-max-height="auto"
                            data-kt-scroll-dependencies="#kt_modal_edit_role_header"
                            data-kt-scroll-wrappers="#kt_modal_edit_role_scroll" data-kt-scroll-offset="300px">
                            <div class="fv-row mb-7" id="EditRowModalBody"></div>
                            <input type="hidden" name="action" id="action" />
                        </div>

                        <div class="text-center pt-10">
                            <button type="button" class="btn btn-sm btn-secondary me-3"
                                data-bs-dismiss="modal">Discard</button>
                            <button type="submit" class="btn btn-sm btn-primary" value="submit" id="btn-change-profile">
                                <span class="indicator-label">Submit</span>
                                <span class="indicator-progress">Please wait...
                                    <span class="spinner-border spinner-border-sm align-middle ms-2"></span></span>
                            </button>
                        </div>
                    </form>
                </div>

            </div>
        </div>
    </div>





    @push('stylesheets')
        <meta name="csrf-token" content="{{ csrf_token() }}">
    @endpush

    @push('scripts')
        <script type="text/javascript">
            $(document).ready(function() {
                var targetedit = document.querySelector("#edit-modal-content");
                var blockUIEdit = new KTBlockUI(targetedit, {
                    message: '<div class="blockui-message"><span class="spinner-border text-primary"></span> <span class="text-white">Please Wait ...</span></div>',
                    overlayClass: "bg-dark bg-opacity-50",
                });

                // Event untuk mengambil data saat tombol edit diklik
                $('body').on('click', '#getEditRowData', function(e) {
                    var id = $(this).data('id');

                    $.ajax({
                        url: "my-profile/" + id + "/edit",
                        dataType: "json",
                        success: function(result) {
                            console.log(result);
                            $('#EditRowModalBody').html(result.html);
                            $('#Modal_Edit_Data').modal('show');
                        }
                    });
                });

                // Update data via Ajax
                $('#FormEditModalID').on('submit', function(e) {
                    e.preventDefault();
                    var submitButton = document.querySelector("#btn-change-profile");
                    submitButton.setAttribute("data-kt-indicator", "on");
                    submitButton.disabled = true;
                    blockUIEdit.block(); // Block UI saat proses berjalan
                    var id = $('#hidden_id').val();

                    $.ajaxSetup({
                        headers: {
                            'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
                        }
                    });

                    $.ajax({
                        url: "my-profile/" + id,
                        method: "POST",
                        data: new FormData(this),
                        contentType: false,
                        cache: false,
                        processData: false,
                        dataType: "json",
                        success: function(result) {
                            handleResponse(result, submitButton); // Panggil handler untuk response
                        },
                        error: function(xhr) {
                            submitButton.removeAttribute("data-kt-indicator");
                            submitButton.disabled = false; // Enable button
                            blockUIEdit.release(); // Perbaiki blockUI
                            alert("Error: " + xhr.status + " " + xhr.statusText);
                        }
                    });
                });

                // Fungsi untuk menangani response
                function handleResponse(result, submitButton) {
                    if (result.errors) {
                        $.each(result.errors, function(prefix, val) {
                            $("span." + prefix + "_error_edit").text(val[0]);
                        });

                        Swal.fire({
                            title: "Error",
                            text: "Terjadi kesalahan validasi, periksa kembali input Anda.",
                            icon: "error",
                            timer: 1500,
                            confirmButtonText: "Ok",
                        });

                    } else if (result.error) {

                        $("#Modal_Edit_Data").modal("hide");

                        Swal.fire({
                            title: result.judul,
                            text: result.error,
                            icon: "error",
                            timer: 1500,
                            confirmButtonText: "Oke",
                        });

                    } else {




                        Swal.fire({
                            text: result.success,
                            icon: "success",
                            buttonsStyling: false,
                            confirmButtonText: "Ok, got it!",
                            timer: 1500,
                            customClass: {
                                confirmButton: "btn btn-primary",
                            },
                        });
                        $("#Modal_Edit_Data").modal("hide");
                        // ================================
                        // UPDATE PROFIL TANPA RELOAD
                        // ================================
                        if (result.updated) {

                            function ucwords(str) {
                                return str.toLowerCase().replace(/\b\w/g, function(c) {
                                    return c.toUpperCase();
                                });
                            }


                            $(".profile-name").text(ucwords(result.updated.name));
                            $(".profile-no-wa").text(result.updated.no_wa);
                            $(".profile-email").text(result.updated.email);
                            //NAVBAR


                            // Update navbar
                            $(".navbar-name").text(ucwords(result.updated.name));
                            $(".navbar-avatar-name").text(result.updated.name);
                            $(".navbar-avatar-sidebar-name").text(result.updated.name);
                            $(".navbar-sidebar-name").text(ucwords(result.updated.name));
                            $(".navbar-sidebar-no_wa").text(result.updated.no_wa);

                            $(".account-name").text(ucwords(result.updated.name));
                            $(".account-no_wa").text(result.updated.no_wa);
                            $(".account-email").text(result.updated.email);







                        }

                    }

                    submitButton.removeAttribute("data-kt-indicator"); // Hide "Please wait..."
                    submitButton.disabled = false; // Enable button
                    blockUIEdit.release();
                }
            });
        </script>

        <script>
            //DRAG MODAL

            // Make the DIV element draggable:
            document.querySelectorAll('#Modal_Edit_Data').forEach(function(element) {

                dragElement(element);

                function dragElement(elmnt) {
                    let pos1 = 0,
                        pos2 = 0,
                        pos3 = 0,
                        pos4 = 0;
                    const header = elmnt.querySelector('.modal-header');

                    if (header) {
                        // Only make the header draggable
                        header.onmousedown = dragMouseDown;
                    }

                    function dragMouseDown(e) {
                        e.preventDefault();
                        pos3 = e.clientX;
                        pos4 = e.clientY;
                        document.onmouseup = closeDragElement;
                        document.onmousemove = elementDrag;
                    }

                    function elementDrag(e) {
                        e.preventDefault();
                        pos1 = pos3 - e.clientX;
                        pos2 = pos4 - e.clientY;
                        pos3 = e.clientX;
                        pos4 = e.clientY;

                        // Move the modal
                        elmnt.style.position = "absolute";
                        elmnt.style.top = (elmnt.offsetTop - pos2) + "px";
                        elmnt.style.left = (elmnt.offsetLeft - pos1) + "px";
                    }

                    function closeDragElement() {
                        // Stop moving when mouse button is released
                        document.onmouseup = null;
                        document.onmousemove = null;
                    }
                }
            });
        </script>
    @endpush
@endsection
