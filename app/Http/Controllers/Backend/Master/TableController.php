<?php

namespace App\Http\Controllers\Backend\Master;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Table;
use Yajra\DataTables\Facades\DataTables;
use SimpleSoftwareIO\QrCode\Facades\QrCode;

class TableController extends Controller
{
    public function index()
    {
        return view('backend.master.tables.index');
    }

    public function getDataTables(Request $request)
    {
        if ($request->ajax()) {
            $data = Table::orderBy('table_number', 'asc')->get();

            return DataTables::of($data)
                ->addIndexColumn()
                ->addColumn('status_badge', function ($row) {
                    if ($row->status == 'available') {
                        return '<span class="badge badge-light-success fs-7">Tersedia</span>';
                    } else {
                        return '<span class="badge badge-light-danger fs-7">Terisi</span>';
                    }
                })
                ->addColumn('action', function ($row) {
                    // 🔥 PERBAIKAN: Ganti $row->id menjadi $row->uuid
                    $btn = '<a href="' . route('tables.print-qr', $row->uuid) . '" target="_blank" class="btn btn-sm btn-icon btn-light-success me-2" title="Cetak QR Code"><i class="ki-outline ki-printer fs-4"></i></a>';

                    $btn .= '<button class="btn btn-sm btn-icon btn-light-info btn-detail me-2" data-id="' . $row->id . '" title="Detail"><i class="ki-outline ki-eye fs-4"></i></button>';
                    $btn .= '<button class="btn btn-sm btn-icon btn-light-primary btn-edit me-2" data-id="' . $row->id . '" title="Edit"><i class="ki-outline ki-pencil fs-4"></i></button>';
                    $btn .= '<button class="btn btn-sm btn-icon btn-light-danger btn-delete" data-id="' . $row->id . '" data-number="' . $row->table_number . '" title="Hapus"><i class="ki-outline ki-trash fs-4"></i></button>';
                    return $btn;
                })
                ->rawColumns(['status_badge', 'action'])
                ->make(true);
        }
    }

    public function store(Request $request)
    {
        $request->validate([
            'table_number' => 'required|string|unique:tables,table_number',
            'capacity' => 'required|integer|min:1'
        ]);

        Table::create($request->all());
        return response()->json(['success' => 'Meja berhasil ditambahkan!']);
    }

    public function edit($id)
    {
        $table = Table::findOrFail($id);
        $html = view('backend.master.tables.edit', compact('table'))->render();
        return response()->json(['html' => $html]);
    }

    public function update(Request $request, $id)
    {
        $request->validate([
            'table_number' => 'required|string|unique:tables,table_number,' . $id,
            'capacity' => 'required|integer|min:1',
            'status' => 'required|in:available,occupied'
        ]);

        Table::findOrFail($id)->update($request->all());
        return response()->json(['success' => 'Meja berhasil diupdate!']);
    }

    public function show($id)
    {
        $table = Table::findOrFail($id);
        $html = view('backend.master.tables.show', compact('table'))->render();
        return response()->json(['html' => $html]);
    }

    public function destroy($id)
    {
        Table::findOrFail($id)->delete();
        return response()->json(['success' => 'Meja berhasil dihapus!']);
    }

    // 🔥 PERBAIKAN: Ganti parameter $id menjadi $uuid
    public function printQr($uuid)
    {
        // Cari berdasarkan kolom uuid
        $table = Table::where('uuid', $uuid)->firstOrFail();

        $url = url('/scan/' . $table->uuid);
        $qrcode = QrCode::size(300)->generate($url);

        return view('backend.master.tables.print-qr', compact('table', 'qrcode', 'url'));
    }
}
