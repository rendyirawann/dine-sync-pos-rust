<?php

namespace App\Http\Controllers\Backend;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Setting;

class SettingController extends Controller
{
    // Menampilkan form pengaturan (karena cuma 1 baris, kita buat otomatis jika kosong)
    public function index()
    {
        $setting = Setting::first();

        // Jika belum ada data sama sekali, buat 1 baris default
        if (!$setting) {
            $setting = Setting::create([
                'store_name' => 'DineSync POS',
                'tax_rate' => 10
            ]);
        }

        return view('backend.settings.index', compact('setting'));
    }

    // Menyimpan perubahan pengaturan
    public function update(Request $request)
    {
        $request->validate([
            'store_name' => 'required|string|max:255',
            'tax_rate' => 'required|numeric|min:0|max:100',
        ]);

        $setting = Setting::first();
        $setting->update($request->all());

        return redirect()->back()->with('success', 'Pengaturan toko dan pajak berhasil diperbarui!');
    }
}
