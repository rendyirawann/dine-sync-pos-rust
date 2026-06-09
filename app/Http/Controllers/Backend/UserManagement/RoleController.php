<?php

namespace App\Http\Controllers\Backend\UserManagement;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;
use DB;
use Illuminate\View\View;
use Illuminate\Http\RedirectResponse;
use Carbon\Carbon;
use Auth;
use Jenssegers\Agent\Agent;

// FIX: Tambahkan ini untuk DataTables
use Yajra\DataTables\Facades\DataTables;

// FIX: Tambahkan interface HasMiddleware dan class Middleware untuk Laravel 12
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;

class RoleController extends Controller implements HasMiddleware
{
    /**
     * IMPLEMENTASI MIDDLEWARE BARU DI LARAVEL 12
     * Menggantikan function __construct() yang lama
     */
    public static function middleware(): array
    {
        return [
            'auth',
            // new Middleware('permission:role.list', only: ['index', 'getDataRoles']),
            // new Middleware('permission:role.show', only: ['show']),
            // new Middleware('permission:role.create', only: ['store']),
            // new Middleware('permission:role.edit', only: ['edit', 'update']),
            // new Middleware('permission:role.delete', only: ['destroy']),
            // new Middleware('permission:role.massdelete', only: ['massDelete']),
        ];
    }

    // Jangan lupa tambahkan ini di paling atas file:
    // use Illuminate\Support\Facades\Artisan;

    public function generatePermissions()
    {
        try {
            // Jalankan command
            \Illuminate\Support\Facades\Artisan::call('permission:generate');

            return response()->json([
                'status' => 'success',
                'message' => 'Permissions berhasil digenerate ulang berdasarkan Route!'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Gagal generate: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request): View
    {
        $permission = Permission::orderBy('category', 'ASC')->get()->groupBy('category');

        return view('backend.user_management.role.index', compact('permission'));
    }

    public function getDataRoles(Request $request, Role $role)
    {
        // $postsQuery = Role::where('id','!=','1')->orderBy('id', 'desc');
        $postsQuery = Role::orderBy('id', 'desc');

        if (!empty($request->search['value'])) {
            $searchValue = $request->search['value'];
            $postsQuery->where(function ($query) use ($searchValue) {
                $query->where('name', 'LIKE', "%{$searchValue}%");
            });
        }

        $data = $postsQuery->select('*');

        return DataTables::of($data)
            ->addColumn('action', function ($data) {
                // === HAPUS PENGECEKAN PERMISSION DISINI AGAR TOMBOL MUNCUL ===

                $x = '<div class="dropdown text-end">
                        <button class="btn btn-sm btn-secondary" type="button" id="dropdownMenuButton2" data-bs-toggle="dropdown" aria-expanded="false">
                            Actions <i class="ki-outline ki-down fs-5 ms-1"></i>
                        </button>
                        <ul class="dropdown-menu dropdown-menu-dark" aria-labelledby="dropdownMenuButton2">';

                // Tombol Detail
                // Tombol Detail (Pakai class 'btn-detail' dan data-id, href jadi javascript:void(0))
                $x .= '<li><a class="dropdown-item btn px-3 btn-detail" href="javascript:void(0)" data-id="' . $data->id . '">Detail</a></li>';

                // Tombol Edit
                $x .= '<li><a class="dropdown-item btn px-3" id="getEditRowData" data-id="' . $data->id . '" >Edit</a></li>';

                // Tombol Hapus
                $x .= '<li><a class="dropdown-item btn px-3" data-id="' . $data->id . '" data-bs-toggle="modal" data-bs-target="#Modal_Hapus_Data" id="getDeleteId">Hapus</a></li>';

                $x .= '</ul></div>';

                return $x;
            })

            ->editColumn('name', function ($data) {
                return '<label class="badge badge-primary">' . $data->name . '</label>';
            })

            ->editColumn('guard_name', function ($data) {
                return '<label class="badge badge-primary">' . $data->guard_name . '</label>';
            })

            ->addColumn('created_at', function ($row) {
                if ($row->created_at) {
                    return '<div class="badge badge-light fw-bold">' .
                        Carbon::parse($row->created_at)->locale('id')->translatedFormat('d F Y, H:i') .
                        '</div>';
                }
                return '<div class="badge badge-light fw-bold">N/A</div>';
            })

            ->addColumn('updated_at', function ($row) {
                if ($row->updated_at) {
                    return '<div class="badge badge-light fw-bold">' .
                        Carbon::parse($row->updated_at)->locale('id')->translatedFormat('d F Y, H:i') .
                        '</div>';
                }
                return '<div class="badge badge-light fw-bold">N/A</div>';
            })

            ->rawColumns(['action', 'name', 'guard_name', 'updated_at', 'created_at'])
            ->make(true);
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
            'name' => 'required|unique:roles,name',
            'permission' => 'required',
        ], [
            'name.unique' => 'Nama Hak Akses sudah terdaftar',
            'name.required' => 'Nama Hak Akses wajib diisi',
            'permission.required' => 'Permission wajib diisi',
        ]);


        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()]);
        }
        // Logika penyimpanan data
        try {
            \DB::beginTransaction();

            $role = Role::create(['name' => $request->input('name')]);
            $role->permissions()->sync($request->input('permission'));




            // ===============================
            // FULL NEW SNAPSHOT
            // ===============================
            $newData = $role->toArray();

            $agent = new Agent;

            activity()
                ->useLog('tambah role')
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
                ->log('Membuat role ' . $role->name);


            \DB::commit();



            return response()->json([
                'success' => ' Data ' . $role->name . ' berhasil disimpan.',
                'time' => $formattedTime,
                'judul' => 'Berhasil'
            ]);
        } catch (\Exception $e) {
            \DB::rollback();
            $errorMessage = $e->getMessage(); // Mendapatkan pesan kesalahan dari Exception
            return response()->json(['error' => 'Terjadi kesalahan di aplikasi, hubungi Developer.', 'time' => $formattedTime, 'judul' => 'Aplikasi Error', 'errorMessage' => $errorMessage]);
        }
    }
    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show(Request $request, $id)
    {
        $role = Role::findOrFail($id);
        $rolePermissions = $role->permissions;

        $allPermissions = Permission::orderBy('category', 'ASC')->get()->groupBy('category');

        // Logic Filter Permission (sama seperti sebelumnya)
        $permissions = collect();
        foreach ($allPermissions as $category => $categoryItems) {
            $filteredPermissions = $categoryItems->filter(function ($item) use ($rolePermissions) {
                return $rolePermissions->contains('id', $item->id);
            });

            if ($filteredPermissions->isNotEmpty()) {
                $permissions[$category] = $filteredPermissions;
            }
        }

        // Jika Request AJAX (dari Modal), return JSON HTML
        if ($request->ajax()) {
            $html = view('backend.user_management.role.show', compact('role', 'rolePermissions', 'permissions'))->render();
            return response()->json(['html' => $html]);
        }

        // Fallback jika diakses langsung via URL (opsional)
        return view('backend.user_management.role.show', compact('role', 'rolePermissions', 'permissions'));
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function edit($id)
    {
        $role = Role::findOrFail($id);

        $html = view(
            'backend.user_management.role.edit',
            [
                'role' => $role->findOrFail($id),
                'permission' => Permission::orderBy('category', 'ASC')->get()->groupBy('category'),
                'rolePermissions' => DB::table("role_has_permissions")->where("role_has_permissions.role_id", $id)
                    ->pluck('role_has_permissions.permission_id', 'role_has_permissions.permission_id')
                    ->all(),

            ]
        )->render();

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
            'name' => 'required|unique:roles,name,' . $id,
            'permission' => 'required|array',
            'permission.*' => 'exists:permissions,id',
        ], [
            'name.unique' => 'Nama Hak Akses sudah terdaftar',
            'name.required' => 'Nama Hak Akses wajib diisi',
            'permission.required' => 'Permission wajib diisi',
            'permission.*.exists' => 'Permission yang dipilih tidak valid',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()]);
        }

        try {
            \DB::beginTransaction();

            // Temukan role berdasarkan ID
            $role = Role::findOrFail($id);
            $oldData = $role->toArray();

            $role->name = $request->input('name');

            // --- FIX: PAKSA UPDATE TIMESTAMP ---
            // Set updated_at ke waktu sekarang agar selalu berubah 
            // (meskipun nama role tidak diganti)
            $role->updated_at = Carbon::now();

            // Update permission dan simpan perubahan
            $role->permissions()->sync($request->input('permission'));

            $role->save();

            // ===============================
            // FULL NEW SNAPSHOT
            // ===============================
            $newData = $role->toArray();

            // ===============================
            // LOG ACTIVITY (AUDIT FULL)
            // ===============================
            $agent = new \Jenssegers\Agent\Agent;

            activity()
                ->useLog('edit role')
                ->causedBy(Auth::user())
                // ->performedOn($role) // Tetap dikomentari agar tidak error UUID
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
                    'old' => $oldData,
                    'new' => $newData,
                ])
                ->log('Mengubah akun user ' . $role->name);

            \DB::commit();

            return response()->json([
                'success' => 'Data ' . $role->name . ' berhasil diperbaharui.',
                'time' => $formattedTime,
                'judul' => 'Berhasil'
            ]);
        } catch (\Exception $e) {
            \DB::rollback();
            $errorMessage = $e->getMessage();
            return response()->json([
                'error' => 'Terjadi kesalahan di aplikasi, hubungi Developer.',
                'time' => $formattedTime,
                'judul' => 'Aplikasi Error',
                'errorMessage' => $errorMessage
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

            // Temukan role berdasarkan ID
            $role = Role::findOrFail($id);
            $getData = $role->toArray();

            // Hapus role
            $role->delete();

            // ===============================
            // LOG ACTIVITY (AUDIT FULL)
            // ===============================
            $agent = new \Jenssegers\Agent\Agent;

            activity()
                ->useLog('hapus role')
                ->causedBy(Auth::user())
                ->performedOn($role)
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
                ->log('Menghapus role ' . $getData['name']);





            \DB::commit();

            return response()->json(['success' => 'Data ' . $role->name . ' berhasil dihapus', 'time' => $formattedTime, 'judul' => 'Berhasil']);
        } catch (\Exception $e) {
            \DB::rollback();
            $errorMessage = $e->getMessage(); // Mendapatkan pesan kesalahan dari Exception
            return response()->json(['error' => 'Data Gagal dihapus', 'time' => $formattedTime, 'judul' => 'Gagal', 'errorMessage' => $errorMessage]);
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
                $data = Role::whereIn('id', $ids)->get();

                // Hapus pengguna
                Role::whereIn('id', $ids)->delete();

                \DB::commit();

                $agent = new \Jenssegers\Agent\Agent;

                // Log activity untuk setiap pengguna yang dihapus
                // FIX: Saya ganti variabel loopnya jadi $role agar tidak menimpa variabel $data (koleksi)
                foreach ($data as $role) {
                    // ===============================
                    // LOG ACTIVITY (AUDIT FULL)
                    // ===============================
                    activity()
                        ->useLog('massdelete role')
                        ->causedBy(Auth::user())
                        ->performedOn($role)
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
                            'get' => $role->toArray(),
                        ])
                        ->log('Menghapus role ' . $role['name']);
                }

                return response()->json([
                    'status' => 'success',
                    'message' => count($ids) . ' data deleted successfully!'
                ]);
            } else {
                return response()->json([
                    'status' => 'error',
                    'message' => 'No data selected for deletion.'
                ]);
            }
        } catch (\Exception $e) {
            \DB::rollback();
            $errorMessage = $e->getMessage(); // Mendapatkan pesan kesalahan dari Exception
            return response()->json(['error' => 'Data Gagal dihapus', 'time' => $formattedTime, 'judul' => 'Gagal', 'errorMessage' => $errorMessage]);
        }
    }


    public function select(Request $request)
    {
        $role = [];

        if ($request->has('q')) {
            $search = $request->q;
            $role = Role::select("id", "name")
                ->Where('name', 'LIKE', "%$search%")
                ->get();
        } else {
            $role = Role::limit(30)->get();
        }
        return response()->json($role);
    }
}
