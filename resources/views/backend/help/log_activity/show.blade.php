@extends('backend.layout.app')
@section('title', 'Log Activity Detail')
@section('content')
    <!--begin::Toolbar-->
    <div id="kt_app_toolbar" class="app-toolbar d-flex flex-stack py-4 py-lg-8 mb-5">
        <!--begin::Toolbar wrapper-->
        <div class="d-flex flex-grow-1 flex-stack flex-wrap gap-2 mb-n10" id="kt_toolbar">
            <!--begin::Page title-->
            <div class="page-title d-flex flex-column justify-content-center flex-wrap me-3">
                <!--begin::Title-->
                <h1 class="page-heading d-flex text-gray-900 fw-bold fs-3 flex-column justify-content-center my-0">Log
                    Activity
                    Detail</h1>
                <!--end::Title-->
                <!--begin::Breadcrumb-->
                <ul class="breadcrumb breadcrumb-separatorless fw-semibold fs-7 my-0 pt-1">
                    <!--begin::Item-->
                    <li class="breadcrumb-item text-muted">
                        <a class="text-muted text-hover-primary">Home</a>
                    </li>
                    <!--end::Item-->
                    <!--begin::Item-->
                    <li class="breadcrumb-item">
                        <span class="bullet bg-gray-500 w-5px h-2px"></span>
                    </li>
                    <!--end::Item-->
                    <!--begin::Item-->
                    <li class="breadcrumb-item text-muted">Help</li>
                    <!--end::Item-->
                    <!--begin::Item-->
                    <li class="breadcrumb-item">
                        <span class="bullet bg-gray-500 w-5px h-2px"></span>
                    </li>
                    <!--end::Item-->
                    <!--begin::Item-->
                    <li class="breadcrumb-item text-muted">Log Activity</li>
                    <!--end::Item-->
                    <!--begin::Item-->
                    <li class="breadcrumb-item">
                        <span class="bullet bg-gray-500 w-5px h-2px"></span>
                    </li>
                    <!--end::Item-->
                    <!--begin::Item-->
                    <li class="breadcrumb-item text-gray-900">{{ $data->log_name }}</li>
                    <!--end::Item-->
                </ul>
                <!--end::Breadcrumb-->
            </div>
            <!--end::Page title-->
        </div>
        <!--end::Toolbar wrapper-->
    </div>
    <!--end::Toolbar-->
    <!--begin::Content-->
    <div class="d-flex flex-column flex-column-fluid">
        <!--begin::Content-->
        <div id="kt_app_content" class="app-content">
            <!--begin::Pricing card-->
            <div class="card border border-gray-300" id="kt_pricing">
                <!--begin::Card body-->
                <div class="card-body p-lg-17">
                    <!--begin::Plans-->
                    <div class="d-flex flex-column">
                        <!--begin::Heading-->
                        <div class="mb-13 text-center">
                            <h1 class="fs-2 fw-bold mb-5">{{ $data->causer->name }} {{ $data->description }}</h1>
                            <div class="text-gray-600 fw-semibold fs-5">
                                <label class="me-4">IP Address - {{ $data->properties['ip'] }}</label>
                                @php
                                    $agent = $data->properties['agent'] ?? [];

                                    $isDesktop = $agent['is_desktop'] ?? false;
                                    $isMobile = $agent['is_mobile'] ?? false;
                                    $deviceRaw = $agent['device'] ?? 'Unknown';
                                @endphp
                                <label>
                                    @if ($isDesktop)
                                        <i class="ki-outline ki-screen text-primary me-2"></i><label
                                            class="me-4">Desktop</label>
                                    @elseif ($isMobile)
                                        <i class="ki-outline ki-tablet text-success me-2"></i><label
                                            class="me-4">Mobile</label>
                                    @else
                                        <i class="ki-outline ki-question-2 text-danger me-2"></i>Unknown
                                    @endif
                                </label>
                                <label
                                    class="me-4">{{ $data->properties['agent']['os'] . ' - ' . $data->properties['agent']['browser'] }}</label>
                            </div>
                        </div>
                        <!--end::Heading-->
                            <!--begin::Row-->
                            <div class="row g-10">
                                <!--begin::Col-->
                                @if (isset($data->properties['attributes']) || isset($data->properties['old']))

                                    @if (isset($data->properties['old']))
                                        <div class="col-xl-6">
                                    @else
                                        <div class="col-xl-12">
                                    @endif
                                <div class="d-flex h-100 align-items-center ">
                                    <!--begin::Option-->
                                    <div
                                        class="w-100 d-flex flex-column flex-center rounded-3 bg-light bg-opacity-75 py-15 px-10 border border-gray-300">
                                        @if (isset($data->properties['old']))
                                            <h1 class="fs-2 fw-bold mb-5">Data Baru</h1>
                                        @else
                                        @endif
                                        <div class="w-100 mb-10">
                                            <pre>{{ json_encode($data->properties['new'], JSON_PRETTY_PRINT) }}</pre>
                                        </div>
                                       
                                    </div>
                                    <!--end::Option-->
                                </div>
                                 
                            </div>
                            <!--end::Col-->
                            @if (isset($data->properties['old']))
                                <!--begin::Col-->
                                <div class="col-xl-6 ">
                                    <div class="d-flex h-100 align-items-center">
                                        <!--begin::Option-->
                                        <div
                                            class="w-100 d-flex flex-column flex-center rounded-3 bg-light bg-opacity-75 py-15 px-10 border border-gray-300">
                                            <h1 class="fs-2 fw-bold mb-5">Data Sebelumnya</h1>
                                            <div class="w-100 mb-10 border-gray-300">
                                                @php
                                                    $oldData = $data->properties['old'];
                                                    if (isset($oldData['password'])) {
                                                        unset($oldData['password']);
                                                    }
                                                @endphp
                                                <pre>{{ json_encode($oldData, JSON_PRETTY_PRINT) }}</pre>
                                            </div>
                                        </div>
                                        <!--end::Option-->
                                    </div>
                                </div>
                                <!--end::Col-->

                                <div class="d-flex h-100 align-items-center">
                                    <!--begin::Option-->
                                    <div
                                        class="w-100 d-flex flex-column flex-center rounded-3 bg-light bg-opacity-75 py-15 px-10 border border-gray-300">
                                        <div class="w-100 mb-10">
                                             <pre>{{ json_encode($data->properties['agent'], JSON_PRETTY_PRINT) }}</pre>
                                            <pre>{{ json_encode($data->properties['request'], JSON_PRETTY_PRINT) }}</pre>
                                        </div>
                                    </div>
                                    <!--end::Option-->
                            </div>
                                
                                  
                            @else
                            @endif
                        @else
                            <!-- Display 'agent' if 'attributes' and 'old' are not present -->
                            <div class="col-xl-12 border-gray-300">
                                <div class="d-flex h-100 align-items-center">
                                    <!--begin::Option-->
                                    <div
                                        class="w-100 d-flex flex-column flex-center rounded-3 bg-light bg-opacity-75 py-15 px-10 border border-gray-300">
                                        <div class="w-100 mb-10">
                                            <pre>{{ json_encode($data->properties, JSON_PRETTY_PRINT) }}</pre>
                                        </div>
                                    </div>
                                    <!--end::Option-->
                                </div>
                            </div>
                        @endif
                    </div>
                    <!--end::Row-->
                </div>
                <!--end::Plans-->
            </div>
            <!--end::Card body-->
        </div>
        <!--end::Pricing card-->
    </div>
    <!--end::Content-->
    </div>

    @push('stylesheets')
        <meta name="csrf-token" content="{{ csrf_token() }}">
    @endpush
    @push('scripts')
    @endpush
@endsection
