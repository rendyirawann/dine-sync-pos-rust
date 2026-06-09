<?php

namespace App\Http\Controllers\Backend\Master;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Menu;
use App\Models\Category;
use Yajra\DataTables\Facades\DataTables;
use Illuminate\Support\Facades\Storage;

class MenuController extends Controller
{
    public function index()
    {
        $categories = Category::orderBy('name', 'asc')->get();
        return view('backend.master.menus.index', compact('categories'));
    }

    public function getDataMenus(Request $request)
    {
        if ($request->ajax()) {
            $data = Menu::with('category')->orderBy('created_at', 'desc')->get();

            return DataTables::of($data)
                ->addIndexColumn()
                ->addColumn('image_view', function ($row) {
                    $imgUrl = $row->image ? asset('storage/menus/' . $row->image) : asset('assets/media/svg/files/blank-image.svg');
                    return '<div class="symbol symbol-50px"><img src="' . $imgUrl . '" alt="foto" style="object-fit:cover;"/></div>';
                })
                ->addColumn('menu_info', function ($row) {
                    $category = $row->category ? $row->category->name : 'Tanpa Kategori';
                    return '<span class="fw-bold text-gray-800">' . $row->name . '</span><br><span class="badge badge-light-primary fs-8 mt-1">' . $category . '</span>';
                })
                ->addColumn('price_format', function ($row) {
                    // Cek jika ada diskon khusus menu ini
                    if ($row->discount_percent > 0) {
                        $discountedPrice = $row->price - ($row->price * ($row->discount_percent / 100));
                        return '<span class="text-muted text-decoration-line-through fs-8">Rp ' . number_format($row->price, 0, ',', '.') . '</span><br>' .
                            '<span class="text-success fw-bold">Rp ' . number_format($discountedPrice, 0, ',', '.') . '</span> ' .
                            '<span class="badge badge-light-danger fs-9">-' . $row->discount_percent . '%</span>';
                    }
                    return '<span class="text-success fw-bold">Rp ' . number_format($row->price, 0, ',', '.') . '</span>';
                })
                ->addColumn('status_badge', function ($row) {
                    if ($row->is_available) {
                        return '<span class="badge badge-light-success fs-7"><i class="ki-outline ki-check fs-5 text-success me-1"></i> Tersedia</span>';
                    } else {
                        return '<span class="badge badge-light-danger fs-7"><i class="ki-outline ki-cross fs-5 text-danger me-1"></i> Habis</span>';
                    }
                })
                ->addColumn('action', function ($row) {
                    $btn = '<button class="btn btn-sm btn-icon btn-light-info btn-ingredients me-2" data-id="' . $row->id . '" title="Resep/Bahan"><i class="ki-outline ki-bucket fs-4"></i></button>';
                    $btn .= '<button class="btn btn-sm btn-icon btn-light-info btn-detail me-2" data-id="' . $row->id . '" title="Detail"><i class="ki-outline ki-eye fs-4"></i></button>';
                    $btn .= '<button class="btn btn-sm btn-icon btn-light-primary btn-edit me-2" data-id="' . $row->id . '" title="Edit"><i class="ki-outline ki-pencil fs-4"></i></button>';
                    $btn .= '<button class="btn btn-sm btn-icon btn-light-danger btn-delete" data-id="' . $row->id . '" data-name="' . $row->name . '" title="Hapus"><i class="ki-outline ki-trash fs-4"></i></button>';
                    return $btn;
                })
                ->rawColumns(['image_view', 'menu_info', 'price_format', 'status_badge', 'action'])
                ->make(true);
        }
    }

    public function store(Request $request)
    {
        $request->validate([
            'category_id'      => 'required|exists:categories,id',
            'name'             => 'required|string|max:255',
            'price'            => 'required|numeric|min:0',
            'discount_percent' => 'nullable|integer|min:0|max:100', // <-- TAMBAHKAN BARIS INI
            'image'            => 'nullable|image|mimes:jpeg,png,jpg|max:2048',
            'description'      => 'nullable|string'
        ]);

        $data = $request->except('image');
        $data['is_available'] = $request->has('is_available') ? true : false;

        // Proses Upload Image
        if ($request->hasFile('image')) {
            $file = $request->file('image');
            $filename = 'menu-' . time() . '.' . $file->getClientOriginalExtension();
            Storage::disk('public')->putFileAs('menus', $file, $filename);
            $data['image'] = $filename;
        }

        Menu::create($data);
        return response()->json(['success' => 'Menu berhasil ditambahkan!']);
    }

    public function show($id)
    {
        $menu = Menu::with('category')->findOrFail($id);
        $html = view('backend.master.menus.show', compact('menu'))->render();
        return response()->json(['html' => $html]);
    }

    public function edit($id)
    {
        $menu = Menu::findOrFail($id);
        $categories = Category::orderBy('name', 'asc')->get();
        $html = view('backend.master.menus.edit', compact('menu', 'categories'))->render();
        return response()->json(['html' => $html]);
    }

    public function update(Request $request, $id)
    {
        $request->validate([
            'category_id'      => 'required|exists:categories,id',
            'name'             => 'required|string|max:255',
            'price'            => 'required|numeric|min:0',
            'discount_percent' => 'nullable|integer|min:0|max:100', // <-- TAMBAHKAN BARIS INI
            'image'            => 'nullable|image|mimes:jpeg,png,jpg|max:2048',
            'description'      => 'nullable|string'
        ]);

        $menu = Menu::findOrFail($id);
        $data = $request->except('image');
        $data['is_available'] = $request->has('is_available') ? true : false;

        // Proses Upload Image Baru jika ada
        if ($request->hasFile('image')) {
            // Hapus gambar lama
            if ($menu->image && Storage::disk('public')->exists('menus/' . $menu->image)) {
                Storage::disk('public')->delete('menus/' . $menu->image);
            }
            $file = $request->file('image');
            $filename = 'menu-' . time() . '.' . $file->getClientOriginalExtension();
            Storage::disk('public')->putFileAs('menus', $file, $filename);
            $data['image'] = $filename;
        }

        $menu->update($data);
        return response()->json(['success' => 'Menu berhasil diupdate!']);
    }

    public function destroy($id)
    {
        $menu = Menu::findOrFail($id);
        // Hapus file gambar jika ada
        if ($menu->image && Storage::disk('public')->exists('menus/' . $menu->image)) {
            Storage::disk('public')->delete('menus/' . $menu->image);
        }
        $menu->delete();
        return response()->json(['success' => 'Menu berhasil dihapus!']);
    }

    // 🔥 NEW: Recipe Management
    public function ingredients($id)
    {
        $menu = Menu::with('ingredients.ingredient')->findOrFail($id);
        $allIngredients = \App\Models\Ingredient::orderBy('name', 'asc')->get();
        $html = view('backend.master.menus.ingredients', compact('menu', 'allIngredients'))->render();
        return response()->json(['html' => $html]);
    }

    public function updateIngredients(Request $request, $id)
    {
        $menu = Menu::findOrFail($id);
        
        \DB::transaction(function() use ($menu, $request) {
            // Clear existing
            $menu->ingredients()->delete();
            
            // Add new ones
            if ($request->ingredients) {
                foreach ($request->ingredients as $item) {
                    if ($item['ingredient_id'] && $item['quantity'] > 0) {
                        $menu->ingredients()->create([
                            'ingredient_id' => $item['ingredient_id'],
                            'quantity' => $item['quantity']
                        ]);
                    }
                }
            }
        });

        return response()->json(['success' => 'Resep/Bahan menu berhasil diperbarui!']);
    }
}
