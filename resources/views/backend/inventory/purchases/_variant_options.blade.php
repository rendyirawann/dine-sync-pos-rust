@foreach ($variants as $var)
    <option value="{{ $var->id }}" data-price="{{ $var->price_buy }}">
        {{ $var->product->brand }} {{ $var->product->model_name }} | {{ $var->color }} | Sz: {{ $var->size }}
    </option>
@endforeach
