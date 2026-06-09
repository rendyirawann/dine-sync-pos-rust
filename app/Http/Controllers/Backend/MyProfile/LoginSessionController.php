<?php
    
    namespace App\Http\Controllers\Backend\MyProfile;


use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Spatie\Activitylog\Models\Activity;
use Carbon\Carbon;
use Auth;
    
class LoginSessionController extends Controller
{
    
    
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request)
    {
        return view('backend.my_profile.login_session.index');
    }

    public function getLoginSession(Request $request)
{
    $userId = Auth::id();

    $data = Activity::with('causer')
        ->where('causer_id', $userId)
        ->whereIn('log_name', ['login', 'logout'])
        ->orderBy('created_at', 'desc')
        ->get();

    return datatables()->of($data)

        // =============================
        // CREATED_AT
        // =============================
        ->addColumn('created_at', function ($data) {
            if (empty($data->created_at)) {
                return '<div class="text-end"><label class="badge badge-warning">Belum Pernah Login</label></div>';
            }
            return '<div class="text-end"><label class="badge badge-info">'
                . $data->created_at->diffForHumans() .
                '</label></div>';
        })

        // =============================
        // DESCRIPTION
        // =============================
        ->addColumn('description', function ($data) {
            return $data->description ?? '-';
        })

        // =============================
        // IP + AGENT (AMAN)
        // =============================
        ->addColumn('ip', function ($data) {
            $ip     = $data->properties['ip'] ?? '-';
            $browser = $data->properties['agent']['browser'] ?? '-';

            //return $ip . ' | ' . $browser;
            return $ip;
        })

        // =============================
        // OS + BROWSER (AMAN)
        // =============================
        ->addColumn('os', function ($data) {
            $os      = $data->properties['agent']['os'] ?? '-';
            $browser = $data->properties['agent']['browser'] ?? '-';

            return $os . ' - ' . $browser;
        })

        // =============================
        // DEVICE (AMAN)
        // =============================
        ->addColumn('device', function ($data) {
        $agent = $data->properties['agent'] ?? [];

        $isDesktop = $agent['is_desktop'] ?? false;
        $isMobile  = $agent['is_mobile'] ?? false;
        $deviceRaw = $agent['device'] ?? 'Unknown';

        if ($isDesktop) {
            return '<i class="ki-outline ki-screen text-primary me-2"></i>Desktop';
        }

        if ($isMobile) {
            return '<i class="ki-outline ki-phone text-warning me-2"></i>Mobile';
        }

        // Jika bukan desktop dan bukan mobile → unknown
        return '<i class="ki-outline ki-question-2 text-danger me-2"></i>' . $deviceRaw;
    })


        ->rawColumns(['created_at', 'description', 'ip', 'os', 'device'])
        ->make(true);
}


  
    
}