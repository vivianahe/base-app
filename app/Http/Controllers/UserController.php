<?php

namespace App\Http\Controllers;

use App\Models\Role;
use App\Models\User;
use Illuminate\Http\Request;
use App\Models\AccessHistory;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;

class UserController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $query = User::join('model_has_roles', 'model_has_roles.model_id', '=', 'users.id')
            ->join('roles', 'roles.id', '=', 'model_has_roles.role_id')
            ->select(
                'users.name',
                'users.email',
                'roles.name as rol',
                'users.id',
                'users.deleted_at',
                'users.created_at'
            )->withTrashed()->orderBy('users.created_at', 'DESC')->get();
        return response()->json($query);
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $userExists = User::where('email', $request->user['email'])->exists();

        if (!$userExists) {
            $user = new User();
            // Cambio en la búsqueda del rol
            $role = Role::where('id', $request->user['rol_id'])->first();
            $user->name = $request->user['name'];
            $user->email = $request->user['email'];
            $user->password = bcrypt($request->user['password']);
            $user->save();
            $user->assignRole($role->id);
            return response()->json(['message' => 'Ok'], 201);
        } else {
            return response()->json(['message' => 'Ya existe un correo: '], 201);
        }
    }


    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(string $id)
    {
        $user = User::where('id', $id)->withTrashed()->first();
        $userRole = $user->roles()->first();
        return response()->json([
            'success' => true,
            'data' => [
                'user' => $user,
                'roles' => $userRole
            ]
        ], 200);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request)
    {
        $existingEmail = User::where('email', $request->email)
            ->where('id', '!=', $request->id)
            ->withTrashed()->exists();
        if ($existingEmail) {
            return response()->json(['message' => 'Ya existe un usuario con email: ']);
        } else {
            User::where('id', $request->id)->update([
                'name' => $request->name,
                'email' => $request->email,
                'password' => $request->password !== null ? bcrypt($request->password) : DB::raw('`password`')
            ]);
            DB::table('model_has_roles')->where('model_id', $request->id)->update([
                'role_id' => $request->rol_id
            ]);
            return response()->json(['message' => 'Ok'], 200);
        }
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        $user = User::withTrashed()->find($id);

        if ($user->photo) {
            $imagePath = public_path('support/userProfile') . '/' . $user->photo;
            if (file_exists($imagePath)) {
                unlink($imagePath);
            }
        }

        $user->roles()->detach();
        $user->forceDelete();
    }

    public function getAccessHistory(Request $request, $id)
    {
        $accessHistory =  AccessHistory::where('user_id', $request->id)->orderBy('created_at', 'DESC')->get();
        return response()->json(['success' => true, 'data' => $accessHistory], 200);
    }
    public function updateState(Request $request)
    {
        $user = User::withTrashed()->where('id', $request->id)->first();
        $state = '';
        if ($request->state == 'activo') {
            $state = 'inactivo';
            $user->delete();
        } else {
            $state = 'activo';
            $user->restore();
        }
        return response()->json($state);
    }
    public function getRol()
    {
        $sql = Role::get();
        return response()->json($sql);
    }
    public function getInitialRedirectPath()
    {
        $authId = Auth::id();

        $usersPer = DB::table('role_has_permissions')
                ->join('model_has_roles as mhr', 'mhr.role_id', '=', 'role_has_permissions.role_id')
                ->join('users as u', 'u.id', '=', 'mhr.model_id')
                ->join('permissions  as p', 'p.id', '=', 'role_has_permissions.permission_id')
                ->selectRaw("u.id")
                ->whereRaw("p.name='manage_dashboard' AND u.id=".Auth::id())
                ->exists();

        if(!$usersPer){
            $authUser = Auth::user();

            $userPermissions = $authUser->getAllPermissions()->pluck('name')->toArray();

            $permissionRoutes = [
                'event_management' => '/',
                'manage_participants' => '/participants',
                'user_management' => '/users',
                'email_management' => '/emailMod'
            ];

            foreach ($permissionRoutes as $permission => $route) {
                if (in_array($permission, $userPermissions)) {
                    return $route;
                }
            }

            return '/assistance';
        } else {
            return 'permission';
        }
    }
}
