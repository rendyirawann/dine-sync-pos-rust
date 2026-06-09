<div class="d-block">

    <div class="card mb-5 mb-xl-10">
        <div class="card-body pt-9 pb-0">
            <div class="d-flex flex-wrap flex-sm-nowrap">
                <div class="me-7 mb-4">
                    <div class="symbol symbol-100px symbol-lg-160px symbol-fixed position-relative">
                        @if ($data->avatar)
                            <img src="{{ asset('storage/user/avatar/' . $data->avatar) }}" alt="image" />
                        @else
                            <div class="symbol-label fs-1 fw-bold bg-light-danger text-danger">
                                {{ substr($data->name, 0, 1) }}
                            </div>
                        @endif
                    </div>
                </div>
                <div class="flex-grow-1">
                    <div class="d-flex justify-content-between align-items-start flex-wrap mb-2">
                        <div class="d-flex flex-column">
                            <div class="d-flex align-items-center mb-2">
                                <a href="#"
                                    class="text-gray-900 text-hover-primary fs-2 fw-bold me-1">{{ $data->name }}</a>
                            </div>
                            <div class="d-flex flex-wrap fw-semibold fs-6 mb-4 pe-2">
                                <a href="#"
                                    class="d-flex align-items-center text-gray-400 text-hover-primary mb-2 me-5">
                                    <i class="ki-outline ki-sms fs-4 me-1"></i> {{ $data->email }}
                                </a>
                                <a href="#"
                                    class="d-flex align-items-center text-gray-400 text-hover-primary mb-2">
                                    <i class="ki-outline ki-phone fs-4 me-1"></i> {{ $data->no_wa ?? '-' }}
                                </a>
                            </div>
                        </div>
                    </div>

                    <div class="d-flex flex-wrap flex-stack">
                        <div class="d-flex flex-column flex-grow-1 pe-8">
                            <div class="d-flex flex-wrap">
                                <div
                                    class="border border-gray-300 border-dashed rounded min-w-125px py-3 px-4 me-6 mb-3">
                                    <div class="d-flex align-items-center">
                                        <div class="fs-2 fw-bold counted">{{ $totalLogin }}</div>
                                        <i class="ki-outline ki-arrow-up fs-3 text-success ms-2"></i>
                                    </div>
                                    <div class="fw-semibold fs-6 text-gray-400">Total Login</div>
                                </div>
                                <div
                                    class="border border-gray-300 border-dashed rounded min-w-125px py-3 px-4 me-6 mb-3">
                                    <div class="d-flex align-items-center">
                                        <div class="fs-2 fw-bold counted">{{ $totalActivity }}</div>
                                        <i class="ki-outline ki-arrow-down fs-3 text-danger ms-2"></i>
                                    </div>
                                    <div class="fw-semibold fs-6 text-gray-400">Activity Logs</div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="separator my-5"></div>
            <div class="d-flex flex-stack fs-4 py-3">
                <div class="fw-bold rotate collapsible" data-bs-toggle="collapse" href="#kt_user_view_details_modal"
                    role="button" aria-expanded="false">
                    Detail Informasi
                    <span class="ms-2 rotate-180"><i class="ki-outline ki-down fs-3"></i></span>
                </div>
            </div>
            <div id="kt_user_view_details_modal" class="collapse show">
                <div class="pb-5 fs-6 row">
                    <div class="col-md-6">
                        <div class="fw-bold mt-5">Account ID</div>
                        <div class="text-gray-600">{{ $data->id }}</div>
                        <div class="fw-bold mt-5">Email</div>
                        <div class="text-gray-600">{{ $data->email }}</div>
                    </div>
                    <div class="col-md-6">
                        <div class="fw-bold mt-5">Last Login</div>
                        <div class="text-gray-600">
                            {{ $data->last_login ? \Carbon\Carbon::parse($data->last_login)->diffForHumans() : 'Belum pernah login' }}
                        </div>
                        <div class="fw-bold mt-5">Last IP Address</div>
                        <div class="text-gray-600">{{ $data->last_ip ?? '-' }}</div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="card mb-5 mb-xl-10">
        <div class="card-header border-0 pt-5">
            <h3 class="card-title align-items-start flex-column">
                <span class="card-label fw-bold fs-3 mb-1">Logs & Roles</span>
                <span class="text-muted mt-1 fw-semibold fs-7">Riwayat aktivitas pengguna</span>
            </h3>
            <div class="card-toolbar">
                <ul class="nav nav-custom nav-tabs nav-line-tabs nav-line-tabs-2x border-0 fs-4 fw-semibold">
                    <li class="nav-item">
                        <a class="nav-link text-active-primary pb-4 active" data-bs-toggle="tab"
                            href="#kt_tab_overview">Roles</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link text-active-primary pb-4" data-bs-toggle="tab"
                            href="#kt_tab_login_logs">Login Logs</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link text-active-primary pb-4" data-bs-toggle="tab"
                            href="#kt_tab_activity_logs">Activity Logs</a>
                    </li>
                </ul>
            </div>
        </div>
        <div class="card-body py-3">
            <div class="tab-content">

                <div class="tab-pane fade show active" id="kt_tab_overview" role="tabpanel">
                    <div class="d-flex flex-column">
                        <div class="fs-6 fw-bold text-dark mb-3">Assigned Roles ({{ $data->roles->count() }})</div>
                        @forelse ($data->getRoleNames() as $role)
                            <div class="d-flex align-items-center position-relative mb-4">
                                <div class="position-absolute top-0 start-0 rounded h-100 bg-primary w-4px"></div>
                                <div class="fw-bold ms-5">
                                    <a href="#"
                                        class="fs-5 fw-bold text-dark text-hover-primary">{{ $role }}</a>
                                    <div class="fs-7 text-muted">Akses level aplikasi</div>
                                </div>
                            </div>
                        @empty
                            <span class="text-muted">User ini belum memiliki role.</span>
                        @endforelse
                    </div>
                </div>

                <div class="tab-pane fade" id="kt_tab_login_logs" role="tabpanel">
                    <div class="table-responsive">
                        <table class="table align-middle table-row-dashed gy-5" id="kt_table_users_login_session">
                            <thead class="border-bottom border-gray-200 fs-7 fw-bold">
                                <tr class="text-start text-muted text-uppercase gs-0">
                                    <th class="min-w-100px">IP Address</th>
                                    <th>Device</th>
                                    <th>OS - Browser</th>
                                    <th>Message</th>
                                    <th class="min-w-125px">Time</th>
                                </tr>
                            </thead>
                            <tbody class="fs-6 fw-semibold text-gray-600"></tbody>
                        </table>
                    </div>
                </div>

                <div class="tab-pane fade" id="kt_tab_activity_logs" role="tabpanel">
                    <div class="table-responsive">
                        <table class="table align-middle table-row-dashed gy-5" id="kt_table_users_activity">
                            <thead class="border-bottom border-gray-200 fs-7 fw-bold">
                                <tr class="text-start text-muted text-uppercase gs-0">
                                    <th class="min-w-100px">IP Address</th>
                                    <th>Device</th>
                                    <th>OS - Browser</th>
                                    <th>Message</th>
                                    <th class="min-w-125px">Time</th>
                                </tr>
                            </thead>
                            <tbody class="fs-6 fw-semibold text-gray-600"></tbody>
                        </table>
                    </div>
                </div>

            </div>
        </div>
    </div>
</div>

<script>
    $(document).ready(function() {
        // Destroy existing datatables jika ada (cegah duplikasi)
        if ($.fn.DataTable.isDataTable('#kt_table_users_login_session')) {
            $('#kt_table_users_login_session').DataTable().destroy();
        }
        if ($.fn.DataTable.isDataTable('#kt_table_users_activity')) {
            $('#kt_table_users_activity').DataTable().destroy();
        }

        // Init Table Login
        var tableLogin = $('#kt_table_users_login_session').DataTable({
            processing: true,
            serverSide: true,
            paging: true,
            info: true,
            searching: false, // Matikan search box kecil di dalam tab agar rapi
            lengthChange: false, // Matikan dropdown jumlah baris agar rapi
            pageLength: 5, // Tampilkan 5 baris saja per halaman biar modal gak kepanjangan
            ajax: {
                url: "{{ route('get-user-show-log', ['id' => $data->id]) }}",
                type: 'GET',
            },
            columns: [{
                    data: 'ip',
                    name: 'ip',
                    orderable: false
                },
                {
                    data: 'device',
                    name: 'device',
                    orderable: false
                },
                {
                    data: 'os',
                    name: 'os',
                    orderable: false
                },
                {
                    data: 'description',
                    name: 'description',
                    orderable: false
                },
                {
                    data: 'created_at',
                    name: 'created_at',
                    orderable: false
                },
            ]
        });

        // Init Table Activity
        var tableActivity = $('#kt_table_users_activity').DataTable({
            processing: true,
            serverSide: true,
            paging: true,
            info: true,
            searching: false,
            lengthChange: false,
            pageLength: 5,
            ajax: {
                url: "{{ route('get-user-show-log-activity', ['id' => $data->id]) }}",
                type: 'GET',
            },
            columns: [{
                    data: 'ip',
                    name: 'ip',
                    orderable: false
                },
                {
                    data: 'device',
                    name: 'device',
                    orderable: false
                },
                {
                    data: 'os',
                    name: 'os',
                    orderable: false
                },
                {
                    data: 'description',
                    name: 'description',
                    orderable: false
                },
                {
                    data: 'created_at',
                    name: 'created_at',
                    orderable: false
                },
            ]
        });

        // Fix Tab Bootstrap: Agar header tabel menyesuaikan lebar saat tab diklik
        $('a[data-bs-toggle="tab"]').on('shown.bs.tab', function(e) {
            $($.fn.dataTable.tables(true)).DataTable().columns.adjust();
        });
    });
</script>
