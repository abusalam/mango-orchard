<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\RoleDelegation;
use App\Permissions;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;
use Illuminate\View\View;

class RoleDelegationController extends Controller implements HasMiddleware
{
    public static function middleware(): array
    {
        return [
            new Middleware(['auth', 'permission:'.Permissions::USERS_MANAGE]),
        ];
    }

    public function index(): View
    {
        return view('admin.role-delegations.index', [
            'active' => RoleDelegation::with(['recipient', 'delegator', 'role'])
                ->active()
                ->latest('delegated_at')
                ->get(),
            'revoked' => RoleDelegation::with(['recipient', 'delegator', 'revoker', 'role'])
                ->revoked()
                ->latest('revoked_at')
                ->limit(25)
                ->get(),
        ]);
    }
}
