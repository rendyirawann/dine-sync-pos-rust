@extends('backend.my_profile.index')
@section('title', 'Login Session')
@section('mp')

    <div class="card mb-5 mb-lg-10 border border-gray-300">
        <!--begin::Card header-->
        <div class="card-header border-bottom border-gray-300">
            <!--begin::Heading-->
            <div class="card-title">
                <h3>Login Sessions</h3>
            </div>
            <!--end::Heading-->
        </div>
        <!--end::Card header-->
        <!--begin::Card body-->
        <div class="card-body">
            <!--begin::Table wrapper-->
            <div class="table-responsive">
                <!--begin::Table-->
                <table class="table align-middle table-row-bordered table-row-solid gy-4 gs-9 chimox" id="chimox">
                    <!--begin::Thead-->
                    <thead class="border-gray-200 fs-5 fw-semibold bg-lighten">
                        <tr>
                            <th class="min-w-100px">IP Address</th>
                            <th class="min-w-100px">Device</th>
                            <th class="min-w-100px">Operating System</th>
                            <th class="min-w-100px">Description</th>
                            <th class="min-w-100px text-end">Time</th>
                        </tr>
                    </thead>
                    <!--end::Thead-->
                </table>
                <!--end::Table-->
            </div>
            <!--end::Table wrapper-->
        </div>
        <!--end::Card body-->
    </div>

    @push('stylesheets')
        <meta name="csrf-token" content="{{ csrf_token() }}">
        <link rel="stylesheet" href="{{ URL::to('assets/plugins/custom/datatables/datatables.bundle.css') }}">
    @endpush
    @push('scripts')
        <script src="{{ URL::to('assets/plugins/custom/datatables/datatables.bundle.js') }}"></script>

        <script type="text/javascript">
            $(document).ready(function() {
                // init datatable.
                var dataTable = $('.chimox').DataTable({
                    processing: true,
                    serverSide: true,
                    paging: true,
                    info: true,
                    ajax: {
                        url: "{{ route('get-my-login-session') }}",
                        type: 'GET',
                    },
                    columns: [{
                            data: 'ip',
                            name: 'ip',
                            orderable: false,
                            searchable: false
                        },
                        {
                            data: 'device',
                            name: 'device',
                            orderable: false,
                            searchable: false
                        },
                        {
                            data: 'os',
                            name: 'os',
                            orderable: false,
                            searchable: false
                        },
                        {
                            data: 'description',
                            name: 'description',
                            orderable: false,
                            searchable: false
                        },
                        {
                            data: 'created_at',
                            name: 'created_at',
                            orderable: false,
                            searchable: false
                        },
                    ]

                });
            });
        </script>
    @endpush
@endsection
