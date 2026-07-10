<?php

declare(strict_types=1);

namespace Tests\Feature\Permissions;

use App\Models\User;
use App\Support\PermissionCatalog;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Gate;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role as SpatieRole;
use Tests\TestCase;

class PermissionCatalogTest extends TestCase
{
    use RefreshDatabase;

    private function role(string $name): SpatieRole
    {
        setPermissionsTeamId(null);

        return SpatieRole::where('name', $name)->where('guard_name', 'web')->firstOrFail();
    }

    public function test_sync_mints_the_full_crud_catalog(): void
    {
        PermissionCatalog::sync();

        // A representative permission from each group / action.
        foreach (['view_contact', 'create_contact', 'update_contact', 'delete_contact',
            'delete_ad_set', 'view_sso_connection', 'view_audit_log'] as $perm) {
            $this->assertNotNull(
                Permission::where('name', $perm)->where('guard_name', 'web')->first(),
                "missing permission {$perm}"
            );
        }

        // 4 actions * (9 core + 14 advertising + 8 settings + 3 logs = 34) = 136.
        $catalog = Permission::all()
            ->filter(fn (Permission $p): bool => (bool) preg_match('/^(view|create|update|delete)_/', $p->name));
        $this->assertGreaterThanOrEqual(136, $catalog->count());
    }

    public function test_admin_has_full_crud_including_settings(): void
    {
        PermissionCatalog::sync();
        $admin = $this->role('admin');

        foreach (['view_contact', 'create_contact', 'update_contact', 'delete_contact',
            'view_campaign', 'delete_campaign', 'create_sso_connection', 'delete_team_role'] as $perm) {
            $this->assertTrue($admin->hasPermissionTo($perm), "admin should have {$perm}");
        }
        // Logs are view-only even for admin (system-generated).
        $this->assertTrue($admin->hasPermissionTo('view_audit_log'));
        $this->assertFalse($admin->hasPermissionTo('delete_audit_log'));
    }

    public function test_manager_has_core_and_adv_but_not_security_settings(): void
    {
        PermissionCatalog::sync();
        $manager = $this->role('manager');

        $this->assertTrue($manager->hasPermissionTo('delete_contact'));
        $this->assertTrue($manager->hasPermissionTo('update_campaign'));
        $this->assertTrue($manager->hasPermissionTo('view_portal_access_log'));
        // manager exceptions inside the withheld settings group:
        $this->assertTrue($manager->hasPermissionTo('create_territory'));
        $this->assertTrue($manager->hasPermissionTo('update_knowledge_base_article'));
        // but no security/team settings:
        $this->assertFalse($manager->hasPermissionTo('view_sso_connection'));
        $this->assertFalse($manager->hasPermissionTo('create_team_role'));
        $this->assertFalse($manager->hasPermissionTo('update_webhook_delivery'));
    }

    public function test_sales_rep_crud_core_view_advertising_no_settings(): void
    {
        PermissionCatalog::sync();
        $rep = $this->role('sales_rep');

        $this->assertTrue($rep->hasPermissionTo('create_deal'));
        $this->assertTrue($rep->hasPermissionTo('view_advertising_account'));
        $this->assertFalse($rep->hasPermissionTo('create_advertising_account'));
        $this->assertFalse($rep->hasPermissionTo('view_sso_connection'));
        $this->assertFalse($rep->hasPermissionTo('view_audit_log'));
    }

    public function test_free_is_view_only_core(): void
    {
        PermissionCatalog::sync();
        $free = $this->role('free');

        $this->assertTrue($free->hasPermissionTo('view_contact'));
        $this->assertFalse($free->hasPermissionTo('create_contact'));
        $this->assertFalse($free->hasPermissionTo('view_campaign'));
    }

    public function test_super_admin_holds_every_permission(): void
    {
        PermissionCatalog::sync();
        $super = $this->role('super_admin');

        $this->assertTrue($super->hasPermissionTo('create_sso_connection'));
        $this->assertTrue($super->hasPermissionTo('delete_audit_log'));
    }

    public function test_super_admin_user_bypasses_every_gate(): void
    {
        PermissionCatalog::sync();
        $user = User::factory()->create();
        setPermissionsTeamId(null);
        $user->assignRole('super_admin');

        // Gate::before from shield grants any ability, even an unminted one.
        $this->assertTrue(Gate::forUser($user)->allows('view_contact'));
        $this->assertTrue(Gate::forUser($user)->allows('some_unminted_ability'));
    }

    public function test_sync_is_idempotent(): void
    {
        PermissionCatalog::sync();
        $countAfterFirst = Permission::count();
        $adminPermsFirst = $this->role('admin')->permissions()->count();

        PermissionCatalog::sync();

        $this->assertSame($countAfterFirst, Permission::count());
        $this->assertSame($adminPermsFirst, $this->role('admin')->permissions()->count());
    }
}
