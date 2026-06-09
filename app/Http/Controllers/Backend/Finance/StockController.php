<?php

namespace App\Http\Controllers\Backend\Finance;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Ingredient;
use App\Models\IngredientBatch;
use App\Models\Supplier;
use App\Models\StockMovement;
use Yajra\DataTables\Facades\DataTables;
use Illuminate\Support\Facades\DB;

class StockController extends Controller
{
    public function index()
    {
        $ingredients = Ingredient::orderBy('name', 'asc')->get();
        $suppliers = Supplier::orderBy('name', 'asc')->get();
        return view('backend.finance.stock.index', compact('ingredients', 'suppliers'));
    }

    public function getData(Request $request)
    {
        if ($request->ajax()) {
            $data = IngredientBatch::with(['ingredient', 'supplier'])->orderBy('entry_date', 'desc')->get();
            return DataTables::of($data)
                ->addIndexColumn()
                ->addColumn('ingredient_name', fn($row) => $row->ingredient->name)
                ->addColumn('supplier_name', fn($row) => $row->supplier->name ?? 'Manual/Tanpa Supplier')
                ->addColumn('quantity_display', fn($row) => number_format($row->remaining_quantity, 2) . ' / ' . number_format($row->initial_quantity, 2) . ' ' . $row->ingredient->unit)
                ->addColumn('price_format', function ($row) {
                    $total = 'Rp ' . number_format($row->buy_price_total, 0, ',', '.');
                    $unit = 'Rp ' . number_format($row->buy_price, 0, ',', '.');
                    return "<div><span class='fw-bold text-dark'>$total</span><br><small class='text-muted'>$unit / " . ($row->ingredient->unit ?? 'unit') . "</small></div>";
                })
                ->addColumn('action', function ($row) {
                    return '<button class="btn btn-sm btn-icon btn-light-danger btn-delete" data-id="' . $row->id . '" title="Hapus Batch"><i class="ki-outline ki-trash fs-4"></i></button>';
                })
                ->rawColumns(['quantity_display', 'price_format', 'action'])
                ->make(true);
        }
    }

    public function store(Request $request)
    {
        $request->validate([
            'ingredient_id' => 'required|exists:ingredients,id',
            'initial_quantity' => 'required|numeric|min:0.01',
            'buy_price' => 'required|numeric|min:0', // Ini sekarang adalah Harga Total
            'entry_date' => 'required|date',
            'supplier_id' => 'nullable|exists:suppliers,id',
        ]);

        $unitPrice = $request->buy_price / $request->initial_quantity;
        $buy_price_total = $request->buy_price;

        DB::transaction(function () use ($request, $unitPrice, $buy_price_total) {
            $batch = IngredientBatch::create([
                'ingredient_id' => $request->ingredient_id,
                'supplier_id' => $request->supplier_id ?: null,
                'initial_quantity' => $request->initial_quantity,
                'remaining_quantity' => $request->initial_quantity,
                'buy_price' => $unitPrice,
                'buy_price_total' => $buy_price_total,
                'entry_date' => $request->entry_date,
                'expiry_date' => $request->expiry_date ?: null,
            ]);

            StockMovement::create([
                'ingredient_id' => $request->ingredient_id,
                'ingredient_batch_id' => $batch->id,
                'type' => 'in',
                'quantity' => $request->initial_quantity,
                'reason' => 'purchase',
                'reference' => 'Manual Input Batch #' . $batch->id
            ]);
        });

        return response()->json(['success' => 'Stok bahan berhasil ditambahkan ke sistem FIFO!']);
    }

    public function destroy($id)
    {
        DB::transaction(function () use ($id) {
            $batch = IngredientBatch::findOrFail($id);
            // Optional: check if already used? Usually yes.
            $batch->delete();
        });
        return response()->json(['success' => 'Batch stok berhasil dihapus!']);
    }
}
