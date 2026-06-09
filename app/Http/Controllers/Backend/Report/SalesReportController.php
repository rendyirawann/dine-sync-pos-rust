<?php

namespace App\Http\Controllers\Backend\Report;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Order;
use App\Models\Setting;
use Carbon\Carbon;
use Yajra\DataTables\Facades\DataTables;

class SalesReportController extends Controller
{
    public function index()
    {
        return view('backend.reports.sales.index');
    }

    public function getData(Request $request)
    {
        if ($request->ajax()) {
            // 🔥 PERBAIKAN: Ambil relasi 'promo' juga
            $query = Order::with(['table', 'promo'])->where('payment_status', 'paid');

            // Filter Rentang Tanggal
            if ($request->start_date && $request->end_date) {
                $query->whereBetween('created_at', [$request->start_date . ' 00:00:00', $request->end_date . ' 23:59:59']);
            }

            // Filter Metode Pembayaran
            if ($request->payment_method && $request->payment_method != 'all') {
                $query->where('payment_method', $request->payment_method);
            }

            // Hitung ringkasan (Summary) 
            $totalRevenue = (clone $query)->sum('grand_total');
            $totalDiscount = (clone $query)->sum('discount_amount'); // Total Uang Promo Terpakai
            $totalOrders = (clone $query)->count();

            // 🔥 TAMBAHAN: Hitung Total HPP
            $totalHpp = \App\Models\OrderDetail::whereHas('order', function($q) use ($request) {
                $q->where('payment_status', 'paid');
                if ($request->start_date && $request->end_date) {
                    $q->whereBetween('created_at', [$request->start_date . ' 00:00:00', $request->end_date . ' 23:59:59']);
                }
            })->sum('hpp');

            // Urutkan dari yang terbaru
            $query->orderBy('created_at', 'desc');

            return DataTables::of($query)
                ->addIndexColumn()
                ->addColumn('date', function ($row) {
                    return Carbon::parse($row->created_at)->translatedFormat('d M Y H:i');
                })
                ->addColumn('invoice', function ($row) {
                    return '<span class="fw-bold text-primary">#' . $row->invoice_no . '</span>';
                })
                ->addColumn('customer', function ($row) {
                    $table = $row->table ? ' (Meja ' . $row->table->table_number . ')' : ' (Walk-in)';
                    return $row->customer_name . '<br><span class="text-muted fs-8">' . $table . '</span>';
                })
                ->addColumn('payment_method', function ($row) {
                    $color = $row->payment_method == 'cash' ? 'success' : 'info';
                    return '<span class="badge badge-light-' . $color . ' text-uppercase">' . $row->payment_method . '</span>';
                })
                // 🔥 TAMBAHAN: Kolom Diskon
                ->addColumn('discount', function ($row) {
                    if ($row->discount_amount > 0) {
                        $promoName = $row->promo ? '<br><span class="badge badge-light-danger fs-9">' . $row->promo->name . '</span>' : '';
                        return '<span class="text-danger fw-bold">- Rp ' . number_format($row->discount_amount, 0, ',', '.') . '</span>' . $promoName;
                    }
                    return '<span class="text-muted">-</span>';
                })
                // 🔥 TAMBAHAN: Kolom HPP
                ->addColumn('total_hpp', function ($row) {
                    $hpp = $row->details->sum('hpp');
                    return '<span class="text-gray-600">Rp ' . number_format($hpp, 0, ',', '.') . '</span>';
                })
                ->addColumn('grand_total', function ($row) {
                    return '<span class="fw-bold text-success fs-5">Rp ' . number_format($row->grand_total, 0, ',', '.') . '</span>';
                })
                // Kirim data tambahan ke frontend
                ->with('totalRevenue', 'Rp ' . number_format($totalRevenue, 0, ',', '.'))
                ->with('totalDiscount', 'Rp ' . number_format($totalDiscount, 0, ',', '.'))
                ->with('totalHpp', 'Rp ' . number_format($totalHpp, 0, ',', '.'))
                ->with('totalOrders', number_format($totalOrders, 0, ',', '.'))
                ->rawColumns(['invoice', 'customer', 'payment_method', 'discount', 'total_hpp', 'grand_total'])
                ->make(true);
        }
    }

    public function print(Request $request)
    {
        // 🔥 PERBAIKAN: Ambil relasi 'promo'
        $query = Order::with(['table', 'promo'])->where('payment_status', 'paid');

        if ($request->start_date && $request->end_date) {
            $query->whereBetween('created_at', [$request->start_date . ' 00:00:00', $request->end_date . ' 23:59:59']);
        }

        if ($request->payment_method && $request->payment_method != 'all') {
            $query->where('payment_method', $request->payment_method);
        }

        $orders = $query->orderBy('created_at', 'asc')->get();
        $totalRevenue = $orders->sum('grand_total');
        $totalDiscount = $orders->sum('discount_amount'); // Kalkulasi diskon untuk print
        $totalOrders = $orders->count();
        $setting = Setting::first();

        $filterDate = Carbon::parse($request->start_date)->translatedFormat('d M Y') . ' - ' . Carbon::parse($request->end_date)->translatedFormat('d M Y');
        $filterPayment = $request->payment_method == 'all' ? 'Semua Metode' : strtoupper($request->payment_method);

        return view('backend.reports.sales.print', compact('orders', 'totalRevenue', 'totalDiscount', 'totalOrders', 'setting', 'filterDate', 'filterPayment'));
    }
}
