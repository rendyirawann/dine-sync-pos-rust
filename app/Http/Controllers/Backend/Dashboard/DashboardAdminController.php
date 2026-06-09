<?php

namespace App\Http\Controllers\Backend\Dashboard;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Menu;
use App\Models\Order;
use App\Models\OrderDetail;
use App\Models\DailySalesTarget;
use App\Models\Expense;
use App\Models\StockMovement;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Yajra\DataTables\Facades\DataTables;

class DashboardAdminController extends Controller
{
    public function index()
    {
        $monthStart = Carbon::now()->startOfMonth();
        $monthEnd = Carbon::now()->endOfMonth();

        // 1. Menu Tidak Tersedia / Habis (Real-time)
        $unavailableMenus = Menu::with('category')
            ->where('is_available', false)
            ->get();

        // 2. Top Selling Menus (Bulan Ini) - Real-time
        $topProducts = OrderDetail::with(['menu.category'])
            ->whereHas('order', function ($q) use ($monthStart, $monthEnd) {
                $q->whereBetween('created_at', [$monthStart, $monthEnd])
                    ->where('payment_status', 'paid');
            })
            ->select('menu_id', DB::raw('SUM(qty) as total_qty'), DB::raw('SUM(subtotal) as total_revenue'))
            ->groupBy('menu_id')
            ->orderByDesc('total_qty')
            ->limit(5)
            ->get();

        // 3. Data Grafik Penjualan vs Target (Bulan Ini) - Real-time
        // Ambil total penjualan per hari
        $actualSales = Order::whereBetween('created_at', [$monthStart, $monthEnd])
            ->where('payment_status', 'paid')
            ->select(DB::raw('DATE(created_at) as date'), DB::raw('SUM(grand_total) as total'))
            ->groupBy(DB::raw('DATE(created_at)'))
            ->pluck('total', 'date');

        // Ambil target per hari
        $targets = DailySalesTarget::whereBetween('date', [$monthStart->format('Y-m-d'), $monthEnd->format('Y-m-d')])
            ->pluck('amount', 'date');

        $dates = [];
        $salesSeries = [];
        $targetSeries = [];

        // Looping dari tanggal 1 sampai hari ini
        for ($date = $monthStart->copy(); $date->lte(Carbon::now()); $date->addDay()) {
            $dateString = $date->format('Y-m-d');
            $dates[] = $date->format('d M'); 
            $salesSeries[] = (int) $actualSales->get($dateString, 0);
            $targetSeries[] = (int) $targets->get($dateString, 0);
        }

        $chartData = [
            'categories' => $dates,
            'sales'      => $salesSeries,
            'targets'    => $targetSeries,
        ];

        // 4. Quick Summary Widget - Real-time
        $revenue = Order::whereBetween('created_at', [$monthStart, $monthEnd])
            ->where('payment_status', 'paid')
            ->sum('grand_total');

        $totalHpp = OrderDetail::whereHas('order', function ($q) use ($monthStart, $monthEnd) {
            $q->whereBetween('created_at', [$monthStart, $monthEnd])
                ->where('payment_status', 'paid');
        })->sum('hpp');

        $expense = Expense::whereBetween('date', [$monthStart->format('Y-m-d'), $monthEnd->format('Y-m-d')])->sum('amount');

        $itemsSold = OrderDetail::whereHas('order', function ($q) use ($monthStart, $monthEnd) {
            $q->whereBetween('created_at', [$monthStart, $monthEnd])
                ->where('payment_status', 'paid');
        })->sum('qty');

        $summary = [
            'revenue'    => $revenue,
            'hpp'        => $totalHpp,
            'expense'    => $expense,
            'items_sold' => $itemsSold,
            'gross_profit' => $revenue - $totalHpp,
            'net_profit'   => $revenue - $totalHpp - $expense
        ];

        return view('backend.dashboard.index', compact('unavailableMenus', 'topProducts', 'chartData', 'summary'));
    }

    public function getHppDetails(Request $request)
    {
        if ($request->ajax()) {
            $monthStart = Carbon::now()->startOfMonth();
            $monthEnd = Carbon::now()->endOfMonth();

            $query = Order::with(['details.menu'])
                ->where('payment_status', 'paid')
                ->whereBetween('created_at', [$monthStart, $monthEnd])
                ->orderBy('created_at', 'desc');

            return DataTables::of($query)
                ->addIndexColumn()
                ->addColumn('invoice_info', function ($row) {
                    return '<div class="d-flex flex-column">' .
                        '<span class="fw-bold text-primary">#' . $row->invoice_no . '</span>' .
                        '<span class="text-muted fs-8">' . $row->created_at->format('d M Y H:i') . '</span>' .
                        '<span class="badge badge-light-success fs-9 w-50px mt-1">' . strtoupper($row->payment_method) . '</span>' .
                        '</div>';
                })
                ->addColumn('menu_breakdown', function ($row) {
                    $html = '';
                    foreach ($row->details as $detail) {
                        $html .= '<div class="mb-4">';
                        $html .= '<div class="fw-bold text-gray-800 fs-7">' . ($detail->menu->name ?? 'Menu Dihapus') . ' (' . $detail->qty . ' Porsi)</div>';

                        // Ambil rincian bahan baku KHUSUS untuk porsi menu ini di nota ini
                        $breakdown = StockMovement::join('ingredients', 'stock_movements.ingredient_id', '=', 'ingredients.id')
                            ->where('stock_movements.order_detail_id', $detail->id)
                            ->select(
                                'ingredients.name as ing_name',
                                'stock_movements.cost_total'
                            )
                            ->get();

                        if ($breakdown->count() > 0) {
                            $html .= '<div class="mt-1 ps-3 border-start border-gray-300">';
                            foreach ($breakdown as $item) {
                                if ($item->cost_total > 0) {
                                    $html .= '<div class="text-muted fs-8"> - ' . $item->ing_name . ': <span class="text-gray-600 fw-semibold">Rp ' . number_format($item->cost_total, 0, ',', '.') . '</span></div>';
                                }
                            }
                            $html .= '</div>';
                        }
                        $html .= '</div>';
                    }
                    return $html;
                })
                ->addColumn('total_hpp', function ($row) {
                    $totalHpp = $row->details->sum('hpp');
                    return '<span class="fw-bolder text-gray-800">Rp ' . number_format($totalHpp, 0, ',', '.') . '</span>';
                })
                ->rawColumns(['invoice_info', 'menu_breakdown', 'total_hpp'])
                ->make(true);
        }
    }
}