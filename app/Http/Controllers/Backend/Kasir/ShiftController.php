<?php

namespace App\Http\Controllers\Backend\Kasir;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Shift;
use App\Models\Order; // <-- Ganti Sale jadi Order
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use App\Models\DailySalesTarget; // Model Target Penjualan
use App\Models\DailyBudget;      // Model Budget Harian
use App\Models\Expense;          // Model Pengeluaran

class ShiftController extends Controller
{
    public function index()
    {
        $userId = Auth::id();
        $currentShift = Shift::where('user_id', $userId)->where('status', 'open')->first();
        $cashSales = 0;

        if ($currentShift) {
            // PERBAIKAN: Gunakan tabel Order dan kolom grand_total
            $cashSales = Order::where('payment_method', 'cash')
                ->where('payment_status', 'paid')
                ->where('created_at', '>=', $currentShift->start_time)
                ->sum('grand_total');
        }

        $history = Shift::where('user_id', $userId)
            ->where('status', 'closed')
            ->orderBy('id', 'desc')
            ->limit(10)
            ->get();

        // PERBAIKAN: Gunakan kolom 'date' sesuai yang ada di Model/Database
        $today = Carbon::today();
        $isFirstShiftOfDay = !DailySalesTarget::whereDate('date', $today)->exists();

        return view('backend.kasir.shift.index', compact('currentShift', 'cashSales', 'history', 'isFirstShiftOfDay'));
    }

    public function openShift(Request $request)
    {
        $today = Carbon::today();
        $isFirstShiftOfDay = !DailySalesTarget::whereDate('date', $today)->exists();

        // 1. Validasi Dinamis
        $rules = ['starting_cash' => 'required|numeric|min:0'];

        if ($isFirstShiftOfDay) {
            $rules['target_penjualan'] = 'required|numeric|min:0';
            $rules['daily_budget']     = 'required|numeric|min:0';
        }
        $request->validate($rules);

        // 2. Cegah buka shift ganda
        $activeShift = Shift::where('user_id', Auth::id())->where('status', 'open')->first();
        if ($activeShift) {
            return redirect()->back()->with('error', 'Anda masih memiliki shift yang aktif!');
        }

        DB::beginTransaction();
        try {
            // 3. JIKA SHIFT PERTAMA: Simpan Data Global Harian (Perbaikan nama kolom)
            if ($isFirstShiftOfDay) {
                // Simpan Target Penjualan
                DailySalesTarget::create([
                    'date'   => $today,
                    'amount' => $request->target_penjualan,
                ]);

                // Simpan Budget Harian
                DailyBudget::create([
                    'date'   => $today,
                    'amount' => $request->daily_budget,
                ]);
            }

            // 4. Buka Shift untuk Kasir tersebut
            Shift::create([
                'user_id'       => Auth::id(),
                'start_time'    => now(),
                'starting_cash' => $request->starting_cash,
                'status'        => 'open'
            ]);

            DB::commit();
            return redirect()->route('kasir.index')->with('success', 'Shift berhasil dibuka! Selamat bekerja.');
        } catch (\Exception $e) {
            DB::rollback();
            return redirect()->back()->with('error', 'Gagal membuka shift: ' . $e->getMessage());
        }
    }

    // public function closeShift(Request $request, $id)
    // {
    //     $request->validate([
    //         'actual_cash' => 'required|numeric|min:0'
    //     ]);

    //     try {
    //         // 🔥 LOGIKA PENCEGAT: Cek apakah masih ada orderan yang menggantung
    //         // Mencari order yang statusnya masih unpaid (Kuning) 
    //         // ATAU status dapurnya belum completed (Meja Merah yang belum dikosongkan)
    //         $pendingOrders = Order::whereIn('order_status', ['pending', 'cooking', 'served'])
    //             ->orWhere('payment_status', 'unpaid')
    //             ->count();

    //         if ($pendingOrders > 0) {
    //             // Jika masih ada, tendang kembali ke halaman shift dengan pesan error
    //             return redirect()->back()->with('error', 'Akses Ditolak! Masih ada ' . $pendingOrders . ' pesanan yang belum dibayar atau meja yang belum dikosongkan. Harap selesaikan semua meja di menu Kasir terlebih dahulu.');
    //         }

    //         DB::beginTransaction();

    //         $shift = Shift::where('user_id', Auth::id())->where('status', 'open')->findOrFail($id);

    //         // Hitung ulang dari tabel Order
    //         $cashSales = Order::where('payment_method', 'cash')
    //             ->where('payment_status', 'paid')
    //             ->where('created_at', '>=', $shift->start_time)
    //             ->sum('grand_total');

    //         // Kalkulasi
    //         $expectedCash = $shift->starting_cash + $cashSales;
    //         $actualCash   = $request->actual_cash;
    //         $difference   = $actualCash - $expectedCash;

    //         // Tutup Shift
    //         $shift->update([
    //             'end_time'      => Carbon::now(),
    //             'cash_sales'    => $cashSales,
    //             'expected_cash' => $expectedCash,
    //             'actual_cash'   => $actualCash,
    //             'difference'    => $difference,
    //             'status'        => 'closed'
    //         ]);

    //         DB::commit();
    //         return redirect()->route('shifts.index')->with('success', 'Shift berhasil ditutup. Laporan kasir telah disimpan.');
    //     } catch (\Exception $e) {
    //         DB::rollback();
    //         return redirect()->back()->with('error', 'Terjadi kesalahan sistem: ' . $e->getMessage());
    //     }
    // }

    public function closeShift(Request $request, $id)
    {
        $request->validate([
            'actual_cash' => 'required|numeric|min:0'
        ]);

        try {
            // 1. Ambil data shift terlebih dahulu untuk mendapatkan waktu mulai (start_time)
            $shift = Shift::where('user_id', Auth::id())->where('status', 'open')->findOrFail($id);

            // 2. 🔥 PERBAIKAN LOGIKA PENCEGAT: 
            // Hanya cari orderan yang dibuat SELAMA shift ini berlangsung.
            // Gunakan where(function(...)) agar orWhere tidak menabrak filter waktu.
            $pendingOrders = Order::where('created_at', '>=', $shift->start_time)
                ->where(function ($query) {
                    $query->whereIn('order_status', ['pending', 'cooking', 'served'])
                        ->orWhere('payment_status', 'unpaid');
                })
                ->count();

            if ($pendingOrders > 0) {
                return redirect()->back()->with('error', 'Akses Ditolak! Masih ada ' . $pendingOrders . ' pesanan yang belum dibayar atau meja yang belum dikosongkan. Harap selesaikan semua meja di menu Kasir terlebih dahulu.');
            }

            DB::beginTransaction();

            // Hitung ulang dari tabel Order (hanya yang masuk di shift ini)
            $cashSales = Order::where('payment_method', 'cash')
                ->where('payment_status', 'paid')
                ->where('created_at', '>=', $shift->start_time)
                ->sum('grand_total');

            // Kalkulasi
            $expectedCash = $shift->starting_cash + $cashSales;
            $actualCash   = $request->actual_cash;
            $difference   = $actualCash - $expectedCash;

            // Tutup Shift
            $shift->update([
                'end_time'      => Carbon::now(),
                'cash_sales'    => $cashSales,
                'expected_cash' => $expectedCash,
                'actual_cash'   => $actualCash,
                'difference'    => $difference,
                'status'        => 'closed'
            ]);

            DB::commit();
            return redirect()->route('shifts.index')->with('success', 'Shift berhasil ditutup. Laporan kasir telah disimpan.');
        } catch (\Exception $e) {
            DB::rollback();
            return redirect()->back()->with('error', 'Terjadi kesalahan sistem: ' . $e->getMessage());
        }
    }
}
