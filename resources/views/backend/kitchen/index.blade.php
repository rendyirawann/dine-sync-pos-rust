@extends('backend.layout.app')
@section('title', 'Kitchen Display System')
@section('content')

    <div id="kt_app_toolbar" class="app-toolbar py-3 py-lg-6">
        <div id="kt_app_toolbar_container" class="app-container container-xxl d-flex flex-stack flex-wrap gap-3">
            <div class="page-title d-flex flex-column justify-content-center flex-wrap me-3">
                <h1 class="page-heading d-flex text-dark fw-bold fs-3 flex-column justify-content-center my-0">
                    <i class="ki-outline ki-fire fs-2 me-2 text-danger"></i> Dapur (Antrian Pesanan)
                </h1>
            </div>

            <ul class="nav nav-pills nav-pills-custom border-transparent flex-row gap-2">
                <li class="nav-item">
                    <a class="nav-link btn btn-sm btn-color-muted btn-active-light-danger fw-bold active"
                        data-bs-toggle="tab" href="#tab_aktif">
                        Sedang Dibuat <span class="badge badge-danger ms-2">{{ $activeOrders->count() }}</span>
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link btn btn-sm btn-color-muted btn-active-light-success fw-bold" data-bs-toggle="tab"
                        href="#tab_selesai">
                        Sudah Selesai <span class="badge badge-success ms-2">{{ $completedOrders->count() }}</span>
                    </a>
                </li>
            </ul>

            <div class="d-flex align-items-center gap-2 gap-lg-3">
                <button type="button" class="btn btn-sm btn-light-primary fw-bold" onclick="location.reload()">
                    <i class="ki-outline ki-arrows-circle fs-3"></i> Refresh Manual
                </button>
            </div>
        </div>
    </div>

    <div id="kt_app_content" class="app-content flex-column-fluid">
        <div id="kt_app_content_container" class="app-container container-xxl">

            <div class="tab-content">

                <div class="tab-pane fade show active" id="tab_aktif" role="tabpanel">
                    <div class="row g-5">
                        @forelse ($activeOrders as $order)
                            <div class="col-md-4 col-lg-3">
                                <div
                                    class="card shadow-sm border-0 h-100 {{ $order->order_status == 'cooking' ? 'border border-primary border-2' : '' }}">
                                    <div
                                        class="card-header min-h-50px px-4 {{ $order->order_status == 'cooking' ? 'bg-light-primary' : 'bg-light-warning' }}">
                                        <div class="card-title d-flex flex-column align-items-start m-0 py-2">
                                            <span
                                                class="fw-bold fs-4 text-gray-800">{{ $order->table->table_number ?? 'Walk-in' }}</span>
                                            <span class="fs-8 text-muted fw-semibold">{{ $order->customer_name }} •
                                                #{{ $order->invoice_no }}</span>
                                        </div>
                                        <div class="card-toolbar m-0">
                                            <span
                                                class="badge {{ $order->order_status == 'cooking' ? 'badge-primary' : 'badge-warning' }} fs-8">
                                                {{ \Carbon\Carbon::parse($order->created_at)->diffForHumans(null, true, true) }}
                                                lalu
                                            </span>
                                        </div>
                                    </div>
                                    <div class="card-body p-4">
                                        <div class="d-flex flex-column gap-3">
                                            @foreach ($order->details as $item)
                                                <div
                                                    class="d-flex align-items-start justify-content-between border-bottom pb-2 mb-1">
                                                    <div class="d-flex flex-column">
                                                        <span
                                                            class="fw-bold text-gray-800 fs-6 {{ $item->status == 'done' ? 'text-decoration-line-through text-muted' : '' }}">
                                                            {{ $item->qty }}x {{ $item->menu->name ?? 'Menu Dihapus' }}
                                                            <button type="button" class="btn btn-sm btn-icon btn-light-info w-20px h-20px ms-1" onclick="showRecipeModal({{ $item->id }}, 'info')" title="Lihat Detail Bahan">
                                                                <i class="ki-outline ki-information fs-7"></i>
                                                            </button>
                                                        </span>
                                                        @if ($item->notes)
                                                            <span class="fs-8 fw-bold text-danger fst-italic"><i
                                                                    class="ki-outline ki-message-text-2 fs-8"></i>
                                                                {{ $item->notes }}</span>
                                                        @endif
                                                    </div>
                                                    <div class="ms-2 min-w-80px text-end">
                                                        @if ($item->status == 'pending')
                                                            <button
                                                                class="btn btn-sm btn-icon btn-light-warning h-30px w-30px"
                                                                onclick="showRecipeModal({{ $item->id }}, 'cooking')"
                                                                title="Mulai Masak">
                                                                <i class="ki-outline ki-fire fs-4"></i>
                                                            </button>
                                                        @elseif ($item->status == 'cooking')
                                                            <button
                                                                class="btn btn-sm btn-icon btn-light-primary h-30px w-30px"
                                                                onclick="updateStatus({{ $item->id }}, 'done')"
                                                                title="Selesai & Sajikan">
                                                                <i class="ki-outline ki-check fs-4"></i>
                                                            </button>
                                                        @else
                                                            <span class="badge badge-light-success fs-8"><i
                                                                    class="ki-outline ki-check-circle text-success fs-5"></i></span>
                                                        @endif
                                                    </div>
                                                </div>
                                            @endforeach
                                        </div>
                                    </div>

                                    <div class="card-footer p-3 d-flex justify-content-between bg-transparent border-top">
                                        @php
                                            $hasPending = $order->details->where('status', 'pending')->count() > 0;
                                        @endphp

                                        @if ($hasPending)
                                            <button class="btn btn-sm btn-light-warning flex-fill fw-bold"
                                                onclick="updateOrderStatus({{ $order->id }}, 'cooking')">
                                                <i class="ki-outline ki-fire fs-5"></i> Masak Semua (FEFO)
                                            </button>
                                        @else
                                            <button class="btn btn-sm btn-light-primary flex-fill fw-bold"
                                                onclick="updateOrderStatus({{ $order->id }}, 'done')">
                                                <i class="ki-outline ki-check fs-5"></i> Selesai Semua
                                            </button>
                                        @endif
                                    </div>
                                </div>
                            </div>
                        @empty
                            <div class="col-12 text-center py-10">
                                <i class="ki-outline ki-coffee fs-5x text-muted mb-3"></i>
                                <h3 class="text-gray-500 fw-semibold">Dapur sedang santai. Belum ada antrian pesanan.</h3>
                            </div>
                        @endforelse
                    </div>
                </div>

                <div class="tab-pane fade" id="tab_selesai" role="tabpanel">
                    <div class="row g-5">
                        @forelse ($completedOrders as $order)
                            <div class="col-md-4 col-lg-3">
                                <div class="card shadow-sm border-0 h-100 border border-success">
                                    <div class="card-header min-h-50px px-4 bg-light-success">
                                        <div class="card-title d-flex flex-column align-items-start m-0 py-2">
                                            <span
                                                class="fw-bold fs-4 text-gray-800">{{ $order->table->table_number ?? 'Walk-in' }}</span>
                                            <span class="fs-8 text-muted fw-semibold">{{ $order->customer_name }} •
                                                #{{ $order->invoice_no }}</span>
                                        </div>
                                        <div class="card-toolbar m-0">
                                            <span class="badge badge-success fs-8"><i
                                                    class="ki-outline ki-check text-white fs-7 me-1"></i> Selesai</span>
                                        </div>
                                    </div>

                                    <div class="card-body p-4">
                                        <div class="d-flex flex-column gap-3">
                                            @foreach ($order->details as $item)
                                                <div
                                                    class="d-flex align-items-start justify-content-between border-bottom pb-2 mb-1">
                                                    <div class="d-flex flex-column">
                                                        <span
                                                            class="fw-bold text-gray-500 fs-6 text-decoration-line-through">
                                                            {{ $item->qty }}x {{ $item->menu->name ?? 'Menu Dihapus' }}
                                                            <button type="button" class="btn btn-sm btn-icon btn-light-info w-20px h-20px ms-1" onclick="showRecipeModal({{ $item->id }}, 'info')" title="Lihat Detail Bahan">
                                                                <i class="ki-outline ki-information fs-7"></i>
                                                            </button>
                                                        </span>
                                                    </div>
                                                    <div class="ms-2 text-end">
                                                        <span class="badge badge-light-success fs-8"><i
                                                                class="ki-outline ki-check-circle text-success fs-5"></i></span>
                                                    </div>
                                                </div>
                                            @endforeach
                                        </div>
                                    </div>

                                    <div class="card-footer p-3 bg-transparent border-top">
                                        <button class="btn btn-sm btn-light-info w-100 fw-bold btn-recall-food"
                                            data-id="{{ $order->id }}">
                                            <i class="ki-outline ki-notification-on fs-5"></i> Panggil Ulang ke TV
                                        </button>
                                    </div>

                                </div>
                            </div>
                        @empty
                            <div class="col-12 text-center py-10">
                                <i class="ki-outline ki-burger fs-5x text-muted mb-3"></i>
                                <h3 class="text-gray-500 fw-semibold">Belum ada pesanan yang disiapkan hari ini.</h3>
                            </div>
                        @endforelse
                    </div>
                </div>

            </div>

        </div>
    </div>

    <div class="modal fade" id="modal_recipe" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered mw-800px">
            <div class="modal-content">
                <div class="modal-header">
                    <h2 class="fw-bold">Konfirmasi Bahan & Batch</h2>
                    <div class="btn btn-icon btn-sm btn-active-icon-primary" data-bs-dismiss="modal">
                        <i class="ki-outline ki-cross fs-1"></i>
                    </div>
                </div>
                <div class="modal-body mx-5 mx-xl-15 my-7">
                    <div class="mb-5 text-center">
                        <h3 id="modal_menu_title" class="text-gray-800 fw-bold">Menu Name</h3>
                        <span id="modal_menu_qty" class="badge badge-light-primary fs-7">1x</span>
                    </div>
                    
                    <form id="form_recipe_selection">
                        <input type="hidden" id="modal_detail_id" name="detail_id">
                        <input type="hidden" id="modal_target_status" name="status">
                        
                        <div id="recipe_list_container" class="mh-300px scroll-y me-n7 pe-7">
                            <!-- Recipe rows will be injected here -->
                        </div>

                        <div class="text-center pt-10 modal-footer-btn">
                            <button type="button" class="btn btn-light me-3" data-bs-dismiss="modal">Batal</button>
                            <button type="submit" class="btn btn-primary">Mulai Masak & Potong Stok</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    @push('scripts')
        <script>
            // ... (previous ready script)
            $(document).ready(function() {
                let activeTab = localStorage.getItem('kds_active_tab');
                if (activeTab) {
                    $('.nav-link[href="' + activeTab + '"]').tab('show');
                }

                $('a[data-bs-toggle="tab"]').on('shown.bs.tab', function(e) {
                    localStorage.setItem('kds_active_tab', $(e.target).attr('href'));
                });

                toastr.options = { "closeButton": true, "progressBar": true, "positionClass": "toastr-top-right", "timeOut": "3000" };
            });

            function showRecipeModal(detailId, newStatus) {
                if (newStatus !== 'cooking' && newStatus !== 'info') {
                    updateStatus(detailId, newStatus);
                    return;
                }

                $('#recipe_list_container').html('<div class="text-center py-5"><span class="spinner-border text-primary"></span></div>');
                $('#modal_recipe').modal('show');
                $('#modal_detail_id').val(detailId);
                $('#modal_target_status').val(newStatus === 'info' ? '' : newStatus);

                $.get("{{ url('admin/kitchen/recipe-details') }}/" + detailId, function(res) {
                    $('#modal_menu_title').text(res.menu_name);
                    $('#modal_menu_qty').text(res.qty + 'x');
                    
                    let html = '';
                    if (res.is_stock_deducted) {
                        html += `<div class="alert alert-light-success d-flex align-items-center p-3 mb-5">
                            <i class="ki-outline ki-check-circle fs-2 me-3 text-success"></i>
                            <span class="fw-bold">Stok untuk menu ini sudah dipotong.</span>
                        </div>`;
                    }

                    res.recipes.forEach(function(r) {
                        let isDisabled = res.is_stock_deducted ? 'disabled' : '';
                        html += `<div class="fv-row mb-7">
                            <label class="fs-6 fw-semibold mb-2">${r.name} (Butuh: ${r.needed} ${r.unit})</label>
                            <select name="selections[${r.ingredient_id}]" class="form-select" data-control="select2" data-hide-search="true" ${isDisabled}>`;
                        
                        if (r.batches.length === 0) {
                            html += `<option value="" disabled selected>STOK HABIS!</option>`;
                        } else {
                            r.batches.forEach(function(b) {
                                let selected = b.id === r.suggested_batch ? 'selected' : '';
                                html += `<option value="${b.id}" ${selected}>${b.label}</option>`;
                            });
                        }
                        
                        html += `</select>
                        </div>`;
                    });
                    $('#recipe_list_container').html(html);
                    $('#recipe_list_container select').select2();

                    // Update button visibility
                    if (res.is_stock_deducted) {
                        $('#modal_recipe .modal-footer-btn').html('<button type="button" class="btn btn-light w-100" data-bs-dismiss="modal">Tutup</button>');
                    } else {
                        $('#modal_recipe .modal-footer-btn').html(`
                            <button type="button" class="btn btn-light me-3" data-bs-dismiss="modal">Batal</button>
                            <button type="submit" class="btn btn-primary">Mulai Masak & Potong Stok</button>
                        `);
                    }
                });
            }

            $('#form_recipe_selection').on('submit', function(e) {
                e.preventDefault();
                let detailId = $('#modal_detail_id').val();
                let status = $('#modal_target_status').val();
                let formData = $(this).serialize();
                
                $('#modal_recipe').modal('hide');
                updateStatus(detailId, status, formData);
            });

            function updateStatus(detailId, newStatus, extraData = '') {
                let swalLoader = Swal.fire({
                    toast: true, position: 'top-end', showConfirmButton: false, title: 'Memproses...', icon: 'info'
                });

                let data = extraData ? extraData : { _token: '{{ csrf_token() }}', detail_id: detailId, status: newStatus };

                $.ajax({
                    url: "{{ route('kitchen.update-item') }}",
                    method: "POST",
                    data: data,
                    headers: extraData ? { 'X-CSRF-TOKEN': '{{ csrf_token() }}' } : {},
                    success: function(res) {
                        if (res.success) {
                            if (res.is_finished) {
                                swalLoader.close();
                                toastr.success(`Semua pesanan untuk ${res.table_name} telah selesai!`, "Pesanan Siap! 🎉");
                                setTimeout(() => { location.reload(); }, 2000);
                            } else {
                                location.reload();
                            }
                        }
                    },
                    error: function(xhr) {
                        Swal.fire('Error 500!', xhr.responseJSON.error || 'Terjadi kesalahan sistem.', 'error');
                    }
                });
            }

            function updateOrderStatus(orderId, newStatus) {
                Swal.fire({
                    title: 'Yakin?',
                    text: `Anda akan memproses pesanan di invoice ini. (Stok dipotong otomatis FEFO)`,
                    icon: 'question',
                    showCancelButton: true,
                    confirmButtonText: 'Ya, Lanjut',
                    cancelButtonText: 'Batal',
                }).then((result) => {
                    if (result.isConfirmed) {
                        $.ajax({
                            url: "{{ route('kitchen.update-order') }}",
                            method: "POST",
                            data: { _token: '{{ csrf_token() }}', order_id: orderId, status: newStatus },
                            success: function(res) {
                                if (res.success) {
                                    location.reload();
                                }
                            }
                        });
                    }
                });
            }

            $('.btn-recall-food').click(function() {
                let btn = $(this);
                let orderId = btn.data('id');
                let originalHtml = btn.html();
                btn.prop('disabled', true).html('<span class="spinner-border spinner-border-sm me-2"></span> Memanggil...');
                $.ajax({
                    url: "{{ route('kitchen.recall') }}",
                    method: "POST",
                    data: { _token: '{{ csrf_token() }}', order_id: orderId },
                    success: function(res) {
                        toastr.success(res.message, "Audio Diputar! 🔊");
                        let timeLeft = 15;
                        let timerInterval = setInterval(() => {
                            timeLeft--;
                            btn.html(`<span class="text-danger fw-bolder">Tunggu ${timeLeft}s</span>`);
                            if (timeLeft <= 0) { clearInterval(timerInterval); btn.prop('disabled', false).html(originalHtml); }
                        }, 1000);
                    }
                });
            });

            setInterval(function() {
                if (!$('#modal_recipe').hasClass('show')) {
                    location.reload();
                }
            }, 30000);
        </script>
    @endpush
@endsection
