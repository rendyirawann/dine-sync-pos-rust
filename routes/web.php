<?php

use Illuminate\Support\Facades\Route;

// Import Controller Dashboard
use App\Http\Controllers\Backend\Dashboard\DashboardAdminController; // Sesuaikan jika nama controllernya beda
use App\Http\Controllers\Backend\Finance\ExpenseController;
// Import Controller PROFILE
use App\Http\Controllers\Backend\MyProfile\AccountController;
use App\Http\Controllers\Backend\MyProfile\ProfileController;
use App\Http\Controllers\Backend\MyProfile\SecurityController;
use App\Http\Controllers\Backend\MyProfile\ActivityController;
use App\Http\Controllers\Backend\MyProfile\LoginSessionController;

// Import Controller USER MANAGEMENT
use App\Http\Controllers\Backend\UserManagement\UserController;
use App\Http\Controllers\Backend\UserManagement\RoleController;

// Import Controller HELP/LOG
use App\Http\Controllers\Backend\Help\LogActivityController;
use App\Http\Controllers\Backend\Kasir\KasirController;
use App\Http\Controllers\Backend\Kasir\ShiftController;
use App\Http\Controllers\Backend\Kitchen\KitchenController;
use App\Http\Controllers\Backend\Master\CategoriesController;
use App\Http\Controllers\Backend\Master\MenuController;
use App\Http\Controllers\Backend\Master\PromoController;
use App\Http\Controllers\Backend\Master\TableController;
use App\Http\Controllers\Backend\Master\IngredientController;
use App\Http\Controllers\Backend\Master\SupplierController;
use App\Http\Controllers\Backend\Finance\StockController;
use App\Http\Controllers\Backend\Finance\StockOpnameController;
use App\Http\Controllers\Backend\QueueController;
use App\Http\Controllers\Backend\Report\ItemSalesReportController;
use App\Http\Controllers\Backend\Report\SalesReportController;
use App\Http\Controllers\Backend\SettingController;
use App\Http\Controllers\Frontend\CustomerOrderController;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| contains the "web" middleware group. Now create something great!
|
*/

// Halaman Depan (Langsung diarahkan ke Login)
// Halaman Depan (Langsung diarahkan ke Login)
Route::any('/', function () {
    return redirect('/admin/login');
});

Route::any('/dine-sync-pos', function () {
    return redirect('/admin/login');
});



// Rute publik tanpa login
Route::get('/scan/{uuid}', [CustomerOrderController::class, 'scan'])->name('frontend.scan');
Route::post('/scan/{uuid}', [CustomerOrderController::class, 'startOrder'])->name('frontend.scan.post');

// --- KIOSK & TV DISPLAY ANTRIAN ---
Route::get('/kiosk', [QueueController::class, 'kiosk'])->name('frontend.kiosk');
Route::post('/kiosk/take', [QueueController::class, 'takeQueue'])->name('frontend.kiosk.take');
Route::get('/display', [QueueController::class, 'display'])->name('frontend.display');

// 🔥 PERBAIKAN: Ubah rute ini agar mengarah ke fungsi menu() di Controller
Route::get('/menu/{uuid}', [CustomerOrderController::class, 'menu'])->name('frontend.menu');

// 🔥 TAMBAHKAN 2 BARIS INI:
Route::post('/menu/{uuid}/checkout', [CustomerOrderController::class, 'checkout'])->name('frontend.checkout');
Route::get('/order-success/{uuid}', [CustomerOrderController::class, 'success'])->name('frontend.success');


// --- TARUH DEBUG DISINI (DI LUAR MIDDLEWARE AUTH) ---
Route::get('/admin/debug-session', function () {
    $user = auth()->user();

    // Cek manual apakah tabel bans error
    $bannedStatus = 'Tidak dicek';
    $error = null;

    if ($user) {
        try {
            // Kita coba panggil paksa relasi banned-nya
            $bannedStatus = $user->isBanned() ? 'YA TER-BANNED' : 'AMAN';
        } catch (\Exception $e) {
            $bannedStatus = 'ERROR SAAT CEK BANNED: ' . $e->getMessage();
        }
    }

    return [
        'status_login' => $user ? 'SUDAH LOGIN' : 'BELUM LOGIN / SESI HILANG',
        'user_id' => $user?->id,
        'user_name' => $user?->name,
        'session_id' => session()->getId(),
        'driver_session' => config('session.driver'),
        'cek_banned' => $bannedStatus,
    ];
});

// NOTE: Route /login POST dihapus dari sini karena sudah ada di auth.php
// agar tidak bentrok "Route [login] defined twice".

// Group Middleware untuk User yang sudah Login
// Kita tambahkan 'forbid-banned-user' agar user yang di-banned tidak bisa akses
Route::middleware(['auth', 'forbid-banned-user'])->group(function () {

    // --- SHARED ROLE ROUTES (generate-permissions helper, select) ---
    Route::post('/admin/roles/generate-permissions', [RoleController::class, 'generatePermissions'])->name('roles.generate');
    Route::get('/admin/select/role', [RoleController::class, 'select'])->name('role.select');

    // --- DASHBOARD (accessible by ALL authenticated roles) ---
    Route::get('/admin/dashboard', [DashboardAdminController::class, 'index'])->name('dashboard');
    Route::get('/admin/dashboard/get-hpp-details', [DashboardAdminController::class, 'getHppDetails'])->name('dashboard.get-hpp-details');

    // --- MY ACCOUNT / PROFILE (accessible by ALL authenticated users) ---
    Route::get('/admin/my-account', [AccountController::class, 'index'])->name('account.index');
    Route::get('/admin/my-account/{id}/avatar', [AccountController::class, 'editAvatar'])->name('avatar-edit');
    Route::post('/admin/my-account/{id}/update-avatar', [AccountController::class, 'updateAvatar'])->name('avatar-update');

    Route::resource('/admin/my-profile', ProfileController::class);
    Route::resource('/admin/my-security', SecurityController::class);
    Route::post('/admin/my-security', [SecurityController::class, 'store'])->name('change.password');

    Route::get('/admin/my-activity', [ActivityController::class, 'index'])->name('my-activity.index');
    Route::get('/admin/mget-my-activity', [ActivityController::class, 'getActivity'])->name('get-my-activity');

    Route::get('/admin/mmy-login-session', [LoginSessionController::class, 'index'])->name('my-login-session.index');
    Route::get('/admin/mget-my-login-session', [LoginSessionController::class, 'getLoginSession'])->name('get-my-login-session');

    // --- SETTINGS (accessible by ALL authenticated users) ---
    Route::get('/admin/settings', [SettingController::class, 'index'])->name('settings.index');
    Route::post('/admin/settings/update', [SettingController::class, 'update'])->name('settings.update');

    // --- DEBUG/CHECK AUTH ---
    Route::get('/admin/check-auth', function () {
        $u = auth()->user();
        return [
            'user' => $u,
            'roles' => $u?->getRoleNames(),
            'permissions' => $u?->getAllPermissions()->pluck('name'),
        ];
    });
    Route::get('/admin/debug-session', function () {
        $user = auth()->user();
        return ['user' => $user?->name, 'roles' => $user?->getRoleNames()];
    });

    // ====================================================
    // KASIR: view_kasir — Superadmin, admin, kasir
    // ====================================================
    Route::middleware('can:view_kasir')->group(function () {
        Route::get('/admin/shifts', [ShiftController::class, 'index'])->name('shifts.index');
        Route::post('/admin/shifts/open', [ShiftController::class, 'openShift'])->name('shifts.open');
        Route::post('/admin/shifts/close/{id}', [ShiftController::class, 'closeShift'])->name('shifts.close');

        Route::get('/admin/kasir', [KasirController::class, 'index'])->name('kasir.index');
        Route::get('/admin/kasir/table-detail/{id}', [KasirController::class, 'getTableDetail'])->name('kasir.table-detail');
        Route::get('/admin/kasir/order/{table_id}', [KasirController::class, 'createOrder'])->name('kasir.order');
        Route::post('/admin/kasir/order/store', [KasirController::class, 'storeOrder'])->name('kasir.store');
        Route::post('/admin/kasir/pay-existing', [KasirController::class, 'payExistingOrder'])->name('kasir.pay-existing');
        Route::post('/admin/kasir/payment-success', [KasirController::class, 'paymentSuccessLocal'])->name('kasir.payment-success');
        Route::post('/admin/kasir/clear-table/{id}', [KasirController::class, 'clearTable'])->name('kasir.clear-table');
        Route::get('/admin/kasir/print/{id}', [KasirController::class, 'printReceipt'])->name('kasir.print');
    });

    // ====================================================
    // KITCHEN: view_kitchen — Superadmin, admin, kasir, kitchen
    // ====================================================
    Route::middleware('can:view_kitchen')->group(function () {
        Route::get('/admin/kitchen', [KitchenController::class, 'index'])->name('kitchen.index');
        Route::post('/admin/kitchen/update-item', [KitchenController::class, 'updateItemStatus'])->name('kitchen.update-item');
        Route::get('/admin/kitchen/recipe-details/{id}', [KitchenController::class, 'getRecipeDetails'])->name('kitchen.recipe-details');
        Route::post('/admin/kitchen/update-order', [KitchenController::class, 'updateOrderStatus'])->name('kitchen.update-order');
        Route::post('/admin/kitchen/recall', [KitchenController::class, 'recallFood'])->name('kitchen.recall');
    });

    // ====================================================
    // QUEUE: view_queue — All roles
    // ====================================================
    Route::middleware('can:view_queue')->group(function () {
        Route::get('/admin/queues', [QueueController::class, 'index'])->name('queues.index');
        Route::post('/admin/queues/call', [QueueController::class, 'callQueue'])->name('queues.call');
        Route::post('/admin/queues/status/{id}', [QueueController::class, 'updateStatus'])->name('queues.status');
    });

    // ====================================================
    // DATA MASTER: view_data_master — Superadmin, admin
    // ====================================================
    Route::middleware('can:view_data_master')->group(function () {
        Route::resource('/admin/categories', CategoriesController::class);
        Route::get('/admin/get-datacategories', [CategoriesController::class, 'getDataCategories'])->name('get-datacategories');

        Route::resource('/admin/menus', MenuController::class);
        Route::get('/admin/get-datamenus', [MenuController::class, 'getDataMenus'])->name('get-datamenus');

        Route::resource('/admin/tables', TableController::class);
        Route::get('/admin/get-datatables', [TableController::class, 'getDataTables'])->name('get-datatables');
        Route::get('/admin/tables/{uuid}/print-qr', [TableController::class, 'printQr'])->name('tables.print-qr');

        // Promos
        Route::get('/admin/promos/data', [PromoController::class, 'getData'])->name('promos.data');
        Route::post('/admin/promos/toggle/{id}', [PromoController::class, 'toggleStatus'])->name('promos.toggle');
        Route::resource('/admin/promos', PromoController::class)
            ->except(['create', 'show'])
            ->names('promos');
            
        // Ingredients
        Route::resource('/admin/ingredients', IngredientController::class);
        Route::get('/admin/get-dataingredients', [IngredientController::class, 'getData'])->name('get-dataingredients');

        // Suppliers
        Route::resource('/admin/suppliers', SupplierController::class);
        Route::get('/admin/get-datasuppliers', [SupplierController::class, 'getData'])->name('get-datasuppliers');

        // Menu Ingredients (Recipes)
        Route::get('/admin/menus/{id}/ingredients', [MenuController::class, 'ingredients'])->name('menus.ingredients');
        Route::post('/admin/menus/{id}/ingredients', [MenuController::class, 'updateIngredients'])->name('menus.ingredients.update');
    });

    // ====================================================
    // FINANCE: view_finance — Superadmin, admin, kasir
    // ====================================================
    Route::middleware('can:view_finance')->group(function () {
        Route::resource('/admin/expenses', ExpenseController::class);
        Route::get('/admin/get-dataexpenses', [ExpenseController::class, 'getDataExpenses'])->name('get-dataexpenses');
        Route::post('/admin/set-daily-budget', [ExpenseController::class, 'setBudget'])->name('set-daily-budget');
        Route::get('/admin/get-databudgets', [ExpenseController::class, 'getDataBudgets'])->name('get-databudgets');
        
        // Stocks (FIFO Batches)
        Route::resource('/admin/stocks', StockController::class);
        Route::get('/admin/get-datastocks', [StockController::class, 'getData'])->name('get-datastocks');

        // Stock Opname
        Route::get('/admin/stock-opname', [StockOpnameController::class, 'index'])->name('stock-opname.index');
        Route::get('/admin/get-datastock-opname', [StockOpnameController::class, 'getData'])->name('stock-opname.get-data');
        Route::get('/admin/get-history-stock-opname', [StockOpnameController::class, 'getHistoryData'])->name('stock-opname.history-data');
        Route::post('/admin/stock-opname', [StockOpnameController::class, 'store'])->name('stock-opname.store');
        Route::get('/admin/stock-opname/pdf/{id}', [StockOpnameController::class, 'downloadPdf'])->name('stock-opname.pdf');
    });

    // ====================================================
    // REPORTS: view_report — Superadmin, admin, kasir
    // ====================================================
    Route::middleware('can:view_report')->group(function () {
        Route::get('/admin/reports/sales', [SalesReportController::class, 'index'])->name('reports.sales.index');
        Route::get('/admin/reports/sales/data', [SalesReportController::class, 'getData'])->name('reports.sales.data');
        Route::get('/admin/reports/sales/print', [SalesReportController::class, 'print'])->name('reports.sales.print');

        Route::get('/admin/reports/items', [ItemSalesReportController::class, 'index'])->name('reports.items.index');
        Route::get('/admin/reports/items/data', [ItemSalesReportController::class, 'getData'])->name('reports.items.data');
        Route::get('/admin/reports/items/print', [ItemSalesReportController::class, 'print'])->name('reports.items.print');
    });

    // ====================================================
    // RESOURCES (User & Role Mgmt): view_resources — Superadmin only
    // ====================================================
    Route::middleware('can:view_resources')->group(function () {
        Route::resource('/admin/users', UserController::class);
        Route::get('/admin/get-datauser', [UserController::class, 'getDataUsers'])->name('get-users');
        Route::post('/admin/users/mass-delete', [UserController::class, 'massDelete'])->name('users.mass-delete');
        Route::get('/admin/get-user-show-log/{id}', [UserController::class, 'getLoginSession'])->name('get-user-show-log');
        Route::get('/admin/get-user-show-log-activity/{id}', [UserController::class, 'getActivity'])->name('get-user-show-log-activity');
        Route::post('/admin/users/{id}/ban', [UserController::class, 'ban'])->name('users.ban');
        Route::post('/admin/users/{id}/unban', [UserController::class, 'unban'])->name('users.unban');

        Route::resource('/admin/roles', RoleController::class);
        Route::get('/admin/get-datarole', [RoleController::class, 'getDataRoles'])->name('get-datarole');
        Route::post('/admin/roles/mass-delete', [RoleController::class, 'massDelete'])->name('roles.mass-delete');
    });

    // ====================================================
    // HELP (Log Activity): view_help — Superadmin, admin
    // ====================================================
    Route::middleware('can:view_help')->group(function () {
        Route::resource('/admin/log-activity', LogActivityController::class);
        Route::get('/admin/get-datalogactivity', [LogActivityController::class, 'getDataLogActivity'])->name('get-datalogactivity');
    });
});



Route::post('/api/midtrans-webhook', [KasirController::class, 'handleWebhook']);
// Load Routes Authentication (Login, Register, Reset Password)
require __DIR__ . '/auth.php';
