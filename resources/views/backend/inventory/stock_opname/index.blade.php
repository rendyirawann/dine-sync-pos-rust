@extends('backend.layout.app')
@section('title', 'Stock Opname (Audit Fisik)')
@section('content')

    <div id="kt_app_content" class="app-content flex-column-fluid mt-5">
        <div id="kt_app_content_container" class="app-container container-xxl">
            <div class="card card-flush">
                <div class="card-header align-items-center py-5 gap-2 gap-md-5">
                    <div class="card-title">
                        <h3 class="fw-bold fs-2 text-gray-800">Riwayat Stock Opname</h3>
                    </div>
                    <div class="card-toolbar gap-3">
                        <a href="{{ route('stock-opname.print-worksheet') }}" target="_blank"
                            class="btn btn-sm btn-light-info"><i class="ki-outline ki-printer fs-2"></i> Cetak Kertas
                            Kerja</a>
                        <a href="{{ route('stock-opname.create') }}" class="btn btn-sm btn-primary"><i
                                class="ki-outline ki-plus-square fs-2"></i> Input Opname Baru</a>
                    </div>
                </div>
                <div class="card-body pt-0">
                    <table class="table align-middle table-row-dashed fs-6 gy-5" id="opname_table">
                        <thead>
                            <tr class="text-start text-gray-400 fw-bold fs-7 text-uppercase gs-0">
                                <th>Tanggal</th>
                                <th>No. Referensi</th>
                                <th>Pelaksana (Admin)</th>
                                <th>Catatan</th>
                                <th class="text-end">Aksi</th>
                            </tr>
                        </thead>
                        <tbody class="fw-semibold text-gray-600">
                            @foreach ($opnames as $op)
                                <tr>
                                    <td>{{ \Carbon\Carbon::parse($op->date)->format('d M Y') }}</td>
                                    <td class="fw-bold text-primary">{{ $op->reference_no }}</td>
                                    <td>{{ $op->user->name }}</td>
                                    <td>{{ $op->notes ?? '-' }}</td>
                                    <td class="text-end">
                                        <button class="btn btn-sm btn-light-primary btn-detail"
                                            data-id="{{ $op->id }}">Lihat Hasil Selisih</button>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <div class="modal fade" id="Modal_Detail" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered mw-800px">
            <div class="modal-content" id="DetailBody"></div>
        </div>
    </div>

    @push('stylesheets')
        <link rel="stylesheet" href="{{ URL::to('assets/plugins/custom/datatables/datatables.bundle.css') }}" />
    @endpush
    @push('scripts')
        <script src="{{ URL::to('assets/plugins/custom/datatables/datatables.bundle.js') }}"></script>
        <script>
            $('#opname_table').DataTable();
            $('.btn-detail').click(function() {
                let id = $(this).data('id');
                $('#DetailBody').html(
                    '<div class="p-10 text-center"><span class="spinner-border text-primary"></span></div>');
                $('#Modal_Detail').modal('show');
                $.get("{{ url('admin') }}/stock-opname/" + id, function(res) {
                    $('#DetailBody').html(res.html);
                });
            });
        </script>
    @endpush
@endsection
