@extends('backend.layout.app')
@section('title', 'My Account')
@section('content')

    <!--begin::Toolbar-->
    <div id="kt_app_toolbar" class="app-toolbar py-3 py-lg-0">
        <!--begin::Toolbar container-->
        <div id="kt_app_toolbar_container" class="app-container container-xxl d-flex flex-stack">
            <!--begin::Page title-->
            <div class="page-title d-flex flex-column justify-content-center me-3">
                <!--begin::Title-->
                <h1 class="page-heading d-flex text-gray-900 fw-bold fs-3 flex-column justify-content-center my-0">
                    Profile</h1>
                <!--end::Title-->
                <!--begin::Breadcrumb-->
                <ul class="breadcrumb breadcrumb-separatorless fw-semibold fs-7 my-0 pt-1">
                    <li class="breadcrumb-item text-gray-700 fw-bold lh-1">
                        <a href="{{ route('dashboard') }}" class="text-white text-hover-primary">
                            <i class="ki-outline ki-home text-gray-700 fs-6"></i>
                        </a>
                    </li>
                    <li class="breadcrumb-item">
                        <i class="ki-outline ki-right fs-5 text-gray-700 mx-n1"></i>
                    </li>
                    <li class="breadcrumb-item text-gray-700 fw-bold lh-1">Dashboards</li>
                    <li class="breadcrumb-item">
                        <i class="ki-outline ki-right fs-5 text-gray-700 mx-n1"></i>
                    </li>
                    <li class="breadcrumb-item text-muted">Resources</li>
                    <li class="breadcrumb-item">
                        <i class="ki-outline ki-right fs-5 text-gray-700 mx-n1"></i>
                    </li>
                    <li class="breadcrumb-item text-muted">Account</li>
                    <li class="breadcrumb-item">
                        <i class="ki-outline ki-right fs-5 text-gray-700 mx-n1"></i>
                    </li>
                    <li class="breadcrumb-item text-gray-900">My Profile</li>
                </ul>
                <!--end::Breadcrumb-->
            </div>
            <!--end::Page title-->

        </div>
        <!--end::Toolbar container-->
    </div>
    <!--end::Toolbar-->
    <!--begin::Content-->
    <div id="kt_app_content" class="app-content flex-column-fluid">
        <!--begin::Content container-->
        <div id="kt_app_content_container" class="app-container container-xxl">
            <div class="card mb-5 mb-xl-10">
                <div class="card-body pt-9 pb-0">
                    <div class="d-flex flex-wrap flex-sm-nowrap">
                        <div class="me-7 mb-4">
                            @if (empty(Auth::user()->avatar))
                                <div
                                    class="symbol symbol-100px symbol-lg-160px symbol-fixed position-relative avatar-wrapper">
                                    <div class="symbol-label fw-semibold bg-primary display-1 text-inverse-primary">
                                        {{ ucwords(substr(Auth::user()->name, 0, 1)) }}
                                    </div>
                                    <div
                                        class="position-absolute translate-middle bottom-0 start-100 mb-6 bg-success rounded-circle border border-4 border-body h-20px w-20px">
                                    </div>
                                </div>
                            @else
                                <div
                                    class="symbol symbol-100px symbol-lg-160px symbol-fixed position-relative avatar-wrapper">
                                    <img class="avatar-img" src="{{ asset('storage/user/avatar/' . Auth::user()->avatar) }}"
                                        alt="avatar" />
                                    <div
                                        class="position-absolute translate-middle bottom-0 start-100 mb-6 bg-success rounded-circle border border-4 border-body h-20px w-20px">
                                    </div>
                                </div>
                            @endif
                        </div>
                        <div class="flex-grow-1">
                            <div class="d-flex justify-content-between align-items-start flex-wrap mb-2">
                                <div class="d-flex flex-column">
                                    <div class="d-flex align-items-center mb-2">
                                        <a href="#"
                                            class="text-gray-900 text-hover-primary fs-2 fw-bold me-1 account-name">{{ ucwords(strtolower(Auth::user()->name)) }}</a>
                                        <a href="#">
                                            <i class="ki-outline ki-verify fs-1 text-primary"></i>
                                        </a>
                                    </div>
                                    <div class="d-flex flex-wrap fw-semibold fs-6 mb-4 pe-2">
                                        <a class="d-flex align-items-center text-gray-500 text-hover-primary me-5 mb-2">
                                            <i class="ki-outline ki-security-user fs-4 me-1"></i>
                                            {{ auth()->user()->getRoleNames()->first() ?? 'No Role' }}
                                        </a>

                                        <a
                                            class="d-flex align-items-center text-gray-500 text-hover-primary me-5 mb-2 account-no_wa">
                                            <i class="ki-outline ki-whatsapp fs-4 me-1"></i>{{ Auth::user()->no_wa }}
                                        </a>
                                        <a
                                            class="d-flex align-items-center text-gray-500 text-hover-primary me-5 mb-2 account-email">
                                            <i class="ki-outline ki-sms fs-4 me-1"></i>{{ Auth::user()->email }}
                                        </a>
                                    </div>
                                </div>
                                <div class="d-flex my-4">
                                    <a href="#" class="btn btn-sm btn-primary me-3" id="EditAvatar"
                                        data-id="{{ Auth::user()->id }}">Change Avatar</a>
                                </div>
                            </div>
                        </div>
                    </div>
                    <ul class="nav nav-stretch nav-line-tabs nav-line-tabs-2x border-transparent fs-5 fw-bold">
                        <li class="nav-item mt-2">
                            <a class="nav-link text-active-primary ms-0 me-10 py-5 {{ request()->routeIs('my-profile.index') ? 'active ' : '' }}"
                                href="{{ route('my-profile.index') }}">Overview</a>
                        </li>
                        <li class="nav-item mt-2">
                            <a class="nav-link text-active-primary ms-0 me-10 py-5 {{ request()->routeIs('my-security.index') ? 'active ' : '' }}"
                                href="{{ route('my-security.index') }}">Security</a>
                        </li>
                        <li class="nav-item mt-2">
                            <a class="nav-link text-active-primary ms-0 me-10 py-5 {{ request()->routeIs('my-activity.index') ? 'active ' : '' }}"
                                href="{{ route('my-activity.index') }}">Activity</a>
                        </li>
                        <li class="nav-item mt-2">
                            <a class="nav-link text-active-primary ms-0 me-10 py-5 {{ request()->routeIs('my-login-session.index') ? 'active ' : '' }}"
                                href="{{ route('my-login-session.index') }}">Logs</a>
                        </li>
                    </ul>
                </div>
            </div>
            @yield('mp')
        </div>
    </div>
    <div class="modal fade" id="Modal_Edit_Avatar" data-bs-backdrop="static" data-bs-keyboard="false" tabindex="-1"
        aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered mw-550px">
            <div class="modal-content" id="changeavatar-modal-content">
                <div class="modal-header border-gray-300">
                    <h2 class="fw-bold">Change Avatar</h2>
                    <div class="btn btn-icon btn-sm btn-active-icon-primary" data-bs-dismiss="modal">
                        <i class="ki-outline ki-cross fs-1 text-dark"></i>
                    </div>
                </div>
                <div class="modal-body px-5 my-7">
                    <form id="FormEditModalAvatarID" class="form" enctype="multipart/form-data">
                        @method('POST')
                        @csrf
                        <div class="d-flex flex-column scroll-y px-5 px-lg-10">
                            <div class="fv-row mb-7" id="ModalAvatar"></div>
                            <input type="hidden" name="action" id="action" />
                        </div>
                        <div class="text-center pt-10">
                            <button type="button" class="btn btn-sm btn-secondary me-3"
                                data-bs-dismiss="modal">Discard</button>
                            <button type="submit" class="btn btn-sm btn-primary" id="btn-change-avatar">
                                <span class="indicator-label">Submit</span>
                                <span class="indicator-progress">Please wait... <span
                                        class="spinner-border spinner-border-sm align-middle ms-2"></span></span>
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <div class="modal fade" id="Modal_Edit_Profile" data-bs-backdrop="static" data-bs-keyboard="false" tabindex="-1"
        aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered mw-650px">
            <div class="modal-content" id="changeprofile-modal-content">
                <div class="modal-header border-gray-300">
                    <h2 class="fw-bold">Edit Profile Details</h2>
                    <div class="btn btn-icon btn-sm btn-active-icon-primary" data-bs-dismiss="modal">
                        <i class="ki-outline ki-cross fs-1 text-dark"></i>
                    </div>
                </div>
                <div class="modal-body px-5 my-7">
                    <form id="FormEditModalProfileID" class="form">
                        @method('PUT')
                        @csrf
                        <div class="d-flex flex-column scroll-y px-5 px-lg-10">
                            <div id="ModalProfileData"></div>
                        </div>
                        <div class="text-center pt-10">
                            <button type="button" class="btn btn-sm btn-secondary me-3"
                                data-bs-dismiss="modal">Discard</button>
                            <button type="submit" class="btn btn-sm btn-primary" id="btn-save-profile">
                                <span class="indicator-label">Save Changes</span>
                                <span class="indicator-progress">Please wait... <span
                                        class="spinner-border spinner-border-sm align-middle ms-2"></span></span>
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

                // ==============================================
                // 1. FUNGSI CHANGE AVATAR
                // ==============================================
                var target_changeavatar = document.querySelector("#changeavatar-modal-content");
                var blockUIChangeavatar = new KTBlockUI(target_changeavatar, {
                    message: '<div class="blockui-message"><span class="spinner-border text-danger"></span> <span class="text-white">Mohon Sabar, Data Sedang Proses...</span></div>',
                    overlayClass: "bg-dark bg-opacity-50"
                });

                $('body').on('click', '#EditAvatar', function(e) {
                    e.preventDefault();
                    $('.alert-danger').html('').hide();
                    var id = $(this).data('id');

                    // MENGGUNAKAN URL ABSOLUT (/admin/...) AGAR TIDAK ERROR
                    $.ajax({
                        url: "{{ url('admin') }}/my-account/" + id + "/avatar",
                        type: "GET",
                        dataType: "json",
                        success: function(result) {
                            $('#ModalAvatar').html(result.html);
                            $('#Modal_Edit_Avatar').modal('show');
                        }
                    });
                });

                $('#FormEditModalAvatarID').on('submit', function(e) {
                    e.preventDefault();
                    var submitButton = document.querySelector("#btn-change-avatar");
                    submitButton.setAttribute("data-kt-indicator", "on");
                    submitButton.disabled = true;
                    blockUIChangeavatar.block();

                    var id = $('#hidden_id').val();
                    $.ajaxSetup({
                        headers: {
                            'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
                        }
                    });

                    // MENGGUNAKAN URL ABSOLUT (/admin/...)
                    $.ajax({
                        url: "{{ url('admin') }}/my-account/" + id + "/update-avatar",
                        method: "POST",
                        data: new FormData(this),
                        contentType: false,
                        cache: false,
                        processData: false,
                        dataType: "json",
                        success: function(result) {
                            handleResponse(result, submitButton);
                        },
                        error: function(xhr) {
                            submitButton.removeAttribute("data-kt-indicator");
                            submitButton.disabled = false;
                            blockUIChangeavatar.release();
                            Swal.fire('Error', "Terjadi Kesalahan: " + xhr.statusText, 'error');
                        }
                    });
                });

                function handleResponse(result, submitButton) {
                    if (result.errors) {
                        blockUIChangeavatar.release();
                        $.each(result.errors, function(fieldName, errorMessage) {
                            var inputField = $('#' + fieldName);
                            if (inputField.length > 0) {
                                inputField.addClass('is-invalid');
                                var feedbacEditkElement = $('.' + fieldName +
                                    '-edit-invalid-feedback-changeavatar');
                                feedbacEditkElement.html(errorMessage).removeClass('d-none');
                            }
                        });
                        Swal.fire({
                            title: 'Error',
                            text: 'Periksa kembali input Anda.',
                            icon: 'error',
                            timer: 1500
                        });
                    } else if (result.error) {
                        blockUIChangeavatar.release();
                        Swal.fire({
                            title: result.judul,
                            text: result.error,
                            icon: 'error',
                            timer: 1500
                        });
                    } else {
                        blockUIChangeavatar.release();
                        $('#Modal_Edit_Avatar').modal('hide');
                        Swal.fire({
                            text: result.success,
                            icon: "success",
                            timer: 1500
                        });

                        // Update Avatar DOM (Live)
                        if (result.avatar_url) {
                            if ($(".avatar-img").length === 0) {
                                $(".avatar-wrapper").html('<img class="avatar-img" src="' + result.avatar_url + '?v=' +
                                    new Date().getTime() + '" alt="avatar" />');
                            }
                            $(".avatar-img").attr("src", result.avatar_url + "?v=" + new Date().getTime());
                        }
                    }
                    submitButton.removeAttribute("data-kt-indicator");
                    submitButton.disabled = false;
                }

                // ==============================================
                // 2. FUNGSI EDIT PROFILE DATA (NEW)
                // ==============================================

                // Trigger saat tombol "Edit Profile" di klik (Berada di dalam view profile/index.blade.php)
                $('body').on('click', '#EditProfile', function(e) {
                    e.preventDefault();
                    $('.error-text').text(''); // Bersihkan error sebelumnya

                    // Ambil ID dari tombol, jika tidak ada fallback ke ID user Auth
                    var id = $(this).data('id');
                    if (!id) id = $('#EditAvatar').data('id');

                    $.ajax({
                        url: "{{ url('admin') }}/my-profile/" + id + "/edit",
                        type: "GET",
                        success: function(result) {
                            $('#ModalProfileData').html(result.html);
                            $('#Modal_Edit_Profile').modal('show');
                        },
                        error: function(xhr) {
                            Swal.fire('Gagal', 'Gagal memuat form edit.', 'error');
                        }
                    });
                });

                // Trigger submit form Edit Profile
                $('#FormEditModalProfileID').on('submit', function(e) {
                    e.preventDefault();
                    var submitBtn = document.querySelector("#btn-save-profile");
                    submitBtn.setAttribute("data-kt-indicator", "on");
                    submitBtn.disabled = true;

                    var id = $('#hidden_id').val(); // Didapat dari form edit.blade.php

                    $.ajax({
                        url: "{{ url('admin') }}/my-profile/" + id,
                        type: "POST", // Menggunakan POST tapi @method('PUT') dari form
                        data: $(this).serialize(),
                        success: function(res) {
                            submitBtn.removeAttribute("data-kt-indicator");
                            submitBtn.disabled = false;

                            if (res.errors) {
                                // Tampilkan pesan validasi error (merah) di bawah input
                                $.each(res.errors, function(key, val) {
                                    $('.' + key + '_error_edit').text(val[0]);
                                });
                            } else if (res.error) {
                                Swal.fire({
                                    icon: 'error',
                                    title: res.judul,
                                    text: res.error
                                });
                            } else {
                                $('#Modal_Edit_Profile').modal('hide');
                                Swal.fire({
                                    icon: 'success',
                                    title: 'Berhasil',
                                    text: res.success,
                                    timer: 1500
                                });

                                // Update DOM Teks di Card Header Profil (Live tanpa reload)
                                $('.account-name').text(res.updated.name);
                                $('.account-no_wa').html(
                                    '<i class="ki-outline ki-whatsapp fs-4 me-1"></i>' + res
                                    .updated.no_wa);
                                $('.account-email').html(
                                    '<i class="ki-outline ki-sms fs-4 me-1"></i>' + res.updated
                                    .email);

                                // Untuk memastikan isi konten di tab overview terganti sempurna, reload page setelah delay
                                setTimeout(function() {
                                    location.reload();
                                }, 1500);
                            }
                        },
                        error: function(xhr) {
                            submitBtn.removeAttribute("data-kt-indicator");
                            submitBtn.disabled = false;
                            Swal.fire({
                                icon: 'error',
                                title: 'Error',
                                text: 'Terjadi kesalahan internal sistem.'
                            });
                        }
                    });
                });

            });

            // ==============================================
            // 3. FUNGSI MOUSE DRAG MODAL (Agar modal bisa digeser)
            // ==============================================
            document.querySelectorAll('.modal').forEach(function(element) {
                dragElement(element);

                function dragElement(elmnt) {
                    let pos1 = 0,
                        pos2 = 0,
                        pos3 = 0,
                        pos4 = 0;
                    const header = elmnt.querySelector('.modal-header');
                    if (header) {
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
                        elmnt.style.position = "absolute";
                        elmnt.style.top = (elmnt.offsetTop - pos2) + "px";
                        elmnt.style.left = (elmnt.offsetLeft - pos1) + "px";
                    }

                    function closeDragElement() {
                        document.onmouseup = null;
                        document.onmousemove = null;
                    }
                }
            });
        </script>
    @endpush
@endsection
