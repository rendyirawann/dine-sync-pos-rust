<div class="d-flex flex-stack bg-light-danger rounded p-5 mb-7">
    <div>
        <div class="fs-5 text-danger fw-bold">Nomor Referensi</div>
        <div class="fs-2 text-danger fw-bolder">{{ $goodsOut->reference_no }}</div>
    </div>
    <div class="text-end">
        <div class="fs-5 text-gray-600 fw-bold">Tanggal & User</div>
        <div class="fs-4 text-gray-800 fw-bold">{{ \Carbon\Carbon::parse($goodsOut->date)->format('d-M-Y') }} |
            {{ $goodsOut->user->name ?? 'Sistem' }}</div>
    </div>
</div>

<div class="mb-5">
    <label class="fw-semibold text-muted">Catatan/Alasan:</label>
    <div class="fw-bold fs-5">{{ $goodsOut->notes ?? 'Tidak ada catatan.' }}</div>
</div>

<div class="table-responsive">
    <table class="table table-bordered table-striped align-middle gs-0 gy-4">
        <thead class="bg-white text-black">
            <tr class="fw-bold fs-7">
                <th class="ps-3">Kode Batch Asal</th>
                <th>Varian Sepatu</th>
                <th class="text-center">Qty Dikeluarkan</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($goodsOut->details as $detail)
                <tr>
                    <td class="ps-3 fw-bold text-primary">{{ $detail->batch->batch_code ?? 'Batch Terhapus' }}</td>
                    <td>
                        @if ($detail->batch && $detail->batch->variant)
                            <span class="fw-bold d-block">{{ $detail->batch->variant->product->brand }}
                                {{ $detail->batch->variant->product->model_name }}</span>
                            <span class="text-muted fs-8">Sz: {{ $detail->batch->variant->size }} |
                                {{ $detail->batch->variant->color }}</span>
                        @else
                            <span class="text-danger fst-italic">Data Varian tidak ditemukan</span>
                        @endif
                    </td>
                    <td class="text-center fw-bolder text-danger">- {{ $detail->qty }} Pcs</td>
                </tr>
            @endforeach
        </tbody>
    </table>
</div>
