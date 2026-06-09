<?php

namespace App\Http\Controllers\Backend\Report;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\OrderDetail;
use App\Models\Category;
use App\Models\Setting;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Yajra\DataTables\Facades\DataTables;

class ItemSalesReportController extends Controller
{
    public function index()
    {
        $categories = Category::orderBy('name', 'asc')->get();
        return view('backend.reports.items.index', compact('categories'));
    }

    public function getData(Request $request)
    {
        if ($request->ajax()) {
            // Gabungkan order_details dengan orders, menus, dan categories
            $query = OrderDetail::join('orders', 'order_details.order_id', '=', 'orders.id')
                ->join('menus', 'order_details.menu_id', '=', 'menus.id')
                ->leftJoin('categories', 'menus.category_id', '=', 'categories.id')
                ->where('orders.payment_status', 'paid')
                ->select(
                    'menus.id',
                    'menus.name as menu_name',
                    'menus.discount_percent', // 🔥 TAMBAHAN: Ambil data diskon
                    'categories.name as category_name',
                    DB::raw('SUM(order_details.qty) as total_qty'),
                    DB::raw('SUM(order_details.subtotal) as total_revenue'),
                    DB::raw('SUM(order_details.hpp) as total_hpp')
                )
                ->groupBy('menus.id', 'menus.name', 'menus.discount_percent', 'categories.name');

            // Filter Rentang Tanggal
            if ($request->start_date && $request->end_date) {
                $query->whereBetween('orders.created_at', [$request->start_date . ' 00:00:00', $request->end_date . ' 23:59:59']);
            }

            // Filter Kategori
            if ($request->category_id && $request->category_id != 'all') {
                $query->where('menus.category_id', $request->category_id);
            }

            // Hitung total keseluruhan (Summary) sebelum Pagination DataTables
            $summaryData = (clone $query)->get();
            $totalItemsSold = $summaryData->sum('total_qty');
            $totalRevenue = $summaryData->sum('total_revenue');
            $totalHpp = $summaryData->sum('total_hpp');

            return DataTables::of($query)
                ->addIndexColumn()
                ->addColumn('menu_name', function ($row) {
                    // 🔥 TAMBAHAN: Munculkan badge merah jika menu sedang diskon
                    $badge = $row->discount_percent > 0 ? ' <span class="badge badge-light-danger ms-2 fs-9">Diskon ' . $row->discount_percent . '%</span>' : '';
                    return '<span class="fw-bold text-gray-800">' . $row->menu_name . '</span>' . $badge;
                })
                ->addColumn('category_name', function ($row) {
                    return '<span class="badge badge-light-primary">' . ($row->category_name ?? 'Tanpa Kategori') . '</span>';
                })
                ->addColumn('total_qty', function ($row) {
                    return '<span class="fw-bold text-success fs-5">' . number_format($row->total_qty, 0, ',', '.') . '</span> Porsi';
                })
                ->addColumn('total_hpp', function ($row) {
                    return '<span class="text-gray-600">Rp ' . number_format($row->total_hpp, 0, ',', '.') . '</span>';
                })
                ->addColumn('total_revenue', function ($row) {
                    return '<span class="fw-bold text-gray-800">Rp ' . number_format($row->total_revenue, 0, ',', '.') . '</span>';
                })
                // Data untuk Kotak Summary di atas Tabel
                ->with('totalItemsSold', number_format($totalItemsSold, 0, ',', '.') . ' Porsi')
                ->with('totalRevenue', 'Rp ' . number_format($totalRevenue, 0, ',', '.'))
                ->with('totalHpp', 'Rp ' . number_format($totalHpp, 0, ',', '.'))
                ->rawColumns(['menu_name', 'category_name', 'total_qty', 'total_hpp', 'total_revenue'])
                ->make(true);
        }
    }

    public function print(Request $request)
    {
        $query = OrderDetail::join('orders', 'order_details.order_id', '=', 'orders.id')
            ->join('menus', 'order_details.menu_id', '=', 'menus.id')
            ->leftJoin('categories', 'menus.category_id', '=', 'categories.id')
            ->where('orders.payment_status', 'paid')
            ->select(
                'menus.name as menu_name',
                'menus.discount_percent', // 🔥 TAMBAHAN
                'categories.name as category_name',
                DB::raw('SUM(order_details.qty) as total_qty'),
                DB::raw('SUM(order_details.subtotal) as total_revenue')
            )
            ->groupBy('menus.id', 'menus.name', 'menus.discount_percent', 'categories.name')
            ->orderBy('total_qty', 'desc'); // Urutkan dari yang paling laris

        if ($request->start_date && $request->end_date) {
            $query->whereBetween('orders.created_at', [$request->start_date . ' 00:00:00', $request->end_date . ' 23:59:59']);
        }

        if ($request->category_id && $request->category_id != 'all') {
            $query->where('menus.category_id', $request->category_id);
        }

        $items = $query->get();
        $totalItemsSold = $items->sum('total_qty');
        $totalRevenue = $items->sum('total_revenue');

        $setting = Setting::first();
        $filterDate = Carbon::parse($request->start_date)->translatedFormat('d M Y') . ' - ' . Carbon::parse($request->end_date)->translatedFormat('d M Y');

        $category = 'Semua Kategori';
        if ($request->category_id && $request->category_id != 'all') {
            $catObj = Category::find($request->category_id);
            $category = $catObj ? $catObj->name : 'Semua Kategori';
        }

        return view('backend.reports.items.print', compact('items', 'totalItemsSold', 'totalRevenue', 'setting', 'filterDate', 'category'));
    }
}
