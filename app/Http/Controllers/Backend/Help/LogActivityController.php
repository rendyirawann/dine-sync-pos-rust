<?php

namespace App\Http\Controllers\Backend\Help;


use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Spatie\Activitylog\Models\Activity;
use Carbon\Carbon;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;
use Yajra\DataTables\Facades\DataTables;

class LogActivityController extends Controller implements HasMiddleware
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    // function __construct()
    // {
    //     // $this->middleware(['auth']);
    //     // $this->middleware('permission:logactivity-list', ['only' => ['index','getDataLogActivity']]);
    //     // $this->middleware('permission:logactivity-show', ['only' => ['show']]);
    // }
    public static function middleware(): array
    {
        return [
            // 'auth', // Tidak perlu ditulis jika sudah ada di web.php

            // Contoh jika mau pakai permission nanti:
            // new Middleware('permission:logactivity-list', only: ['index', 'getDataLogActivity']),
            // new Middleware('permission:logactivity-show', only: ['show']),
        ];
    }

    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request)
    {
        return view('backend.help.log_activity.index');
    }

    public function getDataLogActivity(Request $request)
    {
        $searchValue = $request->search['value'] ?? null;
        $user = auth()->user(); // Ambil user yang sedang login

        // Query Utama
        $postsQuery = Activity::with('causer')
            ->select('activity_log.*')
            ->orderBy('created_at', 'desc');

        // --- TAMBAHAN LOGIC DI SINI ---
        // Jika user BUKAN 'Superadmin', hanya tampilkan activity milik user tersebut
        if (!$user->hasRole('Superadmin')) {
            $postsQuery->where('causer_id', $user->id);
        }
        // -----------------------------

        // Pencarian Manual
        if (!empty($searchValue)) {
            $postsQuery->where(function ($query) use ($searchValue) {
                $query->where('log_name', 'LIKE', "%{$searchValue}%")
                    ->orWhere('description', 'LIKE', "%{$searchValue}%");
            });
        }

        // Gunakan DataTables
        return DataTables::of($postsQuery)
            ->addIndexColumn()
            ->addColumn('causer_id', function ($data) {
                return $data->causer->name ?? 'System';
            })
            ->addColumn('created_at', function ($data) {
                return Carbon::parse($data->created_at)
                    ->timezone('Asia/Jakarta')
                    ->translatedFormat('d F Y H:i:s');
            })
            // Kolom IP
            ->addColumn('ip', function ($data) {
                $props = $data->properties ?? [];
                return $props['ip'] ?? '-';
            })
            // Kolom OS
            ->addColumn('os', function ($data) {
                $props   = $data->properties ?? [];
                $agent   = $props['agent'] ?? [];
                $os      = $agent['os'] ?? '-';
                $browser = $agent['browser'] ?? '-';
                return $os . ' - ' . $browser;
            })
            // Kolom Device
            ->addColumn('device', function ($data) {
                $props = $data->properties ?? [];
                $agent = $props['agent'] ?? [];

                $isDesktop = $agent['is_desktop'] ?? false;
                $isMobile  = $agent['is_mobile'] ?? false;
                $deviceRaw = $agent['device'] ?? 'Unknown';

                if ($isDesktop) {
                    return '<i class="ki-outline ki-screen text-primary me-2"></i>Desktop';
                }

                if ($isMobile) {
                    return '<i class="ki-outline ki-phone text-warning me-2"></i>Mobile';
                }

                return '<i class="ki-outline ki-question-2 text-danger me-2"></i>' . $deviceRaw;
            })
            ->rawColumns(['causer_id', 'log_name', 'created_at', 'ip', 'os', 'device'])
            ->make(true);
    }

    public function show($id)
    {
        $data = Activity::findOrFail($id);
        return view('backend.help.log_activity.show', compact('data'));
    }
}
