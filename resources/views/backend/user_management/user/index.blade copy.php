@extends('backend.layout.app')
@section('title', 'User Management')
@section('content')
    <!--begin::Toolbar-->
    <div id="kt_app_toolbar" class="app-toolbar py-3 py-lg-0">
        <!--begin::Toolbar container-->
        <div id="kt_app_toolbar_container" class="app-container container-xxl d-flex flex-stack">
            <!--begin::Page title-->
            <div class="page-title d-flex flex-column justify-content-center me-3">
                <!--begin::Title-->
                <h1 class="page-heading d-flex text-gray-900 fw-bold fs-3 flex-column justify-content-center my-0">
                    User List</h1>
                <!--end::Title-->
                <!--begin::Breadcrumb-->
                <ul class="breadcrumb breadcrumb-separatorless fw-semibold fs-7 my-0 pt-1">
                    <!--begin::Item-->
                    <li class="breadcrumb-item text-gray-700 fw-bold lh-1">
                        <a href="index.html" class="text-white text-hover-primary">
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
                    <li class="breadcrumb-item text-muted">User Management</li>
                    <li class="breadcrumb-item">
                        <i class="ki-outline ki-right fs-5 text-gray-700 mx-n1"></i>
                    </li>
                    <li class="breadcrumb-item text-gray-900">User List</li>
                    <!--end::Item-->
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
            <div class="row gx-5 gx-xl-12">
                <div class="col-xxl-12 mb-5 mb-xl-12">
                    <div class="card card-flush h-xl-100">
                        <div class="card-header py-7">
                            <div class="card-title">
                                <div class="d-flex align-items-center position-relative my-1">
                                    <i class="ki-outline ki-magnifier fs-3 position-absolute ms-5"></i>
                                    <input type="text" data-kt-user-table-filter="search" id="search"
                                        class="form-control  w-250px ps-13" placeholder="Search user" />
                                </div>
                            </div>
                            <div class="card-toolbar">
                                {{-- Permission Check: Mass Delete --}}
                                @can('user.massdelete')
                                    <div class="d-flex justify-content-end align-items-center d-none me-3"
                                        data-kt-user-table-toolbar="selected">
                                        <div class="fw-bold me-5">
                                            <span class="me-2" data-kt-user-table-select="selected_count"></span>Selected
                                        </div>
                                        <button type="button" class="btn btn-sm btn-danger"
                                            data-kt-user-table-select="delete_selected"> <i
                                                class="ki-outline ki-trash  me-2"></i>Delete
                                            Selected</button>
                                    </div>
                                @endcan
                                <div class="d-flex justify-content-end" data-kt-user-table-toolbar="base">
                                    <button type="button" class="btn btn-sm btn-primary " id="refresh-table-btn">
                                        <span class="indicator-label">
                                            <i class="ki-outline ki-arrows-loop  me-2"></i> Refresh
                                        </span>
                                        <span class="indicator-progress">
                                            Please Wait ... <span
                                                class="spinner-border spinner-border-sm align-middle ms-2"></span>
                                        </span>
                                    </button>
                                </div>
                                <div class="d-flex align-items-center pt-4 pb-7 pt-lg-1 pb-lg-2">
                                    <div class="mx-3">
                                        <a href="#" class="btn btn-sm btn-flex btn-dark fw-bold"
                                            data-kt-menu-trigger="click" data-kt-menu-placement="bottom-end">
                                            <i class="ki-outline ki-filter fs-2  me-1"></i>Filter</a>
                                        <div class="menu menu-sub menu-sub-dropdown w-250px w-md-300px" data-kt-menu="true"
                                            id="kt_menu_66b9aa0df2f28">
                                            <div class="px-7 py-5">
                                                <div class="fs-5 text-gray-900 fw-bold">Filter Options</div>
                                            </div>
                                            <div class="separator border-gray-200"></div>
                                            <div class="px-7 py-5">
                                                <div class="mb-10">
                                                    <div class="form-label fs-6 fw-semibold">Role:</div>
                                                    <select id="filterrole" class="form-select form-select-sm "
                                                        data-kt-select2="true" data-placeholder="Select option"
                                                        data-allow-clear="true" data-kt-user-table-filter="role"
                                                        data-hide-search="true">
                                                        <option>
                                                        </option>
                                                        @foreach ($roles as $role)
                                                            <option value="{{ $role->id }}">{{ __($role->name) }}
                                                            </option>
                                                        @endforeach
                                                    </select>
                                                </div>
                                                <div class="d-flex justify-content-end">
                                                    <button type="reset" id="btnResetSearch"
                                                        class="btn btn-sm btn-secondary fw-semibold me-2 px-6"
                                                        data-kt-menu-dismiss="true"
                                                        data-kt-user-table-filter="reset">Reset</button>
                                                    <button type="submit" id="btnFiterSubmitSearch"
                                                        class="btn btn-sm btn-primary fw-semibold px-6"
                                                        data-kt-menu-dismiss="true"
                                                        data-kt-user-table-filter="filter">Apply</button>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                    {{-- Permission Check: User Create --}}
                                    @can('user.create')
                                        <button type="button" id="btn_tambah_data" class="btn btn-sm btn-primary">
                                            <i class="ki-outline ki-plus fs-2"></i>Add</button>
                                    @endcan

                                </div>
                            </div>
                        </div>
                        <div class="card-body pt-0 pb-1">
                            <table class="table align-middle table-row-dashed fs-6 gy-5 chimox" id="chimox">
                                <thead>
                                    <tr class="text-start text-muted fw-bold fs-7 text-uppercase gs-0">
                                        {{-- Permission Check: Mass Delete Checkbox --}}
                                        @can('user.massdelete')
                                            <th class="w-10px pe-2">
                                                <div class="form-check form-check-sm form-check-custom  me-3">
                                                    <input class="form-check-input" type="checkbox" data-kt-check="true"
                                                        data-kt-check-target="#chimox .form-check-input" value="1" />
                                                </div>
                                            </th>
                                        @endcan
                                        <th class="min-w-125px">Username</th>
                                        <th class="min-w-100px">Role</th>
                                        <th class="min-w-100px">Last login</th>
                                        <th class="min-w-100px">Last IP Adress</th>
                                        <th class="min-w-100px">Joined Date</th>
                                        <th class="min-w-100px">Status</th>

                                        {{-- Permission Check: Any Action --}}
                                        @canany(['user.show', 'user.edit', 'user.delete', 'user.ban'])
                                            <th class="text-end min-w-100px">Action</th>
                                        @endcanany
                                    </tr>
                                </thead>
                                <tbody class="text-gray-600 fw-semibold">
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    @can('user.create')
        <div class="modal fade" id="Modal_Tambah_Data" tabindex="-1" aria-hidden="true">
            <div class="modal-dialog modal-dialog-centered mw-750px">
                <div class="modal-content" id="tambah-modal-content">
                    <div class="modal-header border-gray-300" id="kt_modal_add_user_header">
                        <h2 class="fw-bold">Add User</h2>
                        <div class="btn btn-icon btn-sm btn-active-icon-primary" data-bs-dismiss="modal"
                            onclick="resetForm()">
                            <i class="ki-outline ki-cross fs-1 text-dark"></i>
                        </div>
                    </div>
                    <div class="modal-body px-5 my-7">
                        <form method="post" id="FormTambahModalID" class="form" enctype="multipart/form-data">
                            @csrf
                            <div class="d-flex flex-column scroll-y px-5 px-lg-10" id="kt_modal_add_user_scroll"
                                data-kt-scroll="true" data-kt-scroll-activate="true" data-kt-scroll-max-height="auto"
                                data-kt-scroll-dependencies="#kt_modal_add_user_header"
                                data-kt-scroll-wrappers="#kt_modal_add_user_scroll" data-kt-scroll-offset="300px">
                                <div class="fv-row mb-7">
                                    <label class="d-block fw-semibold fs-6 mb-5">Avatar</label>
                                    <style>
                                        .image-input-placeholder {
                                            background-image: url('{{ URL::to('assets/media/svg/files/blank-image.svg') }}');
                                        }

                                        [data-bs-theme="dark"] .image-input-placeholder {
                                            background-image: url('{{ URL::to('assets/media/svg/files/blank-image-dark.svg') }}');
                                        }
                                    </style>
                                    <div class="image-input image-input-outline image-input-placeholder"
                                        data-kt-image-input="true">
                                        <div class="image-input-wrapper w-125px h-125px" id="default-image"
                                            style="background-image: url({{ URL::to('assets/media/svg/files/blank-image.svg') }});">
                                        </div>
                                        <label
                                            class="btn btn-icon btn-circle btn-active-color-primary w-25px h-25px bg-body shadow"
                                            data-kt-image-input-action="change" data-bs-toggle="tooltip"
                                            title="Change avatar">
                                            <i class="ki-outline ki-pencil fs-7"></i>
                                            <input type="file" name="avatar" id="avatar"
                                                accept=".png, .jpg, .jpeg" />
                                            <input type="hidden" name="avatar_remove" />
                                        </label>
                                        <span
                                            class="btn btn-icon btn-circle btn-active-color-primary w-25px h-25px bg-body shadow"
                                            data-kt-image-input-action="cancel" data-bs-toggle="tooltip"
                                            title="Cancel avatar">
                                            <i class="ki-outline ki-cross fs-2"></i>
                                        </span>
                                        <span
                                            class="btn btn-icon btn-circle btn-active-color-primary w-25px h-25px bg-body shadow"
                                            data-kt-image-input-action="remove" data-bs-toggle="tooltip"
                                            title="Remove avatar">
                                            <i class="ki-outline ki-cross fs-2"></i>
                                        </span>
                                    </div>
                                    <div class="form-text">Allowed file types: png, jpg, jpeg.</div>
                                    <span class="text-danger error-text avatar_error_add"></span>
                                </div>
                                <div class="fv-row mb-7">
                                    <label class="required fw-semibold fs-6 mb-2">Full Name</label>
                                    <input type="text" name="name" id="name" class="form-control mb-3 mb-lg-0"
                                        placeholder="Full name" />
                                    <span class="text-danger error-text name_error_add"></span>
                                </div>
                                <div class="fv-row mb-7">
                                    <label class="required fw-semibold fs-6 mb-2">Email</label>
                                    <input type="email" name="email" id="email" class="form-control mb-3 mb-lg-0"
                                        placeholder="example@domain.com" />
                                    <span class="text-danger error-text email_error_add"></span>
                                </div>
                                <div class="fv-row mb-7">
                                    <label class="required fw-semibold fs-6 mb-2">WhatsApp</label>
                                    <input type="teks" name="no_wa" id="no_wa" class="form-control mb-3 mb-lg-0"
                                        placeholder="081273812533" />
                                    <span class="text-danger error-text no_wa_error_add"></span>
                                </div>
                                <div class="fv-row mb-7">
                                    <label for="password" class="required fw-semibold fs-6 mb-2">Password</label>
                                    <input type="password" name="password" id="password" class="form-control  mb-3 mb-lg-0"
                                        placeholder="Password" />
                                    <span class="text-danger error-text password_error_add"></span>
                                </div>
                                <div class="fv-row mb-7">
                                    <label for="password_confirmation" class="required fw-semibold fs-6 mb-2">Confirm
                                        Password</label>
                                    <input type="password" name="password_confirmation" id="password_confirmation"
                                        class="form-control  mb-3 mb-lg-0" placeholder="Confirm Password" />
                                    <span class="text-danger error-text password_confirmation_error_add"></span>
                                </div>
                                <div class="mb-5">
                                    <label class="required fw-semibold fs-6 mb-5">Role</label>
                                    <select class="form-control mb-3 mb-lg-0" name="roles" id="roles">
                                        <option selected="selected" disabled>Pilih Role</option>
                                        @foreach ($roles as $item)
                                            <option value="{{ $item->name }}">{{ $item->name }}</option>
                                        @endforeach
                                    </select>
                                    <span class="text-danger error-text roles_error_add"></span>
                                </div>
                            </div>
                            <div class="text-center pt-10">
                                <button type="reset" class="btn btn-sm btn-secondary me-3" data-bs-dismiss="modal"
                                    onclick="resetForm()">Discard</button>
                                <button type="submit" class="btn btn-sm btn-primary" id="btn-add-data">
                                    <span class="indicator-label add-data-label">Submit</span>
                                    <span class="indicator-progress add-data-progress" style="display: none;">Please Wait ...
                                        <span class="spinner-border spinner-border-sm align-middle ms-2"></span></span>
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    @endcan
    @can('user.edit')
        <div class="modal fade" id="Modal_Edit_Data" data-bs-backdrop="static" data-bs-keyboard="false" tabindex="-1"
            aria-labelledby="staticBackdropLabel" aria-hidden="true">
            <div class="modal-dialog modal-dialog-centered mw-750px">
                <div class="modal-content" id="edit-modal-content">
                    <div class="modal-header border-gray-300" id="kt_modal_edit_user_header">
                        <h2 class="fw-bold">Edit User</h2>
                        <div class="btn btn-icon btn-sm btn-active-icon-primary" data-bs-dismiss="modal">
                            <i class="ki-outline ki-cross fs-1 text-dark"></i>
                        </div>
                    </div>
                    <div class="modal-body px-5 my-7">
                        <form id="FormEditModalID" class="form" enctype="multipart/form-data">
                            @method('PUT')
                            @csrf
                            <div class="d-flex flex-column scroll-y px-5 px-lg-10" id="kt_modal_edit_user_scroll"
                                data-kt-scroll="true" data-kt-scroll-activate="true" data-kt-scroll-max-height="auto"
                                data-kt-scroll-dependencies="#kt_modal_edit_user_header"
                                data-kt-scroll-wrappers="#kt_modal_edit_user_scroll" data-kt-scroll-offset="300px">
                                <div class="fv-row mb-7" id="EditRowModalBody"></div>
                                <input type="hidden" name="action" id="action" />
                            </div>
                            <div class="text-center pt-10">
                                <button type="button" class="btn btn-sm btn-secondary me-3"
                                    data-bs-dismiss="modal">Discard</button>
                                <button type="submit" class="btn btn-sm btn-primary" id="btn-edit-data" value="submit">
                                    <span class="indicator-label edit-data-label">Submit</span>
                                    <span class="indicator-progress edit-data-progress" style="display: none;">Please Wait ...
                                        <span class="spinner-border spinner-border-sm align-middle ms-2"></span></span>
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    @endcan
    @can('user.delete')
        <div class="modal fade" id="Modal_Hapus_Data" data-bs-backdrop="static" data-bs-keyboard="false" tabindex="-1"
            aria-labelledby="staticBackdropLabel" aria-hidden="true">
            <div class="modal-dialog modal-dialog-centered">
                <div class="modal-content" id="hapus-modal-content">
                    <div class="modal-header border-gray-300">
                        <h2 class="modal-title">Delete User</h2>
                        <div class="btn btn-icon btn-sm btn-active-icon-primary" data-bs-dismiss="modal">
                            <i class="ki-outline ki-cross fs-1 text-dark"></i>
                        </div>
                    </div>
                    <div class="modal-body">
                        <p>Apakah Anda Yakin ingin menghapusnya ?</p>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-sm btn-secondary" data-bs-dismiss="modal">Discard</button>
                        <button type="button" class="btn btn-sm btn-primary" id="SubmitDeleteRowForm">
                            <span class="indicator-label delete-data-label">Submit</span>
                            <span class="indicator-progress delete-data-progress" style="display: none;">Please Wait ...
                                <span class="spinner-border spinner-border-sm align-middle ms-2"></span></span>
                        </button>
                    </div>
                </div>
            </div>
        </div>
    @endcan
    @can('user.ban')
        <div class="modal fade" id="ModalBanUser" tabindex="-1" aria-labelledby="ModalBanUserLabel" aria-hidden="true">
            <div class="modal-dialog modal-dialog-centered">
                <div class="modal-content">
                    <div class="modal-header border-gray-300">
                        <h3 class="modal-title" id="ModalBanUserLabel">Ban User</h3>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                        <input type="hidden" id="ban_user_id">
                        <div class="mb-4">
                            <label class="form-label fw-bold">Alasan</label>
                            <textarea id="ban_reason" class="form-control" rows="3" placeholder="Tuliskan alasan ban"></textarea>
                        </div>
                        <div class="mb-4">
                            <label class="form-label fw-bold">Durasi Ban</label>
                            <select id="ban_duration" class="form-select">
                                <option value="permanent">Ban Permanen</option>
                                <option value="1h">1 Jam</option>
                                <option value="24h">1 Hari</option>
                                <option value="1w">1 Minggu</option>
                            </select>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button class="btn btn-sm btn-light" data-bs-dismiss="modal">Batal</button>
                        <button class="btn btn-sm btn-danger" id="btnBanUser">Ban User</button>
                    </div>
                </div>
            </div>
        </div>
    @endcan

    @can('user.show')
        <div class="modal fade" id="Modal_Show_Data" tabindex="-1" aria-hidden="true">
            <div class="modal-dialog modal-dialog-centered mw-950px">
                <div class="modal-content">
                    <div class="modal-header border-gray-300">
                        <h2 class="fw-bold">Detail User</h2>
                        <div class="btn btn-icon btn-sm btn-active-icon-primary" data-bs-dismiss="modal">
                            <i class="ki-outline ki-cross fs-1 text-dark"></i>
                        </div>
                    </div>
                    <div class="modal-body px-5 my-7" id="ShowRowModalBody">
                    </div>
                    <div class="modal-footer border-gray-300">
                        <button type="button" class="btn btn-light" data-bs-dismiss="modal">Tutup</button>
                    </div>
                </div>
            </div>
        </div>
    @endcan

    @push('stylesheets')
        <meta name="csrf-token" content="{{ csrf_token() }}" />
        <link rel="stylesheet" href="{{ URL::to('assets/plugins/custom/datatables/datatables.bundle.css') }}" />
    @endpush
    @push('scripts')
        <script src="{{ URL::to('assets/plugins/custom/datatables/datatables.bundle.js') }}"></script>
        <script>
            function resetForm() {
                // Reset the form fields
                $("#FormTambahModalID").trigger('reset');
                // Reset avatar input
                $("#avatar").val('');
                // Clear error messages
                $(".error-text").text("");
                // Reset the background image for avatar (optional)
                $('#default-image').css('background-image', 'url({{ URL::to('assets/media/svg/files/blank-image.svg') }})');
            }
        </script>
        <script>
            $('#avatar').on('change', function() {
                var file = this.files[0];
                if (file) {
                    var reader = new FileReader();
                    reader.onload = function(e) {
                        $('#default-image').css('background-image', 'url(' + e.target.result + ')');
                    };
                    reader.onerror = function() {
                        alert("Failed to load image.");
                    };
                    reader.readAsDataURL(file);
                }
            });
        </script>
        <script type="text/javascript">
            function debounce(func, wait) {
                let timeout;
                return function(...args) {
                    clearTimeout(timeout);
                    timeout = setTimeout(() => func.apply(this, args), wait);
                };
            }
            $(document).ready(function() {
                // Passing permission variables to Javascript
                var canShow = @json(auth()->user()->can('user.show'));
                var canEdit = @json(auth()->user()->can('user.edit'));
                var canDelete = @json(auth()->user()->can('user.delete'));
                var canMassDelete = @json(auth()->user()->can('user.massdelete'));
                var canBan = @json(auth()->user()->can('user.ban')); // Permission baru

                var table = $('.chimox').DataTable({
                    processing: true,
                    language: {
                        processing: "Please Wait ...",
                        loadingRecords: false,
                        zeroRecords: "Tidak ada data yang ditemukan",
                        emptyTable: "Tidak ada data yang tersedia di tabel ini",
                        search: "Cari:",
                    },
                    serverSide: true,
                    order: false,
                    ajax: {
                        url: "{{ route('get-users') }}",
                        type: 'GET',
                        data: function(d) {
                            d.filterrole = $('#filterrole').val(); // Parameter tambahan untuk filtering
                        }
                    },
                    columns: [
                        // Kondisi untuk Mass Delete Checkbox
                        canMassDelete ? {
                            data: null,
                            orderable: false,
                            searchable: false,
                            render: function(data, type, full, meta) {
                                return '<div class="form-check form-check-sm form-check-custom ">' +
                                    '<input class="form-check-input" type="checkbox" value="' + full
                                    .id + '" />' +
                                    '</div>';
                            }
                        } : null,
                        {
                            data: 'avatar',
                            name: 'avatar',
                            orderable: false,
                            searchable: false
                        },
                        {
                            data: 'roles',
                            name: 'roles',
                            orderable: false,
                            searchable: false
                        },
                        {
                            data: 'last_login_at',
                            name: 'last_login_at',
                            orderable: false,
                            searchable: false
                        },
                        {
                            data: 'last_login_ip',
                            name: 'last_login_ip',
                            orderable: false,
                            searchable: false
                        },
                        {
                            data: 'joined_date',
                            name: 'joined_date',
                            orderable: false,
                            searchable: false
                        },
                        {
                            data: 'status',
                            name: 'status',
                            orderable: false,
                            searchable: false
                        },
                        // Kondisi untuk menampilkan kolom Action
                        (canShow || canEdit || canDelete || canBan) ? {
                            data: 'action',
                            name: 'action',
                            orderable: false,
                            searchable: false
                        } : null
                    ].filter(column => column !== null) // Filter untuk menghapus kolom null
                });
                $(document).ready(function() {
                    var button = document.querySelector("#refresh-table-btn");
                    $('#refresh-table-btn').on('click', function() {
                        // Disable the button to prevent further clicks
                        button.setAttribute("data-kt-indicator", "on");
                        button.disabled = true; // Disable the button
                        // Reload the DataTable
                        table.ajax.reload(function() {
                            // Re-enable the button after table is refreshed
                            button.removeAttribute("data-kt-indicator");
                            button.disabled = false; // Enable the button again
                        });
                    });
                });
                $('#search').on('keyup', debounce(function() {
                    var table = $('.chimox').DataTable();
                    table.search($(this).val()).draw();
                }, 500));
                $('#btnResetSearch').click(function() {
                    $('#filterrole').val(null).trigger('change');
                    table.draw(true);
                });
                $('#btnFiterSubmitSearch').click(function() {
                    table.draw(true);
                });

                // Permission Check for Add Button
                @if (auth()->user()->can('user.create'))
                    // SHOW MODAL TAMBAH DATA
                    $('#btn_tambah_data').click(function() {
                        $('#Modal_Tambah_Data').modal('show');
                        // Perbarui gambar default
                        $('#default-image').css('background-image',
                            'url({{ URL::to('assets/media/svg/files/blank-image.svg') }})');
                    });
                    var target = document.querySelector("#tambah-modal-content");
                    var blockUI = new KTBlockUI(target, {
                        message: '<div class="blockui-message"><span class="spinner-border text-primary"></span> <span class="text-white">Please Wait ...</span></div>',
                        overlayClass: "bg-dark bg-opacity-50",
                    });
                    $('#FormTambahModalID').on('submit', function(event) {
                        event.preventDefault();
                        blockUI.block();
                        $('#btn-add-data .add-data-label').hide();
                        $('#btn-add-data .add-data-progress').show();
                        $('#btn-add-data').prop('disabled', true);
                        $.ajaxSetup({
                            headers: {
                                'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
                            }
                        });
                        $.ajax({
                            url: "{{ route('users.store') }}",
                            method: 'post',
                            data: new FormData(this),
                            contentType: false,
                            cache: false,
                            processData: false,
                            dataType: "json",
                            beforeSend: function() {
                                $(document).find("span.error-text").text("");
                            },
                            success: function(result) {
                                if (result.errors) {
                                    setTimeout(function() {
                                        $.each(result.errors, function(prefix, val) {
                                            $("span." + prefix + "_error_add").text(
                                                val[
                                                    0]);
                                        });
                                        blockUI.release();
                                        Swal.fire({
                                            title: "Gagal",
                                            text: "Terjadi kesalahan validasi, periksa kembali input Anda.",
                                            icon: "error",
                                            timer: 1500,
                                            confirmButtonText: "Oke",
                                        });
                                        $('#btn-add-data .add-data-label').show();
                                        $('#btn-add-data .add-data-progress').hide();
                                        $('#btn-add-data').prop('disabled', false);
                                    }, 1000);
                                } else if (result.error) {
                                    setTimeout(function() {
                                        $("#Modal_Tambah_Data").modal("hide");
                                        blockUI.release();
                                        Swal.fire({
                                            title: result.judul,
                                            text: result.error,
                                            icon: "error",
                                            timer: 1500,
                                            confirmButtonText: "Oke",
                                        });
                                        $('#btn-add-data .add-data-label').show();
                                        $('#btn-add-data .add-data-progress').hide();
                                        $('#btn-add-data').prop('disabled', false);
                                    }, 1000);
                                } else {
                                    setTimeout(function() {
                                        $("#Modal_Tambah_Data").modal("hide");
                                        $(".chimox").DataTable().ajax.reload();
                                        blockUI.release();
                                        Swal.fire({
                                            title: "Berhasil",
                                            text: result.success,
                                            icon: "success",
                                            timer: 1500,
                                            confirmButtonText: "Oke",
                                        });
                                        $('#btn-add-data .add-data-label').show();
                                        $('#btn-add-data .add-data-progress').hide();
                                        $('#btn-add-data').prop('disabled', false);
                                    }, 1000);
                                }
                            },
                        });
                    });
                    // Tombol "Batal"
                    $("#Modal_Tambah_Data").on("hidden.bs.modal", function() {
                        resetForm();
                    });
                @endif

                @if (auth()->user()->can('user.edit'))
                    var targetedit = document.querySelector("#edit-modal-content");
                    var blockUIEdit = new KTBlockUI(targetedit, {
                        message: '<div class="blockui-message"><span class="spinner-border text-danger"></span> <span class="text-white">Please Wait ...</span></div>',
                        overlayClass: "bg-dark bg-opacity-50"
                    });
                    // EDIT MODAL
                    $("body").on("click", ".btn-edit", function(e) {
                        e.preventDefault(); // Mencegah reload
                        var id = $(this).data("id"); // Ambil ID dari data-id

                        $.ajax({
                            url: "{{ url('admin') }}/users/" + id + "/edit", // Gunakan Absolute Path agar aman
                            type: "GET",
                            dataType: "json",
                            success: function(result) {
                                console.log(result);
                                $("#EditRowModalBody").html(result.html);
                                $("#Modal_Edit_Data").modal("show");
                            },
                            error: function(xhr, status, error) {
                                Swal.fire("Error", "Gagal mengambil data edit.", "error");
                            }
                        });
                    });
                    // UPDATE MODAL
                    $('#FormEditModalID').on('submit', function(e) {
                        e.preventDefault();
                        blockUIEdit.block();
                        $('#btn-edit-data .edit-data-label').hide();
                        $('#btn-edit-data .edit-data-progress').show();
                        $('#btn-edit-data').prop('disabled', true);
                        var id = $('#hidden_id').val();
                        $.ajaxSetup({
                            headers: {
                                'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
                            }
                        });
                        $.ajax({
                            url: "{{ url('admin') }}/users/" + id,
                            method: "POST",
                            data: new FormData(this),
                            contentType: false,
                            cache: false,
                            processData: false,
                            dataType: "json",
                            beforeSend: function() {
                                $(document).find("span.error-text").text("");
                            },
                            success: function(result) {
                                if (result.errors) {
                                    setTimeout(function() {
                                        blockUIEdit.release();
                                        $.each(result.errors, function(prefix, val) {
                                            $("span." + prefix + "_error_edit")
                                                .text(
                                                    val[0]);
                                        });
                                        Swal.fire({
                                            title: "Error",
                                            text: "Terjadi kesalahan validasi, periksa kembali input Anda.",
                                            icon: "error",
                                            timer: 1500,
                                            confirmButtonText: "Ok",
                                        });
                                        $('#btn-edit-data .edit-data-label').show();
                                        $('#btn-edit-data .edit-data-progress').hide();
                                        $('#btn-edit-data').prop('disabled', false);
                                    }, 1000);
                                } else if (result.error) {
                                    setTimeout(function() {
                                        $("#Modal_Edit_Data").modal("hide");
                                        blockUIEdit.release();
                                        Swal.fire({
                                            title: result.judul,
                                            text: result.error,
                                            icon: "error",
                                            timer: 1500,
                                            confirmButtonText: "Oke",
                                        });
                                        $('#btn-edit-data .edit-data-label').show();
                                        $('#btn-edit-data .edit-data-progress').hide();
                                        $('#btn-edit-data').prop('disabled', false);
                                    }, 1000);
                                } else {
                                    setTimeout(function() {
                                        $("#Modal_Edit_Data").modal("hide");
                                        $(".chimox").DataTable().ajax.reload();
                                        blockUIEdit.release();

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
                                        $('#btn-edit-data .edit-data-label').show();
                                        $('#btn-edit-data .edit-data-progress').hide();
                                        $('#btn-edit-data').prop('disabled', false);


                                    }, 1000);
                                }
                            },
                        });
                    });
                @endif

                @if (auth()->user()->can('user.delete'))
                    var targethapus = document.querySelector("#hapus-modal-content");
                    var blockUIHapus = new KTBlockUI(targethapus, {
                        message: '<div class="blockui-message"><span class="spinner-border text-primary"></span> <span class="text-white">Please Wait ...</span></div>',
                        overlayClass: "bg-dark bg-opacity-50"
                    });
                    // Delete article Ajax request.
                    var deleteID;
                    $('body').on('click', '#getDeleteId', function() {
                        deleteID = $(this).data('id');
                    })
                    $('#SubmitDeleteRowForm').click(function(e) {
                        e.preventDefault();
                        blockUIHapus.block();
                        $('#SubmitDeleteRowForm .delete-data-label').hide();
                        $('#SubmitDeleteRowForm .delete-data-progress').show();
                        $('#SubmitDeleteRowForm').prop('disabled', true);
                        var id = deleteID;
                        $.ajaxSetup({
                            headers: {
                                'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
                            }
                        });
                        $.ajax({
                            url: "{{ url('admin/users') }}/" + id,
                            method: 'DELETE',
                            success: function(result) {
                                if (result.error) {
                                    setTimeout(function() {
                                        $("#Modal_Hapus_Data").modal("hide");
                                        blockUIHapus.release();
                                        Swal.fire({
                                            title: result.judul,
                                            text: result.error,
                                            icon: "error",
                                            timer: 1500,
                                            confirmButtonText: "Oke",
                                        });
                                        $('#SubmitDeleteRowForm .delete-data-label').show();
                                        $('#SubmitDeleteRowForm .delete-data-progress')
                                            .hide();
                                        $('#SubmitDeleteRowForm').prop('disabled', false);
                                    }, 1000);
                                } else {
                                    setTimeout(function() {
                                        $("#Modal_Hapus_Data").modal("hide");
                                        $(".chimox").DataTable().ajax.reload();
                                        blockUIHapus.release();
                                        Swal.fire({
                                            text: result.success,
                                            icon: "success",
                                            buttonsStyling: false,
                                            confirmButtonText: "Ok, got it!",
                                            timer: 1500,
                                            customClass: {
                                                confirmButton: "btn btn-primary",
                                            }
                                        });
                                        $('#SubmitDeleteRowForm .delete-data-label').show();
                                        $('#SubmitDeleteRowForm .delete-data-progress')
                                            .hide();
                                        $('#SubmitDeleteRowForm').prop('disabled', false);

                                    }, 1000);
                                }
                            },
                        });
                    });
                @endif

                // ==================
                // 2. LOGIC SHOW DETAIL (MODAL)
                // ==================
                @if (auth()->user()->can('user.show'))
                    $('body').on('click', '.btn-detail', function() {
                        var id = $(this).data('id');

                        // Tampilkan Modal & Loading Spinner
                        $('#Modal_Show_Data').modal('show');
                        $('#ShowRowModalBody').html(`
        <div class="d-flex justify-content-center py-10">
            <div class="spinner-border text-primary" role="status">
                <span class="visually-hidden">Loading...</span>
            </div>
        </div>
    `);

                        // Fetch Data
                        $.ajax({
                            url: "{{ url('admin') }}/users/" + id, // Route Resource otomatis ke 'show'
                            method: "GET",
                            success: function(response) {
                                // Masukkan HTML dari server ke body modal
                                $('#ShowRowModalBody').html(response.html);
                            },
                            error: function() {
                                $('#ShowRowModalBody').html(
                                    '<div class="text-center text-danger py-5">Gagal mengambil data user.</div>'
                                );
                            }
                        });
                    });
                @endif

                // Function to handle individual checkbox change event
                $('.chimox').on('change', 'input.form-check-input', function() {
                    updateToolbar();
                    // Check if all checkboxes are selected
                    var allChecked = $('.chimox tbody input.form-check-input').length === $(
                        '.chimox tbody input.form-check-input:checked').length;
                    // Update the "Select All" checkbox
                    $('[data-kt-check]').prop('checked', allChecked);
                });
                // Function to handle the "Select All" checkbox
                $('[data-kt-check]').on('change', function() {
                    var isChecked = $(this).is(':checked');
                    var target = $(this).data('kt-check-target');
                    // Check/uncheck all checkboxes in the target
                    $(target).prop('checked', isChecked);
                    // Update toolbar display
                    updateToolbar();
                });
                // Function to update the toolbar based on the selected checkboxes
                function updateToolbar() {
                    var selectedCount = $('.chimox tbody input.form-check-input:checked').length;

                    // Update the count in the toolbar
                    $('[data-kt-user-table-select="selected_count"]').text(selectedCount);

                    if (selectedCount > 0) {
                        // Show the toolbar if there are selected checkboxes
                        $('[data-kt-user-table-toolbar="selected"]').removeClass('d-none');
                    } else {
                        // Hide the toolbar if no checkbox is selected
                        $('[data-kt-user-table-toolbar="selected"]').addClass('d-none');
                    }
                }
                // Function to handle checkbox change event
                $('.chimox').on('change', 'input.form-check-input', function() {
                    var selectedCount = $('.chimox tbody input.form-check-input:checked').length;

                    // Update selected count
                    $('[data-kt-user-table-select="selected_count"]').text(selectedCount);

                    if (selectedCount > 0) {
                        // Remove the d-none class to show the toolbar if any checkbox is selected
                        $('[data-kt-user-table-toolbar="selected"]').removeClass('d-none');
                    } else {
                        // Add the d-none class to hide the toolbar if no checkbox is selected
                        $('[data-kt-user-table-toolbar="selected"]').addClass('d-none');
                    }
                });
                @if (auth()->user()->can('user.massdelete'))
                    $('button[data-kt-user-table-select="delete_selected"]').on('click', function() {
                        var selectedIds = [];
                        // Get all selected checkboxes
                        $('.chimox tbody input.form-check-input:checked').each(function() {
                            selectedIds.push($(this).val()); // Collect the user IDs
                        });
                        if (selectedIds.length > 0) {
                            // Confirm before deleting
                            Swal.fire({
                                title: 'Are you sure?',
                                text: 'You are about to delete ' + selectedIds.length + ' users.',
                                icon: 'warning',
                                showCancelButton: true,
                                confirmButtonText: 'Yes, delete!',
                                cancelButtonText: 'No, cancel!',

                                customClass: {
                                    confirmButton: "btn btn-sm btn-primary",
                                    cancelButton: "btn btn-sm btn-secondary",
                                }
                            }).then(function(result) {
                                if (result.isConfirmed) {
                                    // Make an AJAX call to mass delete the users
                                    $.ajax({
                                        url: "{{ route('users.mass-delete') }}", // Pastikan route ini ada
                                        type: 'POST',
                                        data: {
                                            ids: selectedIds,
                                            _token: '{{ csrf_token() }}' // CSRF token for security
                                        },
                                        success: function(response) {
                                            if (response.status === 'success') {
                                                Swal.fire({
                                                    title: 'Deleted!',
                                                    text: response.message,
                                                    icon: 'success',
                                                    timer: 1500, // Timer harus ditempatkan di dalam objek konfigurasi
                                                });
                                                // Reload the DataTable to reflect changes
                                                table.ajax.reload();

                                                // Reset the toolbar and uncheck the "Select All" checkbox
                                                $('[data-kt-user-table-toolbar="selected"]')
                                                    .addClass('d-none');
                                                $('[data-kt-user-table-select="selected_count"]')
                                                    .text(0);

                                                // Uncheck "Select All" checkbox
                                                $('[data-kt-check]').prop('checked', false);
                                            } else {
                                                Swal.fire('Error!', response.message,
                                                    'error');
                                            }
                                        },
                                        error: function() {
                                            Swal.fire('Error!',
                                                'An error occurred while deleting users.',
                                                'error');
                                        }
                                    });
                                }
                            });
                        } else {
                            Swal.fire('Warning!', 'No users selected for deletion.', 'warning');
                        }
                    });
                @endif
            });
            // Make the DIV element draggable:
            var elements = document.querySelectorAll(
                '#Modal_Tambah_Data, #Modal_Edit_Data, #Modal_Hapus_Data, #Modal_Show_Data');
            elements.forEach(function(element) {
                dragElement(element);

                function dragElement(elmnt) {
                    var pos1 = 0,
                        pos2 = 0,
                        pos3 = 0,
                        pos4 = 0;
                    if (elmnt.querySelector('.modal-header')) {
                        // if present, the header is where you move the DIV from:
                        elmnt.querySelector('.modal-header').onmousedown = dragMouseDown;
                    } else {
                        // otherwise, move the DIV from anywhere inside the DIV:
                        elmnt.onmousedown = dragMouseDown;
                    }

                    function dragMouseDown(e) {
                        e = e || window.event;
                        // get the mouse cursor position at startup:
                        pos3 = e.clientX;
                        pos4 = e.clientY;
                        document.onmouseup = closeDragElement;
                        // call a function whenever the cursor moves:
                        document.onmousemove = elementDrag;
                    }

                    function elementDrag(e) {
                        e = e || window.event;
                        // calculate the new cursor position:
                        pos1 = pos3 - e.clientX;
                        pos2 = pos4 - e.clientY;
                        pos3 = e.clientX;
                        pos4 = e.clientY;
                        // set the element's new position:
                        elmnt.style.top = (elmnt.offsetTop - pos2) + "px";
                        elmnt.style.left = (elmnt.offsetLeft - pos1) + "px";
                    }

                    function closeDragElement() {
                        // stop moving when mouse button is released:
                        document.onmouseup = null;
                        document.onmousemove = null;
                    }
                }
            });
        </script>

        <script>
            @if (auth()->user()->can('user.ban'))
                function openBanModal(id) {
                    $('#ban_user_id').val(id);
                    $('#ban_reason').val('');
                    $('#ban_duration').val('permanent');
                    $('#ModalBanUser').modal('show');
                }

                $('#btnBanUser').click(function() {

                    let id = $('#ban_user_id').val();
                    let reason = $('#ban_reason').val();
                    let duration = $('#ban_duration').val();

                    if (reason.trim() === "") {
                        Swal.fire("Peringatan!", "Alasan ban wajib diisi.", "warning");
                        return;
                    }

                    $.ajax({
                        url: `{{ url('admin') }}/users/${id}/ban`,
                        method: 'POST',
                        data: {
                            _token: $('meta[name="csrf-token"]').attr('content'),
                            reason: reason,
                            duration: duration
                        },
                        success: function(res) {
                            Swal.fire("Berhasil", res.success, "success");
                            $('#ModalBanUser').modal('hide');
                            $('#datatable').DataTable().ajax.reload();
                        },
                        error: function(xhr) {
                            Swal.fire("Error", "Gagal melakukan ban user!", "error");
                        }
                    });

                });

                function unbanUser(id) {

                    Swal.fire({
                        title: "Unban User?",
                        icon: "info",
                        showCancelButton: true,
                        confirmButtonText: "Ya, Unban",
                        cancelButtonText: "Batal",
                        customClass: {
                            confirmButton: "btn btn-primary btn-sm me-2",
                            cancelButton: "btn btn-secondary btn-sm"
                        }
                    }).then((result) => {
                        if (result.isConfirmed) {

                            $.ajax({
                                url: `{{ url('admin') }}/users/${id}/unban`,
                                method: "POST",
                                data: {
                                    _token: $('meta[name="csrf-token"]').attr('content')
                                },
                                success: function(res) {
                                    Swal.fire("Berhasil", res.success, "success");
                                    $('#datatable').DataTable().ajax.reload();
                                },
                                error: function() {
                                    Swal.fire("Error", "Gagal unban user!", "error");
                                }
                            });

                        }
                    });
                }
            @endif
        </script>
    @endpush
@endsection
