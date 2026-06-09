<div class="d-flex flex-stack bg-light rounded p-5 mb-7">
    <div>
        <div class="fs-5 text-gray-500 fw-bold">Nomor Referensi</div>
        <div class="fs-2 text-gray-800 fw-bolder">{{ $purchase->reference_no }}</div>
    </div>
    <div class="text-end">
        <div class="fs-5 text-gray-500 fw-bold">Supplier</div>
        <div class="fs-3 text-primary fw-bolder">{{ $purchase->supplier->name ?? 'Tanpa Supplier' }}</div>
    </div>
</div>

<div class="table-responsive">
    <table class="table table-bordered table-striped align-middle gs-0 gy-4">
        <thead class="bg-light-dark">
            <tr class="fw-bold text-gray-800">
                <th class="ps-3">Kode Batch</th>
                <th>Produk & Varian</th>
                <th class="text-center">Qty Awal</th>
                <th class="text-center text-success">Sisa Gudang</th>
                <th class="text-end pe-3">Harga Modal</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($purchase->batches as $batch)
                <tr>
                    <td class="ps-3 fw-bold text-primary">{{ $batch->batch_code }}</td>
                    <td>
                        <span class="fw-bold d-block">{{ $batch->variant->product->brand }}
                            {{ $batch->variant->product->model_name }}</span>
                        <span class="text-muted fs-8">Sz: {{ $batch->variant->size }} |
                            {{ $batch->variant->color }}</span>
                    </td>
                    <td class="text-center">{{ $batch->initial_qty }}</td>
                    <td class="text-center fw-bolder {{ $batch->current_qty > 0 ? 'text-success' : 'text-danger' }}">
                        {{ $batch->current_qty == 0 ? 'Habis (0)' : $batch->current_qty }}
                    </td>
                    <td class="text-end pe-3">Rp {{ number_format($batch->buy_price, 0, ',', '.') }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>
</div>
