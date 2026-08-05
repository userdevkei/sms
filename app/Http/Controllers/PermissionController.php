<?php

namespace App\Http\Controllers;

use App\Models\Permission;

class PermissionController extends Controller
{
    public function index()
    {
        $permissionsByModule = Permission::query()
            ->withCount('roles')
            ->orderBy('module')->orderBy('name')
            ->get()->groupBy('module');

        return view('permissions.index', compact('permissionsByModule'));
    }
}
