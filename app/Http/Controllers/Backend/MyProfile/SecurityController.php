<?php

namespace App\Http\Controllers\Backend\MyProfile;


use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Models\User;
use DB;
use Hash;
use Carbon\Carbon;
use App\Rules\MatchOldPassword;
use Auth;

class SecurityController extends Controller
{
   

    public function index(Request $request)
    {
        $akun = Auth::user();
        return view('backend.my_profile.security.index', compact('akun'));
    }

   public function edit($id)
    {
        $user = User::findOrFail($id);
        $html = view('backend.my_profile.security.edit', compact('user'))->render();

        return response()->json(['html' => $html]);
    }

    public function update(Request $request, $id)
{
    $formattedTime = Carbon::now()->diffForHumans();

    $validator = \Validator::make($request->all(), [
        'email'                 => 'required|email',
        'current_password'      => ['required', new MatchOldPassword],
        'new_password'          => ['required', 'min:8'],
        'new_confirm_password'  => 'required|same:new_password'
    ], [
        'email.required'                => 'Email wajib diisi',
        'current_password.required'     => 'Password terakhir wajib diisi',
        'new_password.required'         => 'Password baru wajib diisi',
        'new_password.min'              => 'Password baru minimal harus 8 karakter',
        'new_confirm_password.required' => 'Konfirmasi password baru wajib diisi',
        'new_confirm_password.same'     => 'Password baru dan konfirmasi tidak sama'
    ]);

    if ($validator->fails()) {
        return response()->json(['errors' => $validator->errors()]);
    }

    try {
        DB::beginTransaction();

        $user = User::findOrFail($id);

        // 📌 Simpan data lama untuk audit
        $old = $user->toArray();

        // 📌 Update email + password
        $user->email = $request->email;
        $user->password = Hash::make($request->new_password);
        $user->save();

        // 📌 Simpan data baru untuk audit
        $new = $user->toArray();

        // ===============================
        // LOG ACTIVITY (SECURITY AUDIT)
        // ===============================
        $agent = new \Jenssegers\Agent\Agent;

        activity()
            ->useLog('security')
            ->causedBy(Auth::user())
            ->performedOn($user)
            ->withProperties([
                'ip' => $request->ip(),
                'agent' => [
                    'browser'    => $agent->browser() . ' ' . $agent->version($agent->browser()),
                    'os'         => $agent->platform() . ' ' . $agent->version($agent->platform()),
                    'device'     => $agent->device(),
                    'is_mobile'  => $agent->isMobile(),
                    'is_desktop' => $agent->isDesktop(),
                    'raw'        => $request->header('User-Agent')
                ],
                'request' => [
                    'method' => $request->method(),
                    'url'    => $request->fullUrl()
                ],
                'old' => $old,                     // 🔥 Data sebelum update
                'new' => $new,                     // 🔥 Data setelah update
                'changes' => [
                    'email_changed'    => $old['email'] !== $new['email'],
                    'password_changed' => true         // password berubah, tdk simpan isinya
                ]
            ])
            ->log('Mengganti Password Akun');

        DB::commit();

        return response()->json([
            'success' => 'Password & Email akun berhasil diperbaharui.',
            'time'    => $formattedTime,
            'judul'   => 'Berhasil'
        ]);

    } catch (\Exception $e) {
        DB::rollback();
        return response()->json([
            'error' => 'Terjadi kesalahan di aplikasi, hubungi Developer.',
            'time' => $formattedTime,
            'judul' => 'Aplikasi Error',
            'errorMessage' => $e->getMessage()
        ]);
    }
}

}
