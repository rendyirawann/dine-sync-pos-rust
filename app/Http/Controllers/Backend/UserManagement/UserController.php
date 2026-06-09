<?php

namespace App\Http\Controllers\Backend\UserManagement;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Models\User;
// use App\Models\Skpd;
use Spatie\Permission\Models\Role;
use DB;
use Hash;
use Illuminate\Support\Arr;
use Illuminate\View\View;
use Illuminate\Http\RedirectResponse;
use Auth;
use Jenssegers\Agent\Agent;

// FIX: Gunakan Facade DataTables yang benar agar tidak error
use Yajra\DataTables\Facades\DataTables;

use Carbon\Carbon;
use Ramsey\Uuid\Uuid;
use Illuminate\Support\Facades\Storage;

use Spatie\Activitylog\Models\Activity;
use Spatie\Activitylog\Traits\LogsActivity;

// FIX: Tambahkan interface HasMiddleware dan class Middleware untuk Laravel 12
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;

class UserController extends Controller implements HasMiddleware
{
    /**
     * IMPLEMENTASI MIDDLEWARE BARU DI LARAVEL 12
     * Menggantikan function __construct() yang lama
     */
    public static function middleware(): array
    {
        return [
            'auth',
            // new Middleware('permission:user.list', only: ['index', 'getUsers']),
            // new Middleware('permission:user.show', only: ['show']),
            // new Middleware('permission:user.create', only: ['store']),
            // new Middleware('permission:user.edit', only: ['edit', 'update']),
            // new Middleware('permission:user.delete', only: ['destroy']),
            // new Middleware('permission:user.massdelete', only: ['massDelete']),
        ];
    }

    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index(): View
    {
        $roles = Role::orderBy('id', 'desc')
            ->get();
        return view('backend.user_management.user.index', compact('roles'));
    }



    public function getDataUsers(Request $request)
    {
        if ($request->ajax()) {
            $postsQuery = User::with('roles')->orderBy('created_at', 'desc');

            // --- FIX BAGIAN INI ---
            // Gunakan $request->filterrole agar lebih aman dibanding $_GET
            // Pastikan where('name', ...) jika value dropdown adalah NAMA ROLE
            $postsQuery->when($request->filled('filterrole'), function ($query) use ($request) {
                $filterrole = $request->filterrole;
                return $query->whereHas('roles', function ($q) use ($filterrole) {
                    $q->where('name', $filterrole);
                });
            });

            if (!empty($request->search['value'])) {
                $searchValue = $request->search['value'];

                // Pecah kalimat pencarian berdasarkan spasi
                $keywords = explode(' ', $searchValue);

                $postsQuery->where(function ($query) use ($keywords) {
                    foreach ($keywords as $word) {
                        // Gunakan where (AND) untuk setiap kata, 
                        // artinya data harus mengandung SEMUA kata yang diketik
                        $query->where(function ($subQuery) use ($word) {
                            $subQuery->where('name', 'ILIKE', "%{$word}%")
                                ->orWhere('nik', 'ILIKE', "%{$word}%")
                                ->orWhere('email', 'ILIKE', "%{$word}%");
                        });
                    }
                });
            }
            $data = $postsQuery->select('*');

            // FIX: Hapus backslash "\" karena sudah di-use di atas
            return DataTables::of($data)
                ->addIndexColumn()
                ->addColumn('avatar', function ($row) {
                    if ($row->avatar) {

                        // Jika avatar ada, tetapi bukan dari Google (misalnya dari aplikasi Laravel)
                        return ' <div class="d-flex align-items-center">
                                        <div class="symbol symbol-45px me-5">

                                                <img src="' . asset('storage/user/avatar/' . $row->avatar) . '" alt="' . $row->name . '"  />

                                        </div>
                                        <div class="d-flex flex-column">
                                            <a href="' . route('users.show', $row->id) . '" class="text-gray-800 text-hover-primary mb-1">' . $row->name . '</a>
                                            <span>' . $row->email . '</span>

                                        </div>
                                    </div>';
                    } else {
                        // Jika avatar kosong, tampilkan huruf pertama dari nama pengguna
                        $initial = strtoupper(substr($row->name, 0, 1));
                        return '<div class="d-flex align-items-center">
                                    <div class="symbol symbol-circle symbol-50px overflow-hidden me-3">
                                        <div class="symbol-label fs-3 bg-light-primary text-primary">' . $initial . '</div>
                                    </div>
                                    <div class="d-flex flex-column">
                                        <a href="' . route('users.show', $row->id) . '" class="text-gray-800 text-hover-primary mb-1">' . $row->name . '</a>
                                        <span>' . $row->email . '</span>
                                    </div>
                                </div>';
                    }
                })

                ->addColumn('roles', function ($row) {
                    $roleNames = $row->getRoleNames(); // Ambil semua role dari user

                    // Cek apakah user memiliki roles
                    if ($roleNames->isEmpty()) {
                        return 'no roles assigned'; // Teks default jika tidak ada role
                    }

                    // Jika ada roles, gabungkan menjadi string yang dipisahkan koma
                    return implode(', ', $roleNames->toArray());
                })

                ->addColumn('last_login_at', function ($row) {
                    if ($row->last_login) {
                        $formattedTime = Carbon::parse($row->last_login)->diffForHumans();
                        return '<div class="badge badge-light fw-bold">' . $formattedTime . '</div>';
                    } else {
                        return '<div class="badge badge-light fw-bold">Never logged in</div>';
                    }
                })
                ->addColumn('last_login_ip', function ($row) {
                    return '<div class="badge badge-light fw-bold">' . ($row->last_ip ?: 'N/A') . '</div>';
                })
                ->addColumn('joined_date', function ($row) {
                    if ($row->created_at) {
                        $formattedTime = Carbon::parse($row->created_at)
                            ->locale('id')  // Set locale to Indonesian
                            ->translatedFormat('d F Y, H:i');
                        return '<div class="badge badge-light fw-bold">' . $formattedTime . '</div>';
                    } else {
                        return '<div class="badge badge-light fw-bold">N/A</div>';
                    }
                })
                ->addColumn('action', function ($row) {
                    // Ambil user yang sedang login
                    $user = auth()->user();

                    $x = '<div class="dropdown text-end">
                            <button class="btn btn-sm btn-secondary" type="button" data-bs-toggle="dropdown" aria-expanded="false">
                                Actions <i class="ki-outline ki-down fs-5 ms-1"></i>
                            </button>
                            <ul class="dropdown-menu dropdown-menu-dark fs-6">';

                    // 1. Cek Permission Show
                    if ($user->username == 'superadmin') {
                        $x .= '<li><a class="dropdown-item btn px-3 btn-detail" href="javascript:void(0)" data-id="' . $row->id . '" >Detail</a></li>';
                    }

                    // 2. Cek Permission Edit
                    if ($user->username == 'superadmin') {
                        $x .= '<li><a class="dropdown-item btn px-3 btn-edit" href="javascript:void(0)" data-id="' . $row->id . '" >Edit</a></li>';
                    }

                    // 3. Cek Permission Delete
                    if ($user->username == 'superadmin') {
                        $x .= '<li><a class="dropdown-item btn px-3" data-id="' . $row->id . '" data-bs-toggle="modal" data-bs-target="#Modal_Hapus_Data" id="getDeleteId">Hapus</a></li>';
                    }

                    // 4. Cek Permission Ban
                    if ($user->username == 'superadmin') {
                        if ($row->isBanned()) {
                            $x .= '<li><a class="dropdown-item px-3 text-success" href="javascript:void(0)" onclick="unbanUser(\'' . $row->id . '\')">Unbanned</a></li>';
                        } else {
                            $x .= '<li><a class="dropdown-item px-3 text-danger" href="javascript:void(0)" onclick="openBanModal(\'' . $row->id . '\')">Banned</a></li>';
                        }
                    }

                    $x .= '</ul></div>';

                    return $x;
                })


                ->addColumn('status', function ($row) {

                    if ($row->isBanned()) {
                        // Ambil ban terakhir
                        $ban = $row->bans()->latest()->first();

                        // Ban permanent
                        if (!$ban->expired_at) {
                            return '<span class="badge bg-danger">Banned</span>';
                        }

                        // Ban sementara
                        $expire = \Carbon\Carbon::parse($ban->expired_at)
                            ->timezone('Asia/Jakarta')
                            ->format('d M Y H:i');

                        // Jika sudah lewat → expired
                        if (now()->greaterThan($ban->expired_at)) {
                            return '<span class="badge bg-warning text-dark">Ban Expired</span>';
                        }

                        // Ban masih aktif (temporary)
                        return '<span class="badge bg-warning text-dark">Suspended until ' . $expire . '</span>';
                    }

                    return '<span class="badge bg-success">Active</span>';
                })





                ->rawColumns(['avatar', 'roles', 'last_login_at', 'last_login_ip', 'joined_date', 'action', 'status'])
                ->make(true);
        }
    }




    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */


    public function store(Request $request)
    {
        $formattedTime = Carbon::now()->diffForHumans();

        $validator = \Validator::make($request->all(), [
            'name'     => 'required|string|max:255',
            'username' => 'required|string|max:100|unique:users,username|alpha_dash',
            'no_wa'    => 'required|string|max:20|min:10|unique:users,no_wa',
            'email'    => 'required|string|email|max:255|unique:users,email',
            'password' => 'required|string|min:8|confirmed',
            'avatar'   => 'nullable|mimes:jpg,png,svg|max:2048',
            'roles'    => 'required',
        ], [
            'name.required'    => 'Nama Lengkap wajib diisi',
            'name.max'         => 'Nama Lengkap maksimal 255 karakter',

            'username.required'  => 'Username wajib diisi.',
            'username.unique'    => 'Username sudah digunakan, pilih username lain.',
            'username.alpha_dash' => 'Username hanya boleh huruf, angka, strip (-) dan underscore (_).',
            'username.max'       => 'Username maksimal 100 karakter.',

            'no_wa.required'    => 'Nomor WhatsApp wajib diisi.',
            'no_wa.string'      => 'Nomor WhatsApp harus berupa teks.',
            'no_wa.max'         => 'Nomor WhatsApp maksimal 20 karakter.',
            'no_wa.min'         => 'Nomor WhatsApp minimal 10 karakter.',
            'no_wa.unique'      => 'Nomor WhatsApp sudah digunakan oleh pengguna lain.',

            'email.required' => 'Email wajib diisi',
            'email.email'    => 'Format Email tidak valid',
            'email.unique'   => 'Email sudah terdaftar',

            'password.required'  => 'Password wajib diisi',
            'password.min'       => 'Kata Sandi minimal 8 karakter',
            'password.confirmed' => 'Kata Sandi tidak sama',

            'avatar.mimes' => 'Avatar harus format .jpg .png .svg',
            'avatar.max'   => 'Ukuran file Avatar maksimal 2 MB',

            'roles.required' => 'Role wajib diisi',
        ]);


        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()]);
        }



        // Logika penyimpanan data
        try {
            \DB::beginTransaction();

            $data = new User;
            // ===============================
            // SIMPAN AVATAR (PAKAI POLA SAMA)
            // ===============================
            if ($request->hasFile('avatar')) {
                $file      = $request->file('avatar');
                $extension = $file->getClientOriginalExtension();

                $filename = 'avatar-' . $data->id . '-' . time() . '.' . $extension;

                Storage::disk('public')->putFileAs(
                    'user/avatar/',
                    $file,
                    $filename
                );

                $data->avatar = $filename;
            }

            $data->id       = Uuid::uuid4();
            $data->name     = $request->name;
            $data->username = $request->username;
            $data->no_wa    = $request->no_wa;
            $data->email    = $request->email;
            $data->password = Hash::make($request->password);
            $data->assignRole($request->input('roles'));


            $data->save();


            // ===============================
            // FULL NEW SNAPSHOT
            // ===============================
            $newData = $data->toArray();

            $agent = new Agent;

            activity()
                ->useLog('tambah user')
                ->causedBy(auth()->user())
                ->withProperties([
                    'ip' => $request->ip(),
                    'agent' => [
                        'browser' => $agent->browser() . ' ' . $agent->version($agent->browser()),
                        'os' => $agent->platform() . ' ' . $agent->version($agent->platform()),
                        'device' => $agent->device(),
                        'is_mobile' => $agent->isMobile(),
                        'is_desktop' => $agent->isDesktop(),
                        'raw' => $request->header('User-Agent'),
                    ],
                    'request' => [
                        'method' => $request->method(),
                        'url' => $request->fullUrl(),
                    ],
                    'new' => $newData,
                ])
                ->log('Membuat akun user ' . $data->name);

            \DB::commit();

            return response()->json([
                'success' => 'Data berhasil disimpan.',
                'time' => $formattedTime,
                'judul' => 'Berhasil'
            ], 201);
        } catch (\Exception $e) {
            \DB::rollback();
            $errorMessage = $e->getMessage(); // Mendapatkan pesan kesalahan dari Exception
            return response()->json([
                'error' => 'Terjadi kesalahan di aplikasi, hubungi Developer.',
                'time' => $formattedTime,
                'judul' => 'Aplikasi Error',
                'errorMessage' => $errorMessage
            ], 500);
        }
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */


    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show(Request $request, $id)
    {
        $data = User::findOrFail($id);

        // 1. Hitung Total Activity
        $totalActivity = \Spatie\Activitylog\Models\Activity::where('causer_type', 'App\Models\User')
            ->where('causer_id', $id)
            ->count();

        // 2. Hitung Total Login (FIXED - AMAN)
        $totalLogin = 0;

        // Cek apakah tabel 'authentication_log' ada di database
        if (\Illuminate\Support\Facades\Schema::hasTable('authentication_log')) {
            $totalLogin = \DB::table('authentication_log')
                ->where('authenticatable_id', $id)
                ->where('authenticatable_type', 'App\Models\User')
                ->count();
        } else {
            // ALTERNATIF: Jika tabel login khusus tidak ada,
            // Coba hitung dari activity log yang deskripsinya mengandung kata 'login'
            // (Jika belum ada log login sama sekali, ini akan mereturn 0, jadi aman)
            $totalLogin = \Spatie\Activitylog\Models\Activity::where('causer_type', 'App\Models\User')
                ->where('causer_id', $id)
                ->where(function ($q) {
                    $q->where('description', 'login')
                        ->orWhere('description', 'logged in')
                        ->orWhere('log_name', 'login');
                })
                ->count();
        }

        if ($request->ajax()) {
            $html = view('backend.user_management.user.show', compact('data', 'totalActivity', 'totalLogin'))->render();
            return response()->json(['html' => $html]);
        }

        return view('backend.user_management.user.show', compact('data', 'totalActivity', 'totalLogin'));
    }

    public function getLoginSession(Request $request, $id)
    {
        $postsQuery = Activity::with('causer')
            ->where('causer_id', $id)
            ->whereIn('log_name', ['login', 'logout'])
            ->orderBy('id', 'desc');

        $data = $postsQuery->get();


        // FIX: Hapus backslash "\"
        return DataTables::of($data)

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

    public function getActivity(Request $request, $id)
    {

        $postsQuery = Activity::with('causer')
            ->where('causer_id', $id)
            ->whereNotIn('log_name', ['login', 'logout']) // Tambahkan klausa whereNotIn untuk log_name
            ->orderBy('id', 'desc');

        $data = $postsQuery->get();

        // FIX: Hapus backslash "\"
        return DataTables::of($data)

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

    /**
     * Show the form for editing the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function edit($id)
    {
        $user = User::findOrFail($id);
        // $skpd = Skpd::orderBy('nama_skpd')->get();

        // Kirim data ke view untuk di-render
        $html = view('backend.user_management.user.edit', [
            'user' => $user,
            // 'skpd' => $skpd,
            'userRole' => $user->getRoleNames()->toArray(),
            'roles' => Role::where('guard_name', '=', 'web')->select(['id', 'name'])->get(),
        ])->render();

        return response()->json(['html' => $html]);
    }



    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, $id)
    {
        $formattedTime = Carbon::now()->diffForHumans();

        $validator = \Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'email' => 'required|email|max:255|unique:users,email,' . $id,
            'password' => 'confirmed',
            'avatar' => 'nullable|mimes:jpg,png,svg|max:2048',
            'roles' => 'required',
            // 'skpd_id' => 'required',
        ], [
            'name.required' => 'Nama Lengkap wajib diisi',
            'name.max' => 'Nama Lengkap maksimal 255 karakter',
            'email.required' => 'Email wajib diisi',
            'email.email' => 'Format Email tidak valid',
            'email.unique' => 'Email sudah terdaftar',
            'password.confirmed' => 'Kata Sandi tidak sama',
            'avatar.mimes' => 'Avatar harus format .jpg .png .svg',
            'avatar.max' => 'Ukuran file Avatar maksimal 2 MB',
            'roles.required' => 'Role wajib diisi',
            // 'skpd_id.required' => 'Skpd wajib diisi',

        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()]);
        }

        try {
            \DB::beginTransaction();

            $data = User::findOrFail($id);
            $oldData = $data->toArray();

            if ($request->hasFile('avatar')) {

                // Hapus file lama
                if ($data->avatar && Storage::disk('public')->exists('user/avatar/' . $data->avatar)) {
                    Storage::disk('public')->delete('user/avatar/' . $data->avatar);
                }

                $file = $request->file('avatar');
                $extension = $file->getClientOriginalExtension();

                // Nama file aman & standar
                $filename = 'avatar-' . $data->id . '-' . time() . '.' . $extension;

                // Simpan file
                Storage::disk('public')->putFileAs(
                    'user/avatar/',
                    $file,
                    $filename
                );

                $data->avatar = $filename;
            }

            $data->name = $request->name;
            $data->email = $request->email;
            if (!empty($request->password)) {
                $data->password = Hash::make($request->password);
            }
            // $data->skpd_id = $request->skpd_id;


            $data->save();

            // Sync roles
            DB::table('model_has_roles')->where('model_id', $id)->delete();
            $data->assignRole($request->input('roles'));

            // ===============================
            // FULL NEW SNAPSHOT
            // ===============================
            $newData = $data->toArray();

            // ===============================
            // LOG ACTIVITY (AUDIT FULL)
            // ===============================
            $agent = new \Jenssegers\Agent\Agent;

            activity()
                ->useLog('edit user')
                ->causedBy(Auth::user())
                ->performedOn($data)
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
                ->log('Mengubah akun user ' . $data->name);





            \DB::commit();

            return response()->json([
                'success' => 'Data berhasil diperbaharui.',
                'time' => $formattedTime,
                'judul' => 'Berhasil',
            ]);
        } catch (\Exception $e) {
            \DB::rollback();
            $errorMessage = $e->getMessage();
            return response()->json([
                'error' => 'Terjadi kesalahan di aplikasi, hubungi Developer.',
                'time' => $formattedTime,
                'judul' => 'Aplikasi Error',
                'errorMessage' => $errorMessage,
            ]);
        }
    }


    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy(Request $request, $id)
    {
        $formattedTime = Carbon::now()->diffForHumans();

        try {
            \DB::beginTransaction();

            $data = User::findOrFail($id);
            $getData = $data->toArray();

            // ===============================
            // HAPUS AVATAR JIKA ADA
            // ===============================
            if ($data->avatar) {
                $avatarPath = 'user/avatar/' . $data->avatar;

                if (Storage::disk('public')->exists($avatarPath)) {
                    Storage::disk('public')->delete($avatarPath);
                }
            }

            // ===============================
            // HAPUS USER
            // ===============================
            $data->delete();

            \DB::commit();

            // ===============================
            // LOG ACTIVITY (AUDIT FULL)
            // ===============================
            $agent = new \Jenssegers\Agent\Agent;

            activity()
                ->useLog('hapus user')
                ->causedBy(Auth::user())
                ->performedOn($data)
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
                    'get' => $getData,
                ])
                ->log('Menghapus akun user ' . $getData['name']);

            return response()->json([
                'success' => 'Data berhasil dihapus',
                'time' => $formattedTime,
                'judul' => 'Berhasil'
            ]);
        } catch (\Exception $e) {

            \DB::rollback();

            return response()->json([
                'error'        => 'Data Gagal dihapus',
                'time'         => $formattedTime,
                'judul'        => 'Gagal',
                'errorMessage' => $e->getMessage()
            ]);
        }
    }


    public function massDelete(Request $request)
    {
        $formattedTime = Carbon::now()->diffForHumans();
        try {
            \DB::beginTransaction();

            $ids = $request->ids;

            if (!empty($ids)) {
                // Dapatkan data pengguna yang akan dihapus untuk logging
                $users = User::whereIn('id', $ids)->get();

                // Hapus avatar masing-masing
                foreach ($users as $user) {
                    if ($user->avatar) {

                        $avatarPath = 'user/avatar/' . $user->avatar;

                        if (Storage::disk('public')->exists($avatarPath)) {
                            Storage::disk('public')->delete($avatarPath);
                        }
                    }
                }

                // Hapus pengguna
                User::whereIn('id', $ids)->delete();

                \DB::commit();

                $agent = new \Jenssegers\Agent\Agent;

                // Log activity untuk setiap pengguna yang dihapus
                foreach ($users as $user) {
                    // ===============================
                    // LOG ACTIVITY (AUDIT FULL)
                    // ===============================
                    activity()
                        ->useLog('massdelete user')
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
                            'get' => $user->toArray(),
                        ])
                        ->log('Menghapus akun user ' . $user['name']);
                }

                return response()->json([
                    'status' => 'success',
                    'message' => count($ids) . ' users deleted successfully!'
                ]);
            } else {
                return response()->json([
                    'status' => 'error',
                    'message' => 'No users selected for deletion.'
                ]);
            }
        } catch (\Exception $e) {
            \DB::rollback();
            $errorMessage = $e->getMessage(); // Mendapatkan pesan kesalahan dari Exception
            return response()->json(['error' => 'Data Gagal dihapus', 'time' => $formattedTime, 'judul' => 'Gagal', 'errorMessage' => $errorMessage]);
        }
    }

    public function ban(Request $request, $id)
    {
        $request->validate([
            'reason' => 'required|string|max:500',
            'duration' => 'required|in:permanent,1h,24h,1w',
        ]);

        $user = User::findOrFail($id);

        // Cegah ban diri sendiri
        if ($user->id === auth()->id()) {
            return response()->json(['error' => 'Anda tidak dapat memban akun Anda sendiri.'], 422);
        }

        // Cegah ban ganda
        if ($user->isBanned()) {
            return response()->json(['error' => 'User sudah diban sebelumnya.'], 422);
        }

        // Durasi ban
        $expired = match ($request->duration) {
            '1h' => now()->addHour(),
            '24h' => now()->addDay(),
            '1w' => now()->addWeek(),
            default => null, // permanent
        };

        // Ban user
        $user->ban([
            'comment' => $request->reason,
            'expired_at' => $expired
        ]);

        // Log activity
        activity('ban user')
            ->causedBy(auth()->user())
            ->performedOn($user)
            ->withProperties([
                'reason'     => $request->reason,
                'duration'   => $request->duration,
                'expired_at' => $expired,
                'ip'         => $request->ip(),
            ])
            ->log('Membanned user: ' . $user->name);

        return response()->json(['success' => 'User berhasil diban']);
    }


    public function unban(Request $request, $id)
    {
        $user = User::findOrFail($id);

        // Cegah unban jika user tidak dibanned
        if ($user->isNotBanned()) {
            return response()->json(['error' => 'User tidak dalam status banned.'], 422);
        }

        $user->unban();

        activity('unban user')
            ->causedBy(auth()->user())
            ->performedOn($user)
            ->withProperties([
                'ip' => $request->ip()
            ])
            ->log('Mengaktifkan kembali user: ' . $user->name);

        return response()->json(['success' => 'User berhasil diaktifkan kembali']);
    }
}
