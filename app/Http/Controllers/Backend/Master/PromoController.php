<?php

namespace App\Http\Controllers\Backend\Master;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Promo;
use Yajra\DataTables\Facades\DataTables;

class PromoController extends Controller
{
    public function index()
    {
        return view('backend.master.promos.index');
    }

    public function getData(Request $request)
    {
        if ($request->ajax()) {
            $data = Promo::orderBy('created_at', 'desc')->get();

            return DataTables::of($data)
                ->addIndexColumn()
                ->addColumn('discount_info', function ($row) {
                    if ($row->discount_type == 'percentage') {
                        return '<span class="badge badge-light-primary fs-6 fw-bold">' . $row->discount_value . '%</span>';
                    } else {
                        return '<span class="badge badge-light-success fs-6 fw-bold">Rp ' . number_format($row->discount_value, 0, ',', '.') . '</span>';
                    }
                })
                ->addColumn('status_toggle', function ($row) {
                    $checked = $row->is_active ? 'checked' : '';
                    return '
                        <div class="form-check form-switch form-check-custom form-check-solid justify-content-center">
                            <input class="form-check-input h-25px w-40px toggle-status" type="checkbox" data-id="' . $row->id . '" ' . $checked . ' />
                        </div>
                    ';
                })
                ->addColumn('action', function ($row) {
                    return '
                        <div class="d-flex justify-content-center gap-2">
                            <button class="btn btn-sm btn-icon btn-light-primary btn-edit" data-id="' . $row->id . '" title="Edit">
                                <i class="ki-outline ki-pencil fs-3"></i>
                            </button>
                            <button class="btn btn-sm btn-icon btn-light-danger btn-delete" data-id="' . $row->id . '" data-name="' . $row->name . '" title="Hapus">
                                <i class="ki-outline ki-trash fs-3"></i>
                            </button>
                        </div>
                    ';
                })
                ->rawColumns(['discount_info', 'status_toggle', 'action'])
                ->make(true);
        }
    }

    public function store(Request $request)
    {
        $request->validate([
            'name'           => 'required|string|max:255',
            'discount_type'  => 'required|in:percentage,nominal',
            'discount_value' => 'required|numeric|min:1',
        ]);

        Promo::create([
            'name'           => $request->name,
            'discount_type'  => $request->discount_type,
            'discount_value' => $request->discount_value,
            'is_active'      => $request->has('is_active') ? true : false,
        ]);

        return response()->json(['success' => 'Promo berhasil ditambahkan!']);
    }

    public function edit($id)
    {
        $promo = Promo::findOrFail($id);
        return response()->json($promo);
    }

    public function update(Request $request, $id)
    {
        $request->validate([
            'name'           => 'required|string|max:255',
            'discount_type'  => 'required|in:percentage,nominal',
            'discount_value' => 'required|numeric|min:1',
        ]);

        $promo = Promo::findOrFail($id);
        $promo->update([
            'name'           => $request->name,
            'discount_type'  => $request->discount_type,
            'discount_value' => $request->discount_value,
            'is_active'      => $request->has('is_active') ? true : false,
        ]);

        return response()->json(['success' => 'Promo berhasil diperbarui!']);
    }

    public function destroy($id)
    {
        $promo = Promo::findOrFail($id);
        $promo->delete();
        return response()->json(['success' => 'Promo berhasil dihapus!']);
    }

    // Fungsi khusus untuk Switch On/Off
    public function toggleStatus(Request $request, $id)
    {
        $promo = Promo::findOrFail($id);
        $promo->update(['is_active' => $request->is_active]);
        return response()->json(['success' => 'Status promo berhasil diubah!']);
    }
}
