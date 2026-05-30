<?php

namespace Tests\Unit\Middleware;

use App\Http\Middleware\TeamsPermission;
use App\Models\Team;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class TeamsPermissionTest extends TestCase
{
    use RefreshDatabase;

    private TeamsPermission $middleware;

    protected function setUp(): void
    {
        parent::setUp();

        if (! Route::has('login')) {
            Route::get('/_test/login', [
                'as' => 'login',
                'uses' => fn () => 'login',
            ]);
        }
        if (! Route::has('home')) {
            Route::get('/_test/home', [
                'as' => 'home',
                'uses' => fn () => 'home',
            ]);
        }

        $this->middleware = new TeamsPermission();
    }

    public function test_unauthenticated_user_is_redirected()
    {
        $request = Request::create('/_test/teams', 'GET');

        $response = $this->middleware->handle($request, fn ($_) => response('ok'));

        $this->assertEquals(302, $response->getStatusCode());
    }

    public function test_admin_user_bypasses_all_checks()
    {
        $team = Team::factory()->create();
        Role::firstOrCreate(['name' => 'admin', 'guard_name' => 'web']);
        $admin = User::factory()->create();
        $admin->teams()->attach($team);
        $admin->current_team_id = $team->id;
        $admin->save();
        $admin->assignRole('admin');
        Auth::login($admin);

        $request = Request::create('/_test/teams', 'GET');

        $response = $this->middleware->handle($request, fn ($req) => response('ok'));

        $this->assertEquals(200, $response->getStatusCode());
        $this->assertEquals('ok', $response->getContent());
    }

    public function test_user_without_team_passes_through()
    {
        $user = User::factory()->create();
        Auth::login($user);

        $request = Request::create('/_test/teams', 'GET');

        $response = $this->middleware->handle($request, fn ($req) => response('ok'));

        $this->assertEquals(200, $response->getStatusCode());
        $this->assertEquals('ok', $response->getContent());
    }

    public function test_user_with_correct_team_passes_through()
    {
        $team = Team::factory()->create();
        $user = User::factory()->create();
        $user->teams()->attach($team);
        $user->current_team_id = $team->id;
        $user->save();
        Auth::login($user);

        $request = Request::create('/_test/teams', 'GET');

        $response = $this->middleware->handle($request, fn ($req) => response('ok'));

        $this->assertEquals(200, $response->getStatusCode());
        $this->assertEquals('ok', $response->getContent());
    }

    public function test_unnamed_route_is_allowed()
    {
        $team = Team::factory()->create();
        $user = User::factory()->create();
        $user->teams()->attach($team);
        $user->current_team_id = $team->id;
        $user->save();
        Auth::login($user);

        $request = Request::create('/_test/teams', 'GET');

        $response = $this->middleware->handle($request, fn ($req) => response('ok'));

        $this->assertEquals(200, $response->getStatusCode());
        $this->assertEquals('ok', $response->getContent());
    }

    public function test_mapped_action_blocks_user_without_permission()
    {
        Permission::firstOrCreate(['name' => 'view:Company', 'guard_name' => 'web']);

        Route::get('/_test/companies', [
            'as' => 'filament.app.resources.companies.index',
            'uses' => fn () => 'ok',
        ]);

        $team = Team::factory()->create();
        $user = User::factory()->create();
        $user->teams()->attach($team);
        $user->current_team_id = $team->id;
        $user->save();
        Auth::login($user);

        $request = Request::create('/_test/companies', 'GET');
        $request->setRouteResolver(fn () => app('router')->getRoutes()->match($request));

        $response = $this->middleware->handle($request, fn ($req) => response('ok'));

        $this->assertEquals(302, $response->getStatusCode());
    }

    public function test_mapped_action_allows_user_with_permission()
    {
        Permission::firstOrCreate(['name' => 'view:Company', 'guard_name' => 'web']);

        Route::get('/_test/companies', [
            'as' => 'filament.app.resources.companies.index',
            'uses' => fn () => 'ok',
        ]);

        $team = Team::factory()->create();
        $user = User::factory()->create();
        $user->teams()->attach($team);
        $user->current_team_id = $team->id;
        $user->save();
        $user->givePermissionTo('view:Company');
        Auth::login($user);

        $request = Request::create('/_test/companies', 'GET');
        $request->setRouteResolver(fn () => app('router')->getRoutes()->match($request));

        $response = $this->middleware->handle($request, fn ($req) => response('ok'));

        $this->assertEquals(200, $response->getStatusCode());
        $this->assertEquals('ok', $response->getContent());
    }

    public function test_non_resource_route_is_allowed()
    {
        Route::get('/_test/dashboard', [
            'as' => 'filament.app.pages.dashboard',
            'uses' => fn () => 'ok',
        ]);

        $team = Team::factory()->create();
        $user = User::factory()->create();
        $user->teams()->attach($team);
        $user->current_team_id = $team->id;
        $user->save();
        Auth::login($user);

        $request = Request::create('/_test/dashboard', 'GET');
        $request->setRouteResolver(fn () => app('router')->getRoutes()->match($request));

        $response = $this->middleware->handle($request, fn ($req) => response('ok'));

        $this->assertEquals(200, $response->getStatusCode());
        $this->assertEquals('ok', $response->getContent());
    }

    public function test_admin_bypasses_permission_check()
    {
        Permission::firstOrCreate(['name' => 'view:Company', 'guard_name' => 'web']);

        Route::get('/_test/companies', [
            'as' => 'filament.app.resources.companies.index',
            'uses' => fn () => 'ok',
        ]);

        $team = Team::factory()->create();
        Role::firstOrCreate(['name' => 'admin', 'guard_name' => 'web']);
        $admin = User::factory()->create();
        $admin->teams()->attach($team);
        $admin->current_team_id = $team->id;
        $admin->save();
        $admin->assignRole('admin');
        Auth::login($admin);

        $request = Request::create('/_test/companies', 'GET');
        $request->setRouteResolver(fn () => app('router')->getRoutes()->match($request));

        $response = $this->middleware->handle($request, fn ($req) => response('ok'));

        $this->assertEquals(200, $response->getStatusCode());
        $this->assertEquals('ok', $response->getContent());
    }

    public function test_user_without_team_can_access_team_registration()
    {
        Route::get('/_test/team/new', [
            'as' => 'filament.app.tenant.registration',
            'uses' => fn () => 'registration page',
        ]);

        $user = User::factory()->create();
        Auth::login($user);

        $request = Request::create('/_test/team/new', 'GET');
        $request->setRouteResolver(fn () => app('router')->getRoutes()->match($request));

        $response = $this->middleware->handle($request, fn ($req) => response('ok'));

        $this->assertEquals(200, $response->getStatusCode());
    }

    public function test_super_admin_bypasses_all_checks()
    {
        $team = Team::factory()->create();
        Role::firstOrCreate(['name' => 'super_admin', 'guard_name' => 'web']);
        $admin = User::factory()->create();
        $admin->teams()->attach($team);
        $admin->current_team_id = $team->id;
        $admin->save();
        $admin->assignRole('super_admin');
        Auth::login($admin);

        $request = Request::create('/_test/teams', 'GET');

        $response = $this->middleware->handle($request, fn ($req) => response('ok'));

        $this->assertEquals(200, $response->getStatusCode());
        $this->assertEquals('ok', $response->getContent());
    }

    public function test_super_admin_bypasses_permission_check()
    {
        Permission::firstOrCreate(['name' => 'view:Company', 'guard_name' => 'web']);

        Route::get('/_test/companies', [
            'as' => 'filament.app.resources.companies.index',
            'uses' => fn () => 'ok',
        ]);

        $team = Team::factory()->create();
        Role::firstOrCreate(['name' => 'super_admin', 'guard_name' => 'web']);
        $admin = User::factory()->create();
        $admin->teams()->attach($team);
        $admin->current_team_id = $team->id;
        $admin->save();
        $admin->assignRole('super_admin');
        Auth::login($admin);

        $request = Request::create('/_test/companies', 'GET');
        $request->setRouteResolver(fn () => app('router')->getRoutes()->match($request));

        $response = $this->middleware->handle($request, fn ($req) => response('ok'));

        $this->assertEquals(200, $response->getStatusCode());
        $this->assertEquals('ok', $response->getContent());
    }

    public function test_wrong_team_redirects()
    {
        Route::get('/_test/teams/{tenant}', [
            'as' => 'filament.app.dashboard',
            'uses' => fn () => 'ok',
        ]);

        $team = Team::factory()->create();
        $otherTeam = Team::factory()->create();
        $user = User::factory()->create();
        $user->teams()->attach($team);
        $user->current_team_id = $team->id;
        $user->save();
        Auth::login($user);

        $request = Request::create("/_test/teams/{$otherTeam->id}", 'GET');
        $request->setRouteResolver(fn () => app('router')->getRoutes()->match($request));

        $response = $this->middleware->handle($request, fn ($req) => response('ok'));

        $this->assertEquals(302, $response->getStatusCode());
    }

    public function test_resource_route_without_action_is_allowed()
    {
        Route::get('/_test/advertising-dashboards', [
            'as' => 'filament.app.resources.advertising-dashboards',
            'uses' => fn () => 'ok',
        ]);

        $team = Team::factory()->create();
        $user = User::factory()->create();
        $user->teams()->attach($team);
        $user->current_team_id = $team->id;
        $user->save();
        Auth::login($user);

        $request = Request::create('/_test/advertising-dashboards', 'GET');
        $request->setRouteResolver(fn () => app('router')->getRoutes()->match($request));

        $response = $this->middleware->handle($request, fn ($req) => response('ok'));

        $this->assertEquals(200, $response->getStatusCode());
        $this->assertEquals('ok', $response->getContent());
    }
}
