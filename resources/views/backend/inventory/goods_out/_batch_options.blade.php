@foreach ($activeBatches as $batch)
    <option value="{{ $batch->id }}" data-max="{{ $batch->current_qty }}">
        [{{ $batch->batch_code }}] {{ $batch->variant->product->brand }} {{ $batch->variant->product->model_name }} -
        {{ $batch->variant->color }} Sz: {{ $batch->variant->size }} (Sisa Stok: {{ $batch->current_qty }})
    </option>
@endforeach
