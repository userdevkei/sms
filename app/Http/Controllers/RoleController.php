<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreRoleRequest;
use App\Http\Requests\UpdateRoleRequest;
use App\Models\Permission;
use App\Models\Role;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Str;

class RoleController extends Controller
{
    public function index()
    {
        $roles = Role::query()
            ->withCount(['permissions', 'users'])
            ->orderBy('name')
            ->get();

        return view('roles.index', compact('roles'));
    }

    public function create()
    {
        $permissionsByModule = Permission::query()
            ->orderBy('module')->orderBy('name')
            ->get()->groupBy('module');

        return view('roles.create', compact('permissionsByModule'));
    }

    public function store(StoreRoleRequest $request): RedirectResponse
    {
        $validated = $request->validated();

        $role = Role::query()->create([
            'name'        => $validated['name'],
            'slug'        => Str::slug($validated['name'], '_'),
            'description' => $validated['description'] ?? null,
            'is_system'   => false, // roles created via the UI are never system-protected
        ]);

        $role->permissions()->sync($validated['permissions'] ?? []);

        return redirect()->route('roles.index')
            ->with('success', "Role \"{$role->name}\" was created successfully.");
    }

    public function edit(Role $role)
    {
        $permissionsByModule = Permission::query()
            ->orderBy('module')->orderBy('name')
            ->get()->groupBy('module');

        $rolePermissionIds = $role->permissions()->pluck('permissions.id')->all();

        return view('roles.edit', compact('role', 'permissionsByModule', 'rolePermissionIds'));
    }

    public function update(UpdateRoleRequest $request, Role $role): RedirectResponse
    {
        $validated = $request->validated();

        $role->update([
            'name'        => $validated['name'],
            'description' => $validated['description'] ?? null,
            // slug intentionally not updated — seeders and hasRole() checks reference it directly
        ]);

        // Super Admin always keeps full access, regardless of what was submitted.
        if ($role->slug === 'super_admin') {
            $role->permissions()->sync(Permission::query()->pluck('id'));
        } else {
            $role->permissions()->sync($validated['permissions'] ?? []);
        }

        return redirect()->route('roles.index')
            ->with('success', "Role \"{$role->name}\" was updated successfully.");
    }

    public function destroy(Role $role): JsonResponse
    {
        if ($role->is_system) {
            return response()->json([
                'success' => false,
                'message' => 'System roles cannot be deleted.',
            ], 422);
        }

        $usersCount = $role->users()->count();
        if ($usersCount > 0) {
            return response()->json([
                'success' => false,
                'message' => "This role is assigned to {$usersCount} user(s). Reassign them before deleting.",
            ], 422);
        }

        $role->permissions()->detach();
        $role->delete();

        return response()->json(['success' => true, 'message' => 'Role deleted successfully.']);
    }
}
