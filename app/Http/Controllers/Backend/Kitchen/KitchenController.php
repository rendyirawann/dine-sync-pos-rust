<?php

namespace App\Http\Controllers\Backend\Kitchen;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Order;
use App\Models\OrderDetail;
use Illuminate\Support\Facades\DB;
use App\Events\CallQueueEvent;
use Illuminate\Support\Facades\Cache;
use App\Services\StockService;

class KitchenController extends Controller
{
    protected $stockService;

    public function __construct(StockService $stockService)
    {
        $this->stockService = $stockService;
    }

    public function index()
    {
        // Tampilkan SEMUA pesanan yang belum selesai, tanpa filter tanggal.
        // Ini penting agar order dari hari sebelumnya (meja belum dibersihkan) tetap muncul.
        $activeOrders = Order::with(['table', 'details.menu'])
            ->whereIn('order_status', ['pending', 'cooking'])
            ->orderBy('created_at', 'asc')
            ->get();

        // Pesanan Sudah Selesai: tampilkan 3 hari terakhir saja untuk referensi
        $completedOrders = Order::with(['table', 'details.menu'])
            ->whereIn('order_status', ['served', 'completed'])
            ->where('updated_at', '>=', \Carbon\Carbon::now()->subDays(3))
            ->orderBy('updated_at', 'desc')
            ->get();

        return view('backend.kitchen.index', compact('activeOrders', 'completedOrders'));
    }

    public function updateItemStatus(Request $request)
    {
        try {
            DB::beginTransaction();

            $detail = OrderDetail::findOrFail($request->detail_id);
            $detail->update(['status' => $request->status]);

            // 🔥 Manual Batch Selection Support
            $selections = $request->selections ?? []; // [ingredient_id => batch_id]

            if (!$detail->is_stock_deducted && in_array($request->status, ['cooking', 'done'])) {
                $this->stockService->deductMenuStock($detail, $selections);
            }

            $order = Order::findOrFail($detail->order_id);
            // ... (rest of logic remains same)
            $totalItems = $order->details()->count();
            $doneItems = $order->details()->where('status', 'done')->count();
            $cookingItems = $order->details()->where('status', 'cooking')->count();

            $isFinished = false;
            if ($doneItems == $totalItems) {
                $order->update(['order_status' => 'served']);
                $isFinished = true;
            } elseif ($cookingItems > 0 || $doneItems > 0) {
                $order->update(['order_status' => 'cooking']);
            } else {
                $order->update(['order_status' => 'pending']);
            }

            DB::commit();
            return response()->json([
                'success' => true,
                'is_finished' => $isFinished,
                'table_name' => $order->table->table_number ?? 'Walk-in'
            ]);
        } catch (\Exception $e) {
            DB::rollback();
            return response()->json(['success' => false, 'error' => $e->getMessage()], 500);
        }
    }

    public function getRecipeDetails($detail_id)
    {
        $detail = OrderDetail::with(['menu.ingredients.ingredient'])->findOrFail($detail_id);
        $recipes = [];

        foreach ($detail->menu->ingredients as $recipe) {
            $ingredient = $recipe->ingredient;
            $batches = \App\Models\IngredientBatch::where('ingredient_id', $ingredient->id)
                ->where('remaining_quantity', '>', 0)
                ->orderByRaw('expiry_date ASC NULLS LAST')
                ->get()
                ->map(function($b) {
                    $arrival = date('d/m/y', strtotime($b->created_at));
                    $expiry = $b->expiry_date ? date('d/m/y', strtotime($b->expiry_date)) : 'N/A';
                    
                    return [
                        'id' => $b->id,
                        'supplier' => $b->supplier->name ?? 'Manual',
                        'remaining' => $b->remaining_quantity,
                        'expiry' => $expiry,
                        'arrival' => $arrival,
                        'label' => ($b->supplier->name ?? 'Manual') . " (Masuk: $arrival | Exp: $expiry) - Sisa: " . number_format($b->remaining_quantity, 2)
                    ];
                });

            $recipes[] = [
                'ingredient_id' => $ingredient->id,
                'name' => $ingredient->name,
                'needed' => $recipe->quantity * $detail->qty,
                'unit' => $ingredient->unit,
                'batches' => $batches,
                'suggested_batch' => $batches->first()['id'] ?? null
            ];
        }

        return response()->json([
            'menu_name' => $detail->menu->name,
            'qty' => $detail->qty,
            'is_stock_deducted' => $detail->is_stock_deducted,
            'recipes' => $recipes
        ]);
    }


    // FUNGSI BARU: Update semua item di dalam 1 Order sekaligus
    public function updateOrderStatus(Request $request)
    {
        try {
            DB::beginTransaction();

            $order = Order::findOrFail($request->order_id);
            $status = $request->status; // 'cooking' atau 'done'
            $isFinished = false;

            if ($status == 'cooking') {
                // Ubah semua yang 'pending' jadi 'cooking'
                $pendingDetails = $order->details()->where('status', 'pending')->get();
                foreach ($pendingDetails as $detail) {
                    if (!$detail->is_stock_deducted) {
                        $this->stockService->deductMenuStock($detail);
                        $detail->update(['is_stock_deducted' => true]);
                    }
                    $detail->update(['status' => 'cooking']);
                }
                $order->update(['order_status' => 'cooking']);
            } elseif ($status == 'done') {
                $undoneDetails = $order->details()->whereIn('status', ['pending', 'cooking'])->get();
                foreach ($undoneDetails as $detail) {
                    if (!$detail->is_stock_deducted) {
                        $this->stockService->deductMenuStock($detail);
                        $detail->update(['is_stock_deducted' => true]);
                    }
                    $detail->update(['status' => 'done']);
                }
                $order->update(['order_status' => 'served']);
                $isFinished = true;

                // Broadcast ke TV display — dibungkus try-catch agar tidak rollback jika Reverb tidak berjalan
                if (!Cache::has('audio_cooldown')) {
                    try {
                        $textToSpeak = "Pesanan atas nama, " . $order->customer_name . ", sudah siap untuk diambil.";
                        $displayData = [
                            'number' => '#' . explode('-', $order->invoice_no)[1],
                            'name'   => $order->customer_name
                        ];
                        Cache::put('audio_cooldown', true, 15);
                        broadcast(new CallQueueEvent($textToSpeak, $displayData, 'food'));
                    } catch (\Exception $broadcastErr) {
                        // Broadcast gagal (Reverb tidak berjalan) — abaikan, status tetap tersimpan
                        \Log::warning('Kitchen broadcast failed: ' . $broadcastErr->getMessage());
                    }
                }
            }

            DB::commit();
            return response()->json([
                'success' => true,
                'is_finished' => $isFinished,
                'table_name' => $order->table->table_number ?? 'Walk-in'
            ]);
        } catch (\Exception $e) {
            DB::rollback();
            return response()->json(['success' => false, 'error' => $e->getMessage()], 500);
        }
    }

    // 🔥 FUNGSI BARU: Panggil Ulang Makanan
    public function recallFood(Request $request)
    {
        // Cek Cooldown 15 Detik
        if (Cache::has('audio_cooldown')) {
            return response()->json([
                'success' => false,
                'message' => 'Harap tunggu! Sedang ada pemanggilan lain yang berlangsung.'
            ], 429);
        }

        $order = Order::findOrFail($request->order_id);

        try {
            $textToSpeak = "Panggilan ulang. Pesanan atas nama, " . $order->customer_name . ", sudah siap untuk diambil.";
            $displayData = [
                'number' => '#' . explode('-', $order->invoice_no)[1],
                'name'   => $order->customer_name
            ];
            Cache::put('audio_cooldown', true, 15);
            broadcast(new CallQueueEvent($textToSpeak, $displayData, 'food'));
            return response()->json(['success' => true, 'message' => 'Memanggil ulang pesanan ' . $order->customer_name]);
        } catch (\Exception $e) {
            \Log::warning('Recall broadcast failed: ' . $e->getMessage());
            return response()->json(['success' => false, 'message' => 'Gagal memanggil ulang: server broadcast tidak aktif.']);
        }
    }
}
