<?php

namespace App\Http\Controllers\Backend\Finance;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Ingredient;
use App\Models\StockOpname;
use App\Models\StockOpnameDetail;
use App\Services\StockService;
use Yajra\DataTables\Facades\DataTables;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;

class StockOpnameController extends Controller
{
    protected $stockService;

    public function __construct(StockService $stockService)
    {
        $this->stockService = $stockService;
    }

    public function index()
    {
        return view('backend.finance.stock_opname.index');
    }

    public function getData(Request $request)
    {
        if ($request->ajax()) {
            $data = Ingredient::all();
            return DataTables::of($data)
                ->addIndexColumn()
                ->addColumn('system_stock', function($row) {
                    return number_format($row->currentStock(), 2) . ' ' . $row->unit;
                })
                ->addColumn('input_physical', function($row) {
                    return '<input type="number" step="0.01" class="form-control form-control-sm physical-input" data-id="'.$row->id.'" data-system="'.$row->currentStock().'" placeholder="Stok Fisik">';
                })
                ->addColumn('difference', function($row) {
                    return '<span class="diff-display" id="diff-'.$row->id.'">0</span> ' . $row->unit;
                })
                ->rawColumns(['input_physical', 'difference'])
                ->make(true);
    }
    }

    public function getHistoryData(Request $request)
    {
        if ($request->ajax()) {
            $data = StockOpname::with('user')->orderBy('date', 'desc')->get();
            return DataTables::of($data)
                ->addIndexColumn()
                ->addColumn('user_name', fn($row) => $row->user->name)
                ->addColumn('date_format', fn($row) => date('d M Y', strtotime($row->date)))
                ->addColumn('action', function ($row) {
                    return '<a href="'.route('stock-opname.pdf', $row->id).'" target="_blank" class="btn btn-sm btn-icon btn-light-danger" title="Cetak PDF"><i class="ki-outline ki-document fs-4"></i></a>';
                })
                ->make(true);
        }
    }

    public function store(Request $request)
    {
        $adjustmentsInput = $request->adjustments ?? []; // Array of [id, physical_qty]
        $adjustmentsMap = [];
        foreach ($adjustmentsInput as $adj) {
            $adjustmentsMap[$adj['id']] = $adj['physical_qty'];
        }

        DB::transaction(function () use ($adjustmentsMap) {
            $opname = StockOpname::create([
                'user_id' => Auth::id(),
                'date' => date('Y-m-d'),
                'notes' => 'Stock Opname Berkala'
            ]);

            $ingredients = Ingredient::all();

            foreach ($ingredients as $ingredient) {
                $systemQty = $ingredient->currentStock();
                
                // Jika user mengisi fisik, pakai itu. Jika tidak, anggap fisik = sistem.
                $physicalQty = (isset($adjustmentsMap[$ingredient->id]) && $adjustmentsMap[$ingredient->id] !== '') 
                                ? $adjustmentsMap[$ingredient->id] 
                                : $systemQty;

                $diff = $physicalQty - $systemQty;

                // Simpan Riwayat Detail (Pasti tersimpan meskipun selisih 0)
                StockOpnameDetail::create([
                    'stock_opname_id' => $opname->id,
                    'ingredient_id' => $ingredient->id,
                    'system_qty' => $systemQty,
                    'physical_qty' => $physicalQty,
                    'difference' => $diff
                ]);

                // Hanya jalankan adjusment stok jika benar-benar ada selisih
                if ($diff != 0) {
                    $this->stockService->adjustStock($ingredient->id, $physicalQty, 'stock_opname');
                }
            }
        });

        return response()->json(['success' => 'Stock Opname berhasil disimpan! Seluruh bahan telah tercatat di laporan.']);
    }

    public function downloadPdf($id)
    {
        $opname = StockOpname::with(['user', 'details.ingredient'])->findOrFail($id);
        $pdf = Pdf::loadView('backend.finance.stock_opname.pdf', compact('opname'));
        return $pdf->stream('Stock_Opname_' . $opname->date . '.pdf');
    }
}
