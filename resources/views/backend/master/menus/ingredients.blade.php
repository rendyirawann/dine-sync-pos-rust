<div class="row">
    <div class="col-12">
        <div class="d-flex flex-column mb-5">
            <h3 class="fw-bold text-gray-800">{{ $menu->name }}</h3>
            <span class="text-muted fs-7">Atur resep dan gramasi bahan makanan untuk menu ini.</span>
        </div>

        <form id="FormRecipeID">
            @csrf
            <div id="recipe_list">
                @forelse ($menu->ingredients as $index => $item)
                    <div class="row mb-3 recipe-row">
                        <div class="col-md-7">
                            <select name="ingredients[{{ $index }}][ingredient_id]" class="form-select form-select-sm" data-control="select2" data-placeholder="Pilih Bahan...">
                                <option></option>
                                @foreach ($allIngredients as $ing)
                                    <option value="{{ $ing->id }}" {{ $item->ingredient_id == $ing->id ? 'selected' : '' }}>
                                        {{ $ing->name }} ({{ $ing->unit }})
                                    </option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-3">
                            <input type="number" step="0.01" name="ingredients[{{ $index }}][quantity]" class="form-control form-control-sm" placeholder="Gramasi" value="{{ $item->quantity }}" required>
                        </div>
                        <div class="col-md-2">
                            <button type="button" class="btn btn-sm btn-icon btn-light-danger remove-recipe"><i class="ki-outline ki-trash fs-5"></i></button>
                        </div>
                    </div>
                @empty
                    <div class="text-center py-5 text-muted empty-state">
                        Belum ada bahan yang ditambahkan.
                    </div>
                @endforelse
            </div>

            <div class="mt-5 d-flex justify-content-between align-items-center">
                <button type="button" class="btn btn-sm btn-light-primary" id="add_ingredient">
                    <i class="ki-outline ki-plus fs-4"></i> Tambah Bahan
                </button>
                <div class="text-end">
                    <button type="button" class="btn btn-sm btn-light me-2" data-bs-dismiss="modal">Tutup</button>
                    <button type="submit" class="btn btn-sm btn-primary" id="btn_save_recipe">Simpan Resep</button>
                </div>
            </div>
        </form>
    </div>
</div>

<script>
    $(document).ready(function() {
        // Re-init select2 for modal
        $('#FormRecipeID select').select2({
            dropdownParent: $('#Modal_Ingredients_Data')
        });

        var recipeIndex = {{ $menu->ingredients->count() }};

        $('#add_ingredient').click(function() {
            $('.empty-state').remove();
            let html = `
                <div class="row mb-3 recipe-row">
                    <div class="col-md-7">
                        <select name="ingredients[${recipeIndex}][ingredient_id]" class="form-select form-select-sm" data-control="select2" data-placeholder="Pilih Bahan...">
                            <option></option>
                            @foreach ($allIngredients as $ing)
                                <option value="{{ $ing->id }}">{{ $ing->name }} ({{ $ing->unit }})</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-3">
                        <input type="number" step="0.01" name="ingredients[${recipeIndex}][quantity]" class="form-control form-control-sm" placeholder="Gramasi" required>
                    </div>
                    <div class="col-md-2">
                        <button type="button" class="btn btn-sm btn-icon btn-light-danger remove-recipe"><i class="ki-outline ki-trash fs-5"></i></button>
                    </div>
                </div>
            `;
            $('#recipe_list').append(html);
            $('#recipe_list .recipe-row:last-child select').select2({
                dropdownParent: $('#Modal_Ingredients_Data')
            });
            recipeIndex++;
        });

        $('body').on('click', '.remove-recipe', function() {
            $(this).closest('.recipe-row').remove();
        });

        $('#FormRecipeID').on('submit', function(e) {
            e.preventDefault();
            let btn = $('#btn_save_recipe');
            let originalText = btn.html();
            btn.prop('disabled', true).html('<span class="spinner-border spinner-border-sm me-2"></span> Menyimpan...');

            let id = "{{ $menu->id }}";
            $.ajax({
                url: "{{ url('admin/menus') }}/" + id + "/ingredients",
                method: 'POST',
                data: $(this).serialize(),
                success: function(res) {
                    $('#Modal_Ingredients_Data').modal('hide');
                    Swal.fire("Berhasil!", res.success, "success");
                },
                error: function() {
                    Swal.fire("Error", "Gagal menyimpan resep", "error");
                    btn.prop('disabled', false).html(originalText);
                }
            });
        });
    });
</script>
