<?php

namespace App\Http\Controllers\Admin;

use App\Role;
use App\Models\Permission;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use APFrmErrHelp;

class RoleController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth:admin');
    }

    public function index()
    {
        $roles = Role::with('permissions')->get();
        return view('admin.role.index', compact('roles'));
    }

    public function create()
    {
        $permissions = Permission::all()->groupBy('group_name');
        return view('admin.role.create', compact('permissions'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'role_name' => 'required|unique:roles,role_name',
            'role_abbreviation' => 'required|unique:roles,role_abbreviation',
            'permissions' => 'array'
        ]);

        $role = new Role();
        $role->role_name = $request->role_name;
        $role->role_abbreviation = $request->role_abbreviation;
        $role->role_description = $request->role_description;
        $role->save();

        if ($request->has('permissions')) {
            $role->permissions()->sync($request->permissions);
        }

        flash('Role has been created!')->success();
        return redirect()->route('admin.roles.index');
    }

    public function edit($id)
    {
        $role = Role::findOrFail($id);
        $permissions = Permission::all()->groupBy('group_name');
        $rolePermissions = $role->permissions->pluck('id')->toArray();
        return view('admin.role.edit', compact('role', 'permissions', 'rolePermissions'));
    }

    public function update(Request $request, $id)
    {
        $role = Role::findOrFail($id);
        $request->validate([
            'role_name' => 'required|unique:roles,role_name,' . $id,
            'role_abbreviation' => 'required|unique:roles,role_abbreviation,' . $id,
            'permissions' => 'array'
        ]);

        $role->role_name = $request->role_name;
        $role->role_abbreviation = $request->role_abbreviation;
        $role->role_description = $request->role_description;
        $role->save();

        if ($request->has('permissions')) {
            $role->permissions()->sync($request->permissions);
        } else {
            $role->permissions()->detach();
        }

        flash('Role has been updated!')->success();
        return redirect()->route('admin.roles.index');
    }

    public function destroy($id)
    {
        $role = Role::findOrFail($id);
        
        // Prevent deleting SUP_ADM
        if (strtolower($role->role_abbreviation) === 'sup_adm') {
            return response()->json(['status' => 'error', 'message' => 'Cannot delete Super Admin role!'], 403);
        }

        $role->permissions()->detach();
        $role->delete();

        return 'ok';
    }
}
