<?php

namespace App\Http\Controllers\Backend\Master;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Supplier;
use Yajra\DataTables\Facades\DataTables;

class SupplierController extends Controller
{
    public function index()
    {
        return view('backend.master.suppliers.index');
    }

    public function getData(Request $request)
    {
        if ($request->ajax()) {
            $data = Supplier::all();
            return DataTables::of($data)
                ->addIndexColumn()
                ->addColumn('action', function ($row) {
                    $btn = '<button class="btn btn-sm btn-icon btn-light-primary btn-edit me-2" data-id="' . $row->id . '" title="Edit"><i class="ki-outline ki-pencil fs-4"></i></button>';
                    $btn .= '<button class="btn btn-sm btn-icon btn-light-danger btn-delete" data-id="' . $row->id . '" data-name="' . $row->name . '" title="Hapus"><i class="ki-outline ki-trash fs-4"></i></button>';
                    return $btn;
                })
                ->make(true);
        }
    }

    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'phone' => 'nullable|string|max:20',
        ]);

        Supplier::create($request->all());
        return response()->json(['success' => 'Supplier berhasil ditambahkan!']);
    }

    public function edit($id)
    {
        return response()->json(Supplier::findOrFail($id));
    }

    public function update(Request $request, $id)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'phone' => 'nullable|string|max:20',
        ]);

        $supplier = Supplier::findOrFail($id);
        $supplier->update($request->all());
        return response()->json(['success' => 'Supplier berhasil diperbarui!']);
    }

    public function destroy($id)
    {
        Supplier::findOrFail($id)->delete();
        return response()->json(['success' => 'Supplier berhasil dihapus!']);
    }
}
