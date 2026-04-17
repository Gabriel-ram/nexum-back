<?php

namespace Tests\Feature;

use App\Models\ProjectCategory;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class CategoryTest extends TestCase
{
    use RefreshDatabase;

    private function adminUser(): User
    {
        Role::firstOrCreate(['name' => 'admin', 'guard_name' => 'web']);
        $user = User::factory()->create();
        $user->assignRole('admin');
        return $user;
    }

    // -------------------------------------------------------------------------
    // POST /api/v1/admin/project-categories
    // -------------------------------------------------------------------------

    public function test_admin_can_create_project_category(): void
    {
        $admin = $this->adminUser();

        $response = $this->actingAs($admin)->postJson('/api/v1/admin/project-categories', [
            'name' => 'Blockchain',
        ]);

        $response->assertCreated()
            ->assertJsonPath('data.name', 'Blockchain');

        $this->assertDatabaseHas('project_categories', ['name' => 'Blockchain']);
    }

    public function test_admin_cannot_create_duplicate_category(): void
    {
        $admin = $this->adminUser();
        ProjectCategory::factory()->create(['name' => 'Web Development']);

        $this->actingAs($admin)->postJson('/api/v1/admin/project-categories', [
            'name' => 'Web Development',
        ])->assertUnprocessable()->assertJsonValidationErrors(['name']);
    }

    public function test_category_name_is_sanitized(): void
    {
        $admin = $this->adminUser();

        $this->actingAs($admin)->postJson('/api/v1/admin/project-categories', [
            'name' => '<b>Blockchain</b>',
        ])->assertCreated();

        $this->assertDatabaseHas('project_categories', ['name' => 'Blockchain']);
    }

    public function test_regular_user_cannot_create_project_category(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)->postJson('/api/v1/admin/project-categories', [
            'name' => 'Blockchain',
        ])->assertForbidden();
    }

    public function test_unauthenticated_user_cannot_create_project_category(): void
    {
        $this->postJson('/api/v1/admin/project-categories', [
            'name' => 'Blockchain',
        ])->assertUnauthorized();
    }
}
