<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Models\Admin;

class AdminPermissions
{
    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next, string $permission = null): mixed
    {
        $admin = Auth::guard('admin')->user();

        if (!$admin) {
            abort(401, 'Unauthorized');
        }

        // Check specific permissions
        if ($permission) {
            switch ($permission) {
                case 'manage_admins':
                    if (!$admin->hasRole(Admin::ROLE_SUPER_ADMIN) && !$admin->hasRole(Admin::ROLE_ADMIN)) {
                        abort(403, 'Insufficient permissions to manage admins');
                    }
                    break;

                case 'manage_users':
                    if (!$admin->canManageUsers()) {
                        abort(403, 'Insufficient permissions to manage users');
                    }
                    break;

                case 'manage_content':
                    if (!$admin->canManageContent()) {
                        abort(403, 'Insufficient permissions to manage content');
                    }
                    break;

                case 'view_analytics':
                    if (!$admin->canViewAnalytics()) {
                        abort(403, 'Insufficient permissions to view analytics');
                    }
                    break;

                default:
                    if (!$admin->hasPermission($permission)) {
                        abort(403, "Insufficient permissions: {$permission}");
                    }
                    break;
            }
        }

        return $next($request);
    }
}
