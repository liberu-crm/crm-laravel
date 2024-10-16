<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class TeamsPermission
{
    public function handle(Request $request, Closure $next)
    {
        $user = Auth::user();

        if (!$user) {
            return redirect()->route('login')->with('error', 'You must be logged in to access this area.');
        }

        // Allow admin users to access without team restrictions
        if ($user->hasRole('admin')) {
            return $next($request);
        }

        if (!$user->currentTeam) {
            // Redirect to a default route or show an error
            return redirect()->route('home')->with('error', 'You must be part of a team to access this area.');
        }

        // Check if the requested team matches the user's current team
        $requestedTeamId = $request->route('tenant');
        if ($requestedTeamId && $requestedTeamId != $user->currentTeam->id) {
            return redirect()->route('staff.dashboard', ['tenant' => $user->currentTeam->id])
                ->with('error', 'You do not have permission to access this team.');
        }

        // Check if the user has permission to access the current route based on their role
        $route = $request->route();
        $actionName = $route->getActionName();
        $permission = $this->getPermissionForAction($actionName);

        if ($permission && !$user->hasPermissionTo($permission)) {
            return redirect()->route('staff.dashboard', ['tenant' => $user->currentTeam->id])
                ->with('error', 'You do not have permission to access this area.');
        }

        return $next($request);
    }

    private function getPermissionForAction($actionName)
    {
        // Map route actions to permissions
        $permissionMap = [
            'ClientController@index' => 'view_any_client',
            'ClientController@show' => 'view_client',
            'ClientController@create' => 'create_client',
            'ClientController@edit' => 'update_client',
            'LeadController@index' => 'view_any_lead',
            'LeadController@show' => 'view_lead',
            'LeadController@create' => 'create_lead',
            'LeadController@edit' => 'update_lead',
            // Add more mappings as needed
        ];

        return $permissionMap[$actionName] ?? null;
    }
}
