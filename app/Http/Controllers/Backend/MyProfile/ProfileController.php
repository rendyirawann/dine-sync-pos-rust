<?php
    
namespace App\Http\Controllers\Backend\MyProfile;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Models\User;
use Carbon\Carbon;
use DB;
use Auth;

    
class ProfileController extends Controller
{ 
   
    function __construct()
    {
        
    }
    
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        
        return view('backend.my_profile.profile.index');

    }

   

    
public function edit($id)
{
    // Fetch the user with the related karyawan model
    $user = User::findOrFail($id);
    
    // Prepare the view with the user data and selected regions
    $html = view('backend.my_profile.profile.edit', [
        'user' => $user
    ])->render();

    return response()->json(['html' => $html]);
}

    
    

public function update(Request $request, $id)
{
    $formattedTime = Carbon::now()->diffForHumans();

    // ===== VALIDASI =====
   $validator = \Validator::make($request->all(), [
    'name'      => 'required|string|max:100',

    // No WA unik kecuali milik user sendiri
    'no_wa'     => 'required|string|max:20|min:10|unique:users,no_wa,' . $id . ',id',

    // Email unik kecuali milik user sendiri
    'email'     => 'required|email|max:255|unique:users,email,' . $id . ',id',
], [
    // NAME
    'name.required'     => 'Nama lengkap wajib diisi.',
    'name.string'       => 'Nama lengkap harus berupa teks.',
    'name.max'          => 'Nama lengkap maksimal 100 karakter.',

    // NOMOR WHATSAPP
    'no_wa.required'    => 'Nomor WhatsApp wajib diisi.',
    'no_wa.string'      => 'Nomor WhatsApp harus berupa teks.',
    'no_wa.max'         => 'Nomor WhatsApp maksimal 20 karakter.',
    'no_wa.min'         => 'Nomor WhatsApp minimal 10 karakter.',
    'no_wa.unique'      => 'Nomor WhatsApp sudah digunakan oleh pengguna lain.',

    // EMAIL
    'email.required'    => 'Email wajib diisi.',
    'email.email'       => 'Format email tidak valid.',
    'email.max'         => 'Email maksimal 255 karakter.',
    'email.unique'      => 'Email sudah digunakan oleh pengguna lain.',
]);



    if ($validator->fails()) {
        return response()->json(['errors' => $validator->errors()]);
    }

    try {

        DB::beginTransaction();

        // Ambil data user
        $user = User::findOrFail($id);

        // === SNAPSHOT OLD FULL ===
        $oldData = $user->toArray();

        // Update field
        $user->name     = $request->name;
        $user->no_wa    = $request->no_wa;
        $user->email    = $request->email;
        $user->save();

        // === SNAPSHOT NEW FULL ===
        $newData = $user->toArray();

        // Agent Info
        $agent = new \Jenssegers\Agent\Agent;

        // ===== AUDIT TRAIL =====
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
                'old' => $oldData,   // 🔥 FULL OLD SNAPSHOT
                'new' => $newData    // 🔥 FULL NEW SNAPSHOT
            ])
            ->log('Mengubah Data Profile Akun');

        DB::commit();

        return response()->json([
            'success' => 'Data profile berhasil diperbaharui.',
            'time' => $formattedTime,
            'judul' => 'Berhasil',
            'updated' => [
                'name'  => $user->name,
                'no_wa' => $user->no_wa,
                'email' => $user->email,
            ]
        ]);

    } catch (\Exception $e) {

        DB::rollback();

        return response()->json([
            'error' => 'Terjadi kesalahan di aplikasi, hubungi Developer.',
            'time' => $formattedTime,
            'judul' => 'Aplikasi Error',
            'errorMessage' => $e->getMessage(),
        ]);
    }
}


   
    
   
}