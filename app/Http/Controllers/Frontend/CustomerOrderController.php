<?php

namespace App\Http\Controllers\Frontend;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Table;
use App\Models\Setting;
use App\Models\Category;
use App\Models\Menu;
use App\Models\Promo;
use App\Models\Order;
use App\Models\OrderDetail;

class CustomerOrderController extends Controller
{
    public function scan($uuid)
    {
        $table = Table::where('uuid', $uuid)->firstOrFail();
        $setting = Setting::first();

        // 🔥 VALIDASI: Jika meja terisi, pastikan yang scan adalah pelanggan yang sama
        if ($table->status == 'occupied') {
            if (!session('customer_name') || session('table_uuid') != $uuid) {
                // Jika tidak punya session (tamu lain), lempar ke halaman peringatan
                $isOccupied = true;
                return view('frontend.scan', compact('table', 'setting', 'isOccupied'));
            }
        } else {
            // Jika meja kosong (available), pastikan HP tidak menyimpan memori pesanan lama
            session()->forget(['customer_name', 'table_uuid']);
        }

        // Jika session masih ada & valid, SKIP form nama
        if (session('customer_name') && session('table_uuid') == $uuid) {
            return redirect()->route('frontend.menu', $uuid);
        }

        return view('frontend.scan', compact('table', 'setting'));
    }

    // Fungsi untuk memproses nama pelanggan dan lanjut ke menu
    public function startOrder(Request $request, $uuid)
    {
        $request->validate([
            'customer_name' => 'required|string|max:255'
        ]);

        $table = Table::where('uuid', $uuid)->firstOrFail();

        // Simpan nama pelanggan di Session browser HP mereka
        session(['customer_name' => $request->customer_name]);
        session(['table_uuid' => $table->uuid]);

        // Redirect ke halaman daftar menu (yang akan kita buat di Langkah 3)
        return redirect()->route('frontend.menu', $uuid);
    }

    public function menu($uuid)
    {
        $customerName = session('customer_name');
        if (!$customerName || session('table_uuid') != $uuid) {
            return redirect()->route('frontend.scan', $uuid);
        }

        $table = Table::where('uuid', $uuid)->firstOrFail();

        // Failsafe "Available" yang bikin looping SUDAH SAYA HAPUS DI SINI.

        $setting = Setting::first();
        $categories = Category::orderBy('name', 'asc')->get();
        $menus = Menu::with('category')->where('is_available', true)->get();
        $promos = Promo::where('is_active', true)->get();

        $activeOrders = Order::with('details.menu')
            ->where('table_id', $table->id)
            ->whereIn('order_status', ['pending', 'cooking', 'served'])
            ->orderBy('created_at', 'desc')
            ->get();

        return view('frontend.menu', compact('table', 'customerName', 'setting', 'categories', 'menus', 'promos', 'activeOrders'));
    }

    // Fungsi untuk Memproses Pesanan & Midtrans dari HP Pelanggan
    public function checkout(Request $request, $uuid)
    {
        try {
            \DB::beginTransaction();
            $table = Table::where('uuid', $uuid)->firstOrFail();
            $customerName = session('customer_name', 'Tamu');

            if (empty($request->cart)) {
                return response()->json(['success' => false, 'message' => 'Keranjang kosong!'], 400);
            }

            // 1. Kalkulasi Ulang di Backend (Anti Hack)
            $subtotal = 0;
            foreach ($request->cart as $item) {
                $subtotal += $item['subtotal'];
            }

            $discount_amount = 0;
            if ($request->promo_id) {
                $promo = Promo::find($request->promo_id);
                if ($promo && $promo->is_active) {
                    if ($promo->discount_type == 'percentage') {
                        $discount_amount = round($subtotal * ($promo->discount_value / 100));
                    } else {
                        $discount_amount = $promo->discount_value;
                    }
                }
            }

            $net_subtotal = $subtotal - $discount_amount;
            if ($net_subtotal < 0) $net_subtotal = 0;

            $setting = Setting::first();
            $tax_rate = $setting ? $setting->tax_rate : 0;
            $tax = round($net_subtotal * ($tax_rate / 100));
            $grand_total = $net_subtotal + $tax;

            // 2. Buat Nomor Invoice & Simpan Order
            $invoice_no = 'INV-' . date('YmdHis') . rand(10, 99);
            $payment_method = $request->payment_method; // 'pay_later' atau 'midtrans'

            $order = Order::create([
                'invoice_no'     => $invoice_no,
                'table_id'       => $table->id,
                'promo_id'       => $request->promo_id,
                'customer_name'  => $customerName,
                'order_type'     => 'dine_in',
                'subtotal'       => $subtotal,
                'discount_amount' => $discount_amount,
                'tax'            => $tax,
                'grand_total'    => $grand_total,
                'payment_method' => $payment_method == 'pay_later' ? null : $payment_method,
                'payment_status' => 'unpaid', // Akan diubah webhook jika lunas
                'order_status'   => 'pending', // Masuk KDS Dapur
            ]);

            foreach ($request->cart as $item) {
                $order->details()->create([
                    'menu_id'  => $item['id'],
                    'qty'      => $item['qty'],
                    'price'    => $item['price'],
                    'subtotal' => $item['subtotal'],
                    'notes'    => $item['note'] ?? null,
                    'status'   => 'pending' // Item menunggu dimasak
                ]);
            }

            // Ubah meja jadi warna merah/terisi di Peta Kasir
            $table->update(['status' => 'occupied']);

            // 3. JIKA PELANGGAN MILIH MIDTRANS ONLINE
            if ($payment_method == 'midtrans') {
                \Midtrans\Config::$serverKey = env('MIDTRANS_SERVER_KEY');
                \Midtrans\Config::$isProduction = env('MIDTRANS_IS_PRODUCTION', false);
                \Midtrans\Config::$isSanitized = true;
                \Midtrans\Config::$is3ds = true;
                \Midtrans\Config::$overrideNotifUrl = 'https://omnificent-reena-intermeasurable.ngrok-free.dev/api/midtrans-webhook';

                $params = [
                    'transaction_details' => ['order_id' => $invoice_no, 'gross_amount' => (int) $grand_total],
                    'customer_details' => ['first_name' => $customerName]
                ];

                $snapToken = \Midtrans\Snap::getSnapToken($params);
                $order->update(['snap_token' => $snapToken]);

                \DB::commit();
                return response()->json(['success' => true, 'type' => 'midtrans', 'snap_token' => $snapToken]);
            }

            // 4. JIKA BAYAR NANTI (KASIR)
            \DB::commit();
            return response()->json(['success' => true, 'type' => 'pay_later', 'redirect' => route('frontend.success', $uuid)]);
        } catch (\Exception $e) {
            \DB::rollback();
            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }

    // Fungsi untuk menampilkan halaman Sukses
    public function success($uuid)
    {
        $table = Table::where('uuid', $uuid)->firstOrFail();
        $setting = Setting::first();
        $customerName = session('customer_name', 'Tamu');

        return view('frontend.success', compact('table', 'setting', 'customerName'));
    }
}
