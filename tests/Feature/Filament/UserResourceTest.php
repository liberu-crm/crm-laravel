<?php

namespace Tests\Feature\Filament;

use App\Filament\Admin\Resources\Users\UserResource;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class UserResourceTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_resource_has_correct_model(): void
    {
        $this->assertEquals(User::class, UserResource::getModel());
    }

    public function test_user_resource_navigation_label(): void
    {
        $this->assertEquals('Users', UserResource::getNavigationLabel());
    }

    public function test_user_resource_navigation_group(): void
    {
        $this->assertEquals('Administration', UserResource::getNavigationGroup());
    }

    public function test_user_resource_record_title_attribute(): void
    {
        $this->assertEquals('name', UserResource::getRecordTitleAttribute());
    }
}
