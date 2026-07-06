<?php

namespace App\Http\Responses;

use App\Enums\Role;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Laravel\Fortify\Contracts\RegisterResponse as RegisterResponseContract;

class RegisterResponse implements RegisterResponseContract
{
    protected array $roleRedirects = [
        Role::Admin->value => '/admin',
        Role::Free->value => '/app',
    ];

    protected function shouldRedirect(Request $request, string $redirect): bool
    {
        return ! $request->is($redirect) && ! $request->is($redirect.'/*');
    }

    public function toResponse($request)
    {
        setPermissionsTeamId(Auth::user()->current_team_id);
        $user = Auth::user();

        foreach ($this->roleRedirects as $role => $redirect) {
            if ($user->hasRole($role)) {
                return $request->wantsJson()
                    ? new JsonResponse(['two_factor' => false], 200)
                    : ($this->shouldRedirect($request, $redirect)
                        ? redirect()->to($redirect)
                        : redirect()->intended($redirect));
            }
        }

        $redirect = '/app';

        return $request->wantsJson()
            ? new JsonResponse(['two_factor' => false], 200)
            : ($this->shouldRedirect($request, $redirect)
                        ? redirect()->to($redirect)
                        : redirect()->intended($redirect));
    }
}
