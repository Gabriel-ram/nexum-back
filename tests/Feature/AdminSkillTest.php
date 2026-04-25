<?php

namespace Tests\Feature;

use App\Models\Skill;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class AdminSkillTest extends TestCase
{
    use RefreshDatabase;

    private function adminUser(): User
    {
        Role::firstOrCreate(['name' => 'admin', 'guard_name' => 'web']);
        $user = User::factory()->create();
        $user->assignRole('admin');
        return $user;
    }

    private function regularUser(): User
    {
        return User::factory()->create();
    }

    private function seedCategories(): void
    {
        Skill::factory()->create(['name' => 'PHP',    'type' => 'tecnica', 'category' => 'Lenguajes de Programación']);
        Skill::factory()->create(['name' => 'Python', 'type' => 'tecnica', 'category' => 'Lenguajes de Programación']);
        Skill::factory()->create(['name' => 'React',  'type' => 'tecnica', 'category' => 'Frameworks & Librerías']);
    }

    // -------------------------------------------------------------------------
    // GET /api/v1/admin/skills
    // -------------------------------------------------------------------------

    public function test_admin_can_list_all_skills(): void
    {
        $admin = $this->adminUser();
        $this->seedCategories();

        $response = $this->actingAs($admin)->getJson('/api/v1/admin/skills');

        $response->assertOk()
            ->assertJsonStructure([
                'data' => [['id', 'name', 'type', 'category']],
                'meta' => ['current_page', 'per_page', 'total'],
            ])
            ->assertJsonPath('meta.total', 3);
    }

    public function test_admin_can_filter_skills_by_type(): void
    {
        $admin = $this->adminUser();
        $this->seedCategories();
        Skill::factory()->create(['name' => 'Trabajo en equipo', 'type' => 'blanda', 'category' => 'Colaboración']);

        $response = $this->actingAs($admin)->getJson('/api/v1/admin/skills?type=blanda');

        $response->assertOk()
            ->assertJsonPath('meta.total', 1);
    }

    public function test_admin_can_filter_skills_by_category(): void
    {
        $admin = $this->adminUser();
        $this->seedCategories();

        $response = $this->actingAs($admin)
            ->getJson('/api/v1/admin/skills?category=Lenguajes de Programaci%C3%B3n');

        $response->assertOk()
            ->assertJsonPath('meta.total', 2);
    }

    public function test_regular_user_cannot_list_admin_skills(): void
    {
        $user = $this->regularUser();

        $this->actingAs($user)->getJson('/api/v1/admin/skills')->assertForbidden();
    }

    public function test_unauthenticated_user_cannot_list_admin_skills(): void
    {
        $this->getJson('/api/v1/admin/skills')->assertUnauthorized();
    }

    // -------------------------------------------------------------------------
    // GET /api/v1/admin/skills/categories
    // -------------------------------------------------------------------------

    public function test_admin_can_get_available_categories(): void
    {
        $admin = $this->adminUser();
        $this->seedCategories();

        $response = $this->actingAs($admin)->getJson('/api/v1/admin/skills/categories');

        $response->assertOk()
            ->assertJsonStructure(['data' => [['type', 'category']]]);

        // Debe retornar combinaciones únicas (2 categorías aunque hay 3 skills)
        $this->assertCount(2, $response->json('data'));
    }

    // -------------------------------------------------------------------------
    // POST /api/v1/admin/skills
    // -------------------------------------------------------------------------

    public function test_admin_can_add_new_skill_to_existing_category(): void
    {
        $admin = $this->adminUser();
        $this->seedCategories();

        $response = $this->actingAs($admin)->postJson('/api/v1/admin/skills', [
            'name'     => 'PHP Ultimate',
            'category' => 'Lenguajes de Programación',
        ]);

        $response->assertCreated()
            ->assertJsonPath('data.name', 'PHP Ultimate')
            ->assertJsonPath('data.type', 'tecnica')
            ->assertJsonPath('data.category', 'Lenguajes de Programación');

        $this->assertDatabaseHas('skills', [
            'name'     => 'PHP Ultimate',
            'type'     => 'tecnica',
            'category' => 'Lenguajes de Programación',
        ]);
    }

    public function test_admin_cannot_create_a_new_category(): void
    {
        $admin = $this->adminUser();
        $this->seedCategories();

        $response = $this->actingAs($admin)->postJson('/api/v1/admin/skills', [
            'name'     => 'Some Skill',
            'category' => 'Nueva Categoría Inventada',
        ]);

        $response->assertUnprocessable()
            ->assertJsonValidationErrors(['category']);
    }

    public function test_admin_cannot_add_duplicate_skill_in_same_category(): void
    {
        $admin = $this->adminUser();
        $this->seedCategories();

        $response = $this->actingAs($admin)->postJson('/api/v1/admin/skills', [
            'name'     => 'PHP', // ya existe en Lenguajes de Programación
            'category' => 'Lenguajes de Programación',
        ]);

        $response->assertUnprocessable()
            ->assertJsonValidationErrors(['name']);
    }

    public function test_skill_name_is_sanitized(): void
    {
        $admin = $this->adminUser();
        $this->seedCategories();

        $response = $this->actingAs($admin)->postJson('/api/v1/admin/skills', [
            'name'     => '<b>PHP Ultimate</b>',
            'category' => 'Lenguajes de Programación',
        ]);

        $response->assertCreated();
        $this->assertDatabaseHas('skills', ['name' => 'PHP Ultimate']);
    }

    public function test_admin_cannot_create_skill_without_name(): void
    {
        $admin = $this->adminUser();
        $this->seedCategories();

        $response = $this->actingAs($admin)->postJson('/api/v1/admin/skills', [
            'category' => 'Lenguajes de Programación',
        ]);

        $response->assertUnprocessable()
            ->assertJsonValidationErrors(['name']);
    }

    public function test_regular_user_cannot_create_skill(): void
    {
        $user = $this->regularUser();
        $this->seedCategories();

        $response = $this->actingAs($user)->postJson('/api/v1/admin/skills', [
            'name'     => 'PHP Ultimate',
            'category' => 'Lenguajes de Programación',
        ]);

        $response->assertForbidden();
    }

    public function test_unauthenticated_user_cannot_create_skill(): void
    {
        $this->postJson('/api/v1/admin/skills', [
            'name'     => 'PHP Ultimate',
            'category' => 'Lenguajes de Programación',
        ])->assertUnauthorized();
    }
}
