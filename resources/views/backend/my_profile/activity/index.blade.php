@extends('backend.my_profile.index')
@section('title', 'My Activity')
@section('mp')


    <div class="card pt-4 border border-gray-300">
        <!--begin::Card header-->
        <div class="card-header border-bottom border-gray-300">
            <!--begin::Card title-->
            <div class="card-title">
                <h2>My Activity</h2>
            </div>
            <!--end::Card title-->

        </div>
        <!--end::Card header-->
        <!--begin::Card body-->
        <div class="card-body">
            <!--begin::Table wrapper-->
            <div class="table-responsive">
                <!--begin::Table-->
                <table class="table align-middle table-row-bordered table-row-solid gy-4 gs-9 chimox_logs" id="chimox_logs">
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
                window.activityTable = $('#chimox_logs').DataTable({

                    processing: true,
                    serverSide: true,
                    paging: true,
                    info: true,
                    ajax: {
                        url: "{{ route('get-my-activity') }}",
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
