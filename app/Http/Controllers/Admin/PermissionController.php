<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class PermissionController extends Controller
{
    public function index(Request $request)
    {
        $perPage = $request->query('per_page', 10);
        $page = $request->query('page', 1);

        $query = Permission::withCount('roles')
            ->when($request->search, function ($query, $search) {
                $query->where('name', 'like', "%{$search}%");
            })
            ->orderBy('name');

        $permissions = $query->paginate($perPage, ['*'], 'page', $page);

        return Inertia::render('Admin/Permissions/Index', [
            'permissions' => [
                'data' => $permissions->items(),
                'current_page' => $permissions->currentPage(),
                'last_page' => $permissions->lastPage(),
                'per_page' => $permissions->perPage(),
                'total' => $permissions->total(),
                'from' => $permissions->firstItem(),
                'to' => $permissions->lastItem(),
            ],
            'filters' => $request->only(['search']),
        ]);
    }

    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255|unique:permissions,name',
            'guard_name' => 'sometimes|string|max:255',
        ]);

        Permission::create([
            'name' => $request->name,
            'guard_name' => $request->guard_name ?? 'web',
        ]);

        return redirect()->route('admin.permissions.index')
            ->with('success', 'Permission created successfully.');
    }

    public function show(Permission $permission)
    {
        $permission->load(['roles' => function ($query) {
            $query->withCount('users');
        }]);

        // Get available roles that don't have this permission
        $availableRoles = Role::whereDoesntHave('permissions', function ($query) use ($permission) {
            $query->where('permissions.id', $permission->id);
        })->get();

        return Inertia::render('Admin/Permissions/Show', [
            'permission' => $permission,
            'roles' => $permission->roles,
            'availableRoles' => $availableRoles,
        ]);
    }

    public function update(Request $request, Permission $permission)
    {
        $request->validate([
            'name' => 'required|string|max:255|unique:permissions,name,'.$permission->id,
        ]);

        $permission->update([
            'name' => $request->name,
        ]);

        return redirect()->route('admin.permissions.index')
            ->with('success', 'Permission updated successfully.');
    }

    public function destroy(Permission $permission)
    {
        if ($permission->roles()->count() > 0) {
            return redirect()->back()
                ->with('error', 'Cannot delete permission. It is assigned to one or more roles.');
        }

        $permission->delete();

        return redirect()->route('admin.permissions.index')
            ->with('success', 'Permission deleted successfully.');
    }

    public function bulkDestroy(Request $request)
    {
        $request->validate([
            'ids' => 'required|array',
            'ids.*' => 'exists:permissions,id',
        ]);

        $permissions = Permission::whereIn('id', $request->ids)
            ->withCount('roles')
            ->get();

        $deletable = $permissions->filter(fn ($permission) => $permission->roles_count === 0);
        $nonDeletable = $permissions->filter(fn ($permission) => $permission->roles_count > 0);

        if ($deletable->isNotEmpty()) {
            Permission::whereIn('id', $deletable->pluck('id'))->delete();
        }

        $message = '';
        if ($deletable->isNotEmpty()) {
            $message = "Successfully deleted {$deletable->count()} permission(s).";
        }
        if ($nonDeletable->isNotEmpty()) {
            $message .= " {$nonDeletable->count()} permission(s) could not be deleted because they are assigned to roles.";
        }

        return redirect()->route('admin.permissions.index')
            ->with('info', $message);
    }

    public function removeRole(Permission $permission, Role $role)
    {
        $role->revokePermissionTo($permission);

        return redirect()->back()
            ->with('success', "Permission removed from {$role->name} role successfully.");
    }

    public function assignRoles(Request $request, Permission $permission)
    {
        $request->validate([
            'role_ids' => 'required|array',
            'role_ids.*' => 'exists:roles,id',
        ]);

        $roles = Role::whereIn('id', $request->role_ids)->get();

        foreach ($roles as $role) {
            $role->givePermissionTo($permission);
        }

        $roleNames = $roles->pluck('name')->implode(', ');

        return redirect()->back()
            ->with('success', "Permission assigned to {$roleNames} successfully.");
    }
}
