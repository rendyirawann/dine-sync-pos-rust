<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\View;
use App\Models\DailySalesTarget;
use App\Models\DailyBudget;
use App\Models\Expense;
use App\Models\Order; // Pastikan menggunakan Order (bukan Sale)
use Carbon\Carbon;

use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\URL;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        //
    }

    public function boot(): void
    {
        // Paksa HTTPS di Production/VPS agar tidak terjadi Mixed Content
        if (config('app.env') === 'production') {
            URL::forceRootUrl(config('app.url'));
            URL::forceScheme('https');
        }

        // Implicitly grant "Superadmin" role all permissions
        Gate::before(function ($user, $ability) {
            return $user->hasRole(['Superadmin', 'superadmin']) ? true : null;
        });

        // Inject data HANYA ke view di folder 'backend' (Sidebar Widget)
        // Dan HANYA jika user sudah login (Auth::check)
        View::composer('backend.*', function ($view) {
            if (auth()->check()) {
                $today = date('Y-m-d');

                // 1. Target Penjualan Harian
                $salesTargetObj = DailySalesTarget::where('date', $today)->first();
                $salesTarget = $salesTargetObj ? $salesTargetObj->amount : 0;

                // 2. Omzet Harian (Dari tabel orders yang sudah dibayar)
                $income = Order::whereDate('created_at', $today)
                    ->where('payment_status', 'paid')
                    ->sum('grand_total');

                // 3. Budget & Pengeluaran Harian
                $budgetObj = DailyBudget::where('date', $today)->first();
                $budget = $budgetObj ? $budgetObj->amount : 0;

                $spent = Expense::whereDate('date', $today)->sum('amount');

                // Kalkulasi Persentase Pengeluaran
                $percentage = 0;
                $progressColor = 'bg-primary';
                if ($budget > 0) {
                    $percentage = round(($spent / $budget) * 100);
                    if ($percentage >= 100) {
                        $percentage = 100;
                        $progressColor = 'bg-danger';
                    } elseif ($percentage >= 75) {
                        $progressColor = 'bg-warning';
                    }
                }

                // Kalkulasi Persentase Penjualan vs Target
                $salesPercentage = 0;
                $salesBarWidth = 0;
                $salesProgressColor = 'bg-warning';
                if ($salesTarget > 0) {
                    $salesPercentage = round(($income / $salesTarget) * 100);
                    $salesBarWidth = $salesPercentage > 100 ? 100 : $salesPercentage;
                    if ($salesPercentage >= 100) {
                        $salesProgressColor = 'bg-success';
                    } elseif ($salesPercentage >= 50) {
                        $salesProgressColor = 'bg-primary';
                    }
                }

                $view->with(compact(
                    'salesTarget',
                    'income',
                    'salesPercentage',
                    'salesBarWidth',
                    'salesProgressColor',
                    'budget',
                    'spent',
                    'percentage',
                    'progressColor'
                ));
            }
        });
    }
}
