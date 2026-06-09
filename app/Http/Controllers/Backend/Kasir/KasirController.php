<?php

namespace App\Http\Controllers\Backend\Kasir;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Table;
use App\Models\Order;
use App\Models\Menu;
use App\Models\Category;
use App\Models\Setting;
use Illuminate\Support\Facades\Auth;
use App\Models\Shift;
use App\Models\Promo;

class KasirController extends Controller
{
    public function index()
    {
        $activeShift = Shift::where('user_id', Auth::id())->where('status', 'open')->first();

        if (!$activeShift) {
            return redirect()->route('shifts.index')->with('warning', '⚠️ Akses ditolak! Anda wajib membuka shift dan mengisi modal kasir terlebih dahulu.');
        }

        $tables = Table::orderBy('table_number', 'asc')->get();

        $emptyCount = 0;
        $unpaidCount = 0;
        $paidCount = 0;

        foreach ($tables as $table) {
            if ($table->status == 'available') {
                $emptyCount++;
            } elseif ($table->status == 'occupied') {
                // 🔥 PERBAIKAN: Ambil SEMUA order di meja ini
                $activeOrders = Order::where('table_id', $table->id)
                    ->whereIn('order_status', ['pending', 'cooking', 'served'])
                    ->get();

                if ($activeOrders->isNotEmpty()) {
                    // Jika ada 1 saja invoice yang belum lunas, meja statusnya Kuning (Belum Bayar)
                    $hasUnpaid = $activeOrders->contains('payment_status', 'unpaid');
                    $table->payment_status = $hasUnpaid ? 'unpaid' : 'paid';

                    if ($hasUnpaid) {
                        $unpaidCount++;
                    } else {
                        $paidCount++;
                    }
                } else {
                    // Failsafe (Data kotor)
                    $table->update(['status' => 'available']);
                    $emptyCount++;
                }
            }
        }

        return view('backend.kasir.index', compact('tables', 'emptyCount', 'unpaidCount', 'paidCount'));
    }

    public function getTableDetail($id)
    {
        $table = Table::findOrFail($id);

        if ($table->status == 'available') {
            return response()->json(['status' => 'available', 'table_number' => $table->table_number]);
        }

        // 🔥 PERBAIKAN: Gunakan ->get() untuk mengambil SEMUA invoice aktif
        $orders = Order::with('details.menu')
            ->where('table_id', $id)
            ->whereIn('order_status', ['pending', 'cooking', 'served'])
            ->orderBy('created_at', 'desc') // Yang paling baru di atas
            ->get();

        if ($orders->isEmpty()) {
            $table->update(['status' => 'available']);
            return response()->json(['status' => 'available', 'table_number' => $table->table_number]);
        }

        // Kirim variabel $orders (banyak) bukan $order (satu)
        $html = view('backend.kasir._table_detail', compact('table', 'orders'))->render();

        return response()->json([
            'status' => 'occupied',
            'html' => $html
        ]);
    }

    public function createOrder(Request $request, $table_id)
    {
        $table = Table::findOrFail($table_id);

        if ($table->status == 'occupied') {
            return redirect()->route('kasir.index')->with('error', 'Meja sudah terisi! Tidak bisa membuat pesanan baru.');
        }

        $customer_name = $request->query('customer', 'Walk-in');

        // 🔥 TAMBAHAN: Tangkap tipe pesanan dari URL
        $order_type = $request->query('type', 'dine_in');

        $categories = Category::orderBy('name', 'asc')->get();
        $menus = Menu::with('category')->where('is_available', true)->get();
        $setting = Setting::first();
        $promos = Promo::where('is_active', true)->get();

        // 🔥 TAMBAHKAN $order_type ke compact()
        return view('backend.kasir.order', compact('table', 'customer_name', 'order_type', 'categories', 'menus', 'setting', 'promos'));
    }

    public function storeOrder(Request $request)
    {
        try {
            \DB::beginTransaction();

            $subtotal = 0;
            foreach ($request->cart as $item) {
                $subtotal += $item['subtotal'];
            }
            // HITUNG DISKON (JIKA ADA PROMO YANG DIPILIH KASIR)
            $discount_amount = 0;
            if ($request->promo_id) {
                $promo = Promo::find($request->promo_id);
                if ($promo && $promo->is_active) {
                    if ($promo->discount_type == 'percentage') {
                        // 🔥 Tambahkan round() di sini
                        $discount_amount = round($subtotal * ($promo->discount_value / 100));
                    } else {
                        $discount_amount = $promo->discount_value;
                    }
                }
            }

            $net_subtotal = $subtotal - $discount_amount;
            if ($net_subtotal < 0) $net_subtotal = 0; // Cegah minus

            $setting = Setting::first();
            $tax_rate = $setting ? $setting->tax_rate : 0;

            // Pajak dikenakan SETELAH dipotong diskon
            // 🔥 Tambahkan round() di sini juga
            $tax = round($net_subtotal * ($tax_rate / 100));
            $grand_total = $net_subtotal + $tax;

            $invoice_no = 'INV-' . date('YmdHis') . rand(10, 99);
            $order = Order::create([
                'invoice_no'     => $invoice_no,
                'table_id'       => $request->table_id,
                'promo_id'        => $request->promo_id, // Simpan ID Promo
                'customer_name'  => $request->customer_name,
                'order_type'     => $request->order_type ?? 'dine_in',
                'subtotal'       => $subtotal,
                'discount_amount' => $discount_amount, // Simpan Nominal Diskon
                'tax'            => $tax,
                'grand_total'    => $grand_total,
                'payment_method' => $request->payment_method == 'pay_later' ? null : $request->payment_method,
                'payment_status' => 'unpaid',
                'order_status'   => 'pending',
            ]);

            foreach ($request->cart as $item) {
                $order->details()->create([
                    'menu_id'  => $item['id'],
                    'qty' => $item['qty'],
                    'price' => $item['price'],
                    'subtotal' => $item['subtotal'],
                    'notes' => $item['note'] ?? null,
                    'status' => 'pending'
                ]);
            }

            Table::where('id', $request->table_id)->update(['status' => 'occupied']);

            // JIKA PAY LATER (BAYAR NANTI)
            if ($request->payment_method == 'pay_later') {
                \DB::commit();
                return response()->json([
                    'success' => true,
                    'type'    => 'pay_later',
                    'message' => 'Pesanan dikirim ke dapur. Pembayaran ditangguhkan (Pay Later).'
                ]);
            }
            // JIKA CASH
            else if ($request->payment_method == 'cash') {
                $order->update(['payment_status' => 'paid']);
                \DB::commit();
                return response()->json(['success' => true, 'type' => 'cash', 'message' => 'Pembayaran tunai berhasil!', 'order_id' => $order->id]);
            }
            // JIKA MIDTRANS
            else if ($request->payment_method == 'midtrans') {
                \Midtrans\Config::$serverKey = config('services.midtrans.server_key');
                \Midtrans\Config::$isProduction = config('services.midtrans.is_production', false);
                \Midtrans\Config::$isSanitized = true;
                \Midtrans\Config::$is3ds = true;

                // 🔥 TAMBAHKAN BARIS SAKTI INI: 
                // Memaksa Midtrans lapor ke Ngrok, mengabaikan bug Dashboard Sandbox
                \Midtrans\Config::$overrideNotifUrl = 'https://omnificent-reena-intermeasurable.ngrok-free.dev/api/midtrans-webhook';

                $params = [
                    'transaction_details' => ['order_id' => $invoice_no, 'gross_amount' => (int) $grand_total],
                    'customer_details' => ['first_name' => $request->customer_name]
                ];

                $snapToken = \Midtrans\Snap::getSnapToken($params);
                $order->update(['snap_token' => $snapToken]);

                \DB::commit();
                return response()->json(['success' => true, 'type' => 'midtrans', 'snap_token' => $snapToken, 'order_id' => $order->id]);
            }
        } catch (\Exception $e) {
            \DB::rollback();
            return response()->json(['success' => false, 'error' => $e->getMessage()], 500);
        }
    }

    // FUNGSI BARU: Untuk bayar pesanan yang statusnya "Belum Bayar"
    // FUNGSI BARU: Untuk bayar pesanan yang statusnya "Belum Bayar"
    public function payExistingOrder(Request $request)
    {
        try {
            $order = Order::findOrFail($request->order_id);
            $order->update(['payment_method' => $request->payment_method]);

            if ($request->payment_method == 'cash') {
                $order->update(['payment_status' => 'paid']);
                return response()->json(['success' => true, 'type' => 'cash', 'message' => 'Pembayaran tunai lunas!', 'order_id' => $order->id]);
            } else if ($request->payment_method == 'midtrans') {

                \Midtrans\Config::$serverKey = config('services.midtrans.server_key');
                \Midtrans\Config::$isProduction = config('services.midtrans.is_production', false);
                \Midtrans\Config::$isSanitized = true;
                \Midtrans\Config::$is3ds = true;

                // 🔥 TAMBAHKAN BARIS SAKTI INI JUGA:
                \Midtrans\Config::$overrideNotifUrl = 'https://omnificent-reena-intermeasurable.ngrok-free.dev/api/midtrans-webhook';

                // TRIK JITU: Tambahkan suffix "-R" (Retry)

                // TRIK JITU: Tambahkan suffix "-R" (Retry) dan angka random di belakang Invoice 
                // Agar Midtrans mereset ulang pilihan metode pembayaran untuk nota ini
                $retry_invoice = $order->invoice_no . '-R' . rand(100, 999);

                $params = [
                    'transaction_details' => ['order_id' => $retry_invoice, 'gross_amount' => (int) $order->grand_total],
                    'customer_details' => ['first_name' => $order->customer_name]
                ];

                $snapToken = \Midtrans\Snap::getSnapToken($params);
                $order->update(['snap_token' => $snapToken]);

                return response()->json(['success' => true, 'type' => 'midtrans', 'snap_token' => $snapToken, 'order_id' => $order->id]);
            }
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'error' => $e->getMessage()], 500);
        }
    }

    // FUNGSI BARU: Update status jadi paid dari Javascript (Khusus Localhost/Client-side)
    // FUNGSI BARU: Update status jadi paid dari Javascript (Khusus Localhost/Client-side)
    // public function paymentSuccessLocal(Request $request)
    // {
    //     try {
    //         // Ambil order_id dari respon Midtrans (contoh: INV-20260310... atau INV-2026...-R123)
    //         // Kita pisahkan '-R' untuk jaga-jaga kalau itu dari trik pembayaran susulan kita
    //         $invoice_raw = $request->order_id;
    //         $invoice_no = explode('-R', $invoice_raw)[0];

    //         $order = Order::where('invoice_no', $invoice_no)->first();

    //         if ($order) {
    //             $order->update(['payment_status' => 'paid']);
    //             return response()->json(['success' => true, 'message' => 'Status database diupdate jadi Lunas!']);
    //         }

    //         return response()->json(['success' => false, 'message' => 'Order tidak ditemukan.']);
    //     } catch (\Exception $e) {
    //         return response()->json(['success' => false, 'error' => $e->getMessage()], 500);
    //     }
    // }

    public function handleWebhook(Request $request)
    {
        $serverKey = config('services.midtrans.server_key');

        // 1. Generate Signature Key untuk verifikasi keamanan
        $hashed = hash("sha512", $request->order_id . $request->status_code . $request->gross_amount . $serverKey);

        if ($hashed !== $request->signature_key) {
            return response()->json(['message' => 'Invalid signature'], 403);
        }

        // 2. Ambil Order ID (bersihkan suffix -R jika ada)
        $invoice_raw = $request->order_id;
        $invoice_no = explode('-R', $invoice_raw)[0];
        $order = Order::where('invoice_no', $invoice_no)->first();

        if (!$order) return response()->json(['message' => 'Order not found'], 404);

        // 3. Update status berdasarkan respon Midtrans
        $transaction = $request->transaction_status;
        $type = $request->payment_type;

        if ($transaction == 'settlement' || $transaction == 'capture') {
            $order->update([
                'payment_status' => 'paid',
                'payment_method' => $type
            ]);
        } elseif ($transaction == 'pending') {
            $order->update(['payment_status' => 'unpaid']);
        } elseif (in_array($transaction, ['deny', 'expire', 'cancel'])) {
            $order->update(['payment_status' => 'failed']);
        }

        return response()->json(['message' => 'Webhook received']);
    }

    public function clearTable($id)
    {
        try {
            \DB::beginTransaction();

            $table = Table::findOrFail($id);

            // 🔥 PERBAIKAN: Ambil SEMUA order yang ada di meja ini
            $activeOrders = Order::where('table_id', $id)
                ->whereIn('order_status', ['pending', 'cooking', 'served'])
                ->get();

            if ($activeOrders->isNotEmpty()) {
                $unfinishedItems = 0;

                // Cek status dapur untuk SEMUA invoice
                foreach ($activeOrders as $order) {
                    $unfinishedItems += $order->details()->whereIn('status', ['pending', 'cooking'])->count();
                }

                if ($unfinishedItems > 0) {
                    \DB::rollback();
                    return response()->json([
                        'success' => false,
                        'error' => 'Tidak bisa mengosongkan meja! Masih ada ' . $unfinishedItems . ' pesanan yang belum diselesaikan oleh Koki.'
                    ], 400);
                }

                // Selesaikan SEMUA invoice
                foreach ($activeOrders as $order) {
                    $order->update(['order_status' => 'completed']);
                }
            }

            $table->update(['status' => 'available']);

            \DB::commit();

            return response()->json(['success' => true, 'message' => 'Meja berhasil dikosongkan dan siap digunakan kembali!']);
        } catch (\Exception $e) {
            \DB::rollback();
            return response()->json(['success' => false, 'error' => $e->getMessage()], 500);
        }
    }

    public function printReceipt($id)
    {
        $order = Order::with(['table', 'details.menu'])->findOrFail($id);
        $setting = Setting::first(); // Ambil profil toko dinamis
        return view('backend.kasir.print', compact('order', 'setting'));
    }
}
