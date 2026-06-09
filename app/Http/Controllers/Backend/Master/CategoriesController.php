<?php

namespace App\Http\Controllers\Backend\Master;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Category;
use Illuminate\Support\Str;
use Yajra\DataTables\Facades\DataTables;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;
use Jenssegers\Agent\Agent;

class CategoriesController extends Controller
{
    public function index()
    {
        return view('backend.master.categories.index');
    }

    public function getDataCategories(Request $request)
    {
        if ($request->ajax()) {
            $data = Category::orderBy('created_at', 'desc')->select('*');

            return DataTables::of($data)
                ->addIndexColumn()
                ->addColumn('name', function ($row) {
                    return '<span class="fw-bold text-gray-800">' . $row->name . '</span>';
                })
                ->addColumn('slug', function ($row) {
                    return '<span class="badge badge-light-primary fs-7">' . $row->slug . '</span>';
                })
                ->addColumn('action', function ($row) {
                    $btn = '<div class="dropdown text-end">
                                <button class="btn btn-sm btn-secondary" type="button" data-bs-toggle="dropdown" aria-expanded="false">
                                    Actions <i class="ki-outline ki-down fs-5 ms-1"></i>
                                </button>
                                <ul class="dropdown-menu dropdown-menu-dark fs-6">
                                    <li><a class="dropdown-item btn px-3 btn-detail" href="javascript:void(0)" data-id="' . $row->id . '">Detail</a></li>
                                    <li><a class="dropdown-item btn px-3 btn-edit" href="javascript:void(0)" data-id="' . $row->id . '">Edit</a></li>
                                    <li><a class="dropdown-item btn px-3 btn-delete" href="javascript:void(0)" data-id="' . $row->id . '" data-name="' . $row->name . '">Hapus</a></li>
                                </ul>
                            </div>';
                    return $btn;
                })
                ->rawColumns(['name', 'slug', 'action'])
                ->make(true);
        }
    }

    public function store(Request $request)
    {
        $request->merge(['slug' => Str::slug($request->name)]);

        $validator = \Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'slug' => 'required|string|unique:categories,slug',
        ], [
            'name.required' => 'Nama Kategori wajib diisi.',
            'slug.unique'   => 'Kategori ini sudah ada (Slug duplikat).',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()]);
        }

        try {
            \DB::beginTransaction();

            $category = Category::create([
                'name' => $request->name,
                'slug' => $request->slug,
            ]);

            // === ACTIVITY LOG (FIXED UUID CONFLICT) ===
            $agent = new Agent;
            activity()
                ->useLog('tambah kategori')
                ->causedBy(Auth::user())
                // Dihapus ->performedOn($category) agar tidak error UUID vs Integer
                ->withProperties([
                    'ip'    => $request->ip(),
                    'agent' => ['browser' => $agent->browser(), 'os' => $agent->platform()],
                    'new'   => $category->toArray(),
                ])->log('Menambah kategori produk: ' . $category->name);

            \DB::commit();
            return response()->json(['success' => 'Kategori berhasil ditambahkan.', 'judul' => 'Berhasil'], 201);
        } catch (\Exception $e) {
            \DB::rollback();
            // Menampilkan error asli untuk mempermudah debug jika terjadi masalah lain
            return response()->json(['error' => 'Terjadi kesalahan: ' . $e->getMessage(), 'judul' => 'Gagal'], 500);
        }
    }

    // FUNGSI BARU UNTUK MENAMPILKAN DETAIL
    public function show($id)
    {
        $category = Category::findOrFail($id);
        $html = view('backend.master.categories.show', compact('category'))->render();
        return response()->json(['html' => $html]);
    }

    public function edit($id)
    {
        $category = Category::findOrFail($id);
        $html = view('backend.master.categories.edit', compact('category'))->render();
        return response()->json(['html' => $html]);
    }

    public function update(Request $request, $id)
    {
        $request->merge(['slug' => Str::slug($request->name)]);

        $validator = \Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'slug' => 'required|string|unique:categories,slug,' . $id,
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()]);
        }

        try {
            \DB::beginTransaction();

            $category = Category::findOrFail($id);
            $oldData = $category->toArray();

            $category->update([
                'name' => $request->name,
                'slug' => $request->slug,
            ]);

            // === ACTIVITY LOG ===
            $agent = new Agent;
            activity()
                ->useLog('edit kategori')
                ->causedBy(Auth::user())
                ->withProperties([
                    'ip'  => $request->ip(),
                    'old' => $oldData,
                    'new' => $category->toArray(),
                ])->log('Mengubah kategori produk: ' . $category->name);

            \DB::commit();
            return response()->json(['success' => 'Kategori berhasil diperbarui.', 'judul' => 'Berhasil']);
        } catch (\Exception $e) {
            \DB::rollback();
            return response()->json(['error' => 'Terjadi kesalahan sistem.', 'judul' => 'Gagal'], 500);
        }
    }

    public function destroy(Request $request, $id)
    {
        try {
            \DB::beginTransaction();
            $category = Category::findOrFail($id);
            $oldData = $category->toArray();

            $category->delete();

            activity()->useLog('hapus kategori')->causedBy(Auth::user())
                ->withProperties(['ip' => $request->ip(), 'old' => $oldData])
                ->log('Menghapus kategori produk: ' . $oldData['name']);

            \DB::commit();
            return response()->json(['success' => 'Kategori berhasil dihapus.', 'judul' => 'Berhasil']);
        } catch (\Exception $e) {
            \DB::rollback();
            return response()->json(['error' => 'Kategori gagal dihapus karena sedang digunakan oleh produk.', 'judul' => 'Gagal']);
        }
    }
}
