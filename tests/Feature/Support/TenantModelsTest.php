<?php

declare(strict_types=1);

namespace Tests\Feature\Support;

use App\Models\Contact;
use App\Models\Lead;
use App\Models\Team;
use App\Models\TeamBackup;
use App\Models\User;
use App\Support\TenantModels;
use Tests\TestCase;

class TenantModelsTest extends TestCase
{
    public function test_all_includes_team_scoped_models(): void
    {
        $all = TenantModels::all();

        $this->assertContains(Contact::class, $all);
        $this->assertContains(Lead::class, $all);
    }

    public function test_all_excludes_non_tenant_models(): void
    {
        $all = TenantModels::all();

        $this->assertNotContains(User::class, $all);
        $this->assertNotContains(Team::class, $all);
        $this->assertNotContains(TeamBackup::class, $all);
    }
}
