<?php
    
namespace App\Http\Controllers\Backend\MyProfile;

use App\Http\Controllers\Controller;
    
use Illuminate\Http\Request;
use App\Models\User;
use Spatie\Permission\Models\Role;
use DB;
use Hash;
use Illuminate\Support\Arr;
use Validator;
use Illuminate\Support\Str;
use Illuminate\View\View;
use File;
use Image;
use Illuminate\Support\Facades\Storage;
use Illuminate\Http\RedirectResponse;
use Auth;
use Jenssegers\Agent\Agent;

use DataTables; 
use Carbon\Carbon;
use Ramsey\Uuid\Uuid;

use Spatie\Activitylog\Models\Activity;
use Spatie\Activitylog\Traits\LogsActivity;
    
class AccountController extends Controller
{


    function __construct()
    {
        
    }



    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index(): View
    {
       
  
        return view('backend.my_profile.index');    
    }

   public function editAvatar($id, User $user)
    {
        
        
        $html = view('backend.my_profile.change_pic', 
        [
        'user' => $user->findOrFail($id)
        ])->render();
       
                                                                																						
        return response()->json(['html'=>$html]);
    }


  public function updateAvatar(Request $request, $id)
{
    $formattedTime = Carbon::now()->diffForHumans();

    // ========================
    // VALIDASI
    // ========================
    $validator = \Validator::make($request->all(), [
        'avatar' => 'required|image|mimes:jpg,png,jpeg|max:2048',
    ], [
        'avatar.required' => 'Foto wajib di upload.',
        'avatar.image' => 'File harus berupa gambar.',
        'avatar.mimes' => 'Foto harus berformat JPG atau PNG.',
        'avatar.max' => 'Ukuran file foto maksimal 2MB.',
    ]);

    if ($validator->fails()) {
        return response()->json(['errors' => $validator->errors()]);
    }

    try {

        DB::beginTransaction();

        $user = User::findOrFail($id);

        // ===============================
        // FULL OLD SNAPSHOT
        // ===============================
        $oldData = $user->toArray();

        // ===============================
        // UPDATE AVATAR
        // ===============================
        if ($request->hasFile('avatar')) {

            // Hapus file lama
            if ($user->avatar && Storage::disk('public')->exists('user/avatar/'.$user->avatar)) {
                Storage::disk('public')->delete('user/avatar/'.$user->avatar);
            }

            $file = $request->file('avatar');
            $extension = $file->getClientOriginalExtension();

            // Nama file aman & standar
            $filename = 'avatar-'.$user->id.'-'.time().'.'.$extension;

            // Simpan file
            Storage::disk('public')->putFileAs(
                'user/avatar/',
                $file,
                $filename
            );

            $user->avatar = $filename;
        }

        $user->save();

        // ===============================
        // FULL NEW SNAPSHOT
        // ===============================
        $newData = $user->toArray();

        // ===============================
        // LOG ACTIVITY (AUDIT FULL)
        // ===============================
        $agent = new \Jenssegers\Agent\Agent;

        activity()
            ->useLog('profile')
            ->causedBy(Auth::user())
            ->performedOn($user)
            ->withProperties([
                'ip' => $request->ip(),
                'agent' => [
                    'browser' => $agent->browser() . ' ' . $agent->version($agent->browser()),
                    'os'      => $agent->platform() . ' ' . $agent->version($agent->platform()),
                    'device'  => $agent->device(),
                    'is_mobile' => $agent->isMobile(),
                    'is_desktop' => $agent->isDesktop(),
                    'raw' => $request->header('User-Agent'),
                ],
                'request' => [
                    'method' => $request->method(),
                    'url'    => $request->fullUrl(),
                ],

                // === CATAT SEMUA DATA SEBELUM & SESUDAH ===
                'old' => $oldData,
                'new' => $newData,
            ])
            ->log('Mengganti Avatar');

        DB::commit();

        return response()->json([
            'success' => 'Avatar berhasil diperbaharui.',
            'time' => $formattedTime,
            'judul' => 'Berhasil',
            'avatar_url' => asset('storage/user/avatar/' . $user->avatar)
        ], 201);

    } catch (\Exception $e) {

        DB::rollBack();

        return response()->json([
            'error' => 'Terjadi kesalahan di aplikasi, hubungi Developer.',
            'time' => $formattedTime,
            'judul' => 'Aplikasi Error',
            'errorMessage' => $e->getMessage(),
        ], 500);
    }
}

    

   
}
