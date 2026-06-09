<?php

namespace App\Http\Controllers\Backend\Master;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Ingredient;
use Yajra\DataTables\Facades\DataTables;

class IngredientController extends Controller
{
    public function index()
    {
        return view('backend.master.ingredients.index');
    }

    public function getData(Request $request)
    {
        if ($request->ajax()) {
            $data = Ingredient::withCount(['batches as stock' => function($query) {
                $query->select(\DB::raw('sum(remaining_quantity)'));
            }])->get();

            return DataTables::of($data)
                ->addIndexColumn()
                ->addColumn('stock_display', function ($row) {
                    $stock = $row->stock ?? 0;
                    $badge = $stock <= $row->minimum_stock ? 'badge-light-danger' : 'badge-light-success';
                    return '<span class="badge ' . $badge . ' fs-7">' . number_format($stock, 2) . ' ' . $row->unit . '</span>';
                })
                ->addColumn('action', function ($row) {
                    $btn = '<button class="btn btn-sm btn-icon btn-light-primary btn-edit me-2" data-id="' . $row->id . '" title="Edit"><i class="ki-outline ki-pencil fs-4"></i></button>';
                    $btn .= '<button class="btn btn-sm btn-icon btn-light-danger btn-delete" data-id="' . $row->id . '" data-name="' . $row->name . '" title="Hapus"><i class="ki-outline ki-trash fs-4"></i></button>';
                    return $btn;
                })
                ->rawColumns(['stock_display', 'action'])
                ->make(true);
        }
    }

    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'unit' => 'required|string|max:50',
            'minimum_stock' => 'required|numeric|min:0',
        ]);

        Ingredient::create($request->all());
        return response()->json(['success' => 'Bahan makanan berhasil ditambahkan!']);
    }

    public function edit($id)
    {
        $ingredient = Ingredient::findOrFail($id);
        return response()->json($ingredient);
    }

    public function update(Request $request, $id)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'unit' => 'required|string|max:50',
            'minimum_stock' => 'required|numeric|min:0',
        ]);

        $ingredient = Ingredient::findOrFail($id);
        $ingredient->update($request->all());
        return response()->json(['success' => 'Bahan makanan berhasil diperbarui!']);
    }

    public function destroy($id)
    {
        $ingredient = Ingredient::findOrFail($id);
        $ingredient->delete();
        return response()->json(['success' => 'Bahan makanan berhasil dihapus!']);
    }
}
