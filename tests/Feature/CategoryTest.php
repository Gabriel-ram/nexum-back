<?php

namespace Tests\Feature;

use App\Models\Skill;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * La tabla categories fue eliminada. Su contenido pasó a la tabla skills
 * como type = 'project_category'. Estos tests cubren el endpoint de catálogo
 * de skills para proyectos: GET /api/v1/projects/skills
 */
class CategoryTest extends TestCase
{
    use RefreshDatabase;

    public function test_authenticated_user_can_get_project_skills_catalog(): void
    {
        $user = User::factory()->create();
        Skill::factory()->create(['type' => 'tecnica', 'category' => 'Frameworks & Librerías']);
        Skill::factory()->create(['type' => 'project_category', 'category' => 'Categoría de Proyecto']);

        $response = $this->actingAs($user)->getJson('/api/v1/projects/skills');

        $response->assertOk()
            ->assertJsonStructure(['data']);
    }

    public function test_unauthenticated_user_cannot_get_project_skills_catalog(): void
    {
        $this->getJson('/api/v1/projects/skills')->assertUnauthorized();
    }

    public function test_catalog_excludes_soft_skills(): void
    {
        $user = User::factory()->create();
        Skill::factory()->create(['name' => 'Trabajo en equipo', 'type' => 'blanda', 'category' => 'Colaboración']);
        Skill::factory()->create(['name' => 'Laravel', 'type' => 'tecnica', 'category' => 'Frameworks']);

        $response = $this->actingAs($user)->getJson('/api/v1/projects/skills');

        $response->assertOk();
        $this->assertArrayNotHasKey('blanda', $response->json('data'));
        $this->assertArrayHasKey('tecnica', $response->json('data'));
    }

    public function test_catalog_includes_project_categories(): void
    {
        $user = User::factory()->create();
        Skill::factory()->create(['name' => 'Web Development', 'type' => 'project_category', 'category' => 'Categoría de Proyecto']);
        Skill::factory()->create(['name' => 'Mobile Development', 'type' => 'project_category', 'category' => 'Categoría de Proyecto']);

        $response = $this->actingAs($user)->getJson('/api/v1/projects/skills');

        $response->assertOk();
        $this->assertArrayHasKey('project_category', $response->json('data'));
        $this->assertCount(2, $response->json('data.project_category.Categoría de Proyecto'));
    }
}
