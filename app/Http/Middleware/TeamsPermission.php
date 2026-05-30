<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use Spatie\Permission\Models\Permission;

class TeamsPermission
{
    public function handle(Request $request, Closure $next)
    {
        $user = Auth::user();

        if (!$user) {
            return redirect()->route('login')->with('error', 'You must be logged in to access this area.');
        }

        if ($user->hasRole('admin') || $user->hasRole('super_admin')) {
            return $next($request);
        }

        if (!$user->currentTeam) {
            return $next($request);
        }

        $requestedTeamId = $request->route('tenant');
        if ($requestedTeamId && $requestedTeamId != $user->currentTeam->id) {
            return redirect()->route('home')->with('error', 'You do not have permission to access this team.');
        }

        $permission = $this->getPermissionForRoute($request);
        if ($permission && !$user->checkPermissionTo($permission)) {
            return redirect()->route('home')->with('error', 'You do not have permission to perform this action.');
        }

        return $next($request);
    }

    private function getPermissionForRoute(Request $request): ?string
    {
        $route = $request->route();
        if (!$route || !$route->getName()) {
            return null;
        }

        $routeName = $route->getName();

        if (!str_starts_with($routeName, 'filament.app.resources.')) {
            return null;
        }

        $suffix = Str::after($routeName, 'filament.app.resources.');
        [$resourceSlug, $action] = array_pad(explode('.', $suffix, 2), 2, null);

        $actionMap = [
            'index' => 'view',
            'view' => 'view',
            'create' => 'create',
            'edit' => 'update',
        ];

        $actionType = $actionMap[$action] ?? null;
        if (!$actionType) {
            return null;
        }

        $modelName = Str::studly(Str::singular($resourceSlug));
        $permissionName = "{$actionType}:{$modelName}";

        if (!Permission::where('name', $permissionName)->exists()) {
            return null;
        }

        return $permissionName;
    }
}
