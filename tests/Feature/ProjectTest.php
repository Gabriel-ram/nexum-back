<?php

namespace Tests\Feature;

use App\Models\Portfolio;
use App\Models\Project;
use App\Models\Skill;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProjectTest extends TestCase
{
    use RefreshDatabase;

    private function professionalUser(): User
    {
        return User::factory()->create();
    }

    // -------------------------------------------------------------------------
    // GET /api/v1/projects
    // -------------------------------------------------------------------------

    public function test_authenticated_user_can_list_their_projects(): void
    {
        $user = $this->professionalUser();
        $portfolio = Portfolio::factory()->create(['user_id' => $user->id]);
        Project::factory()->count(3)->create(['portfolio_id' => $portfolio->id]);

        $response = $this->actingAs($user)->getJson('/api/v1/projects');

        $response->assertOk()
            ->assertJsonCount(3, 'data');
    }

    public function test_archived_projects_are_excluded_from_listing(): void
    {
        $user = $this->professionalUser();
        $portfolio = Portfolio::factory()->create(['user_id' => $user->id]);

        Project::factory()->count(2)->create(['portfolio_id' => $portfolio->id, 'archived' => false]);
        Project::factory()->count(1)->create(['portfolio_id' => $portfolio->id, 'archived' => true]);

        $response = $this->actingAs($user)->getJson('/api/v1/projects');

        $response->assertOk()
            ->assertJsonCount(2, 'data');
    }

    public function test_user_without_portfolio_gets_empty_project_list(): void
    {
        $user = $this->professionalUser();

        $response = $this->actingAs($user)->getJson('/api/v1/projects');

        $response->assertOk()
            ->assertJsonCount(0, 'data');
    }

    public function test_unauthenticated_user_cannot_list_projects(): void
    {
        $this->getJson('/api/v1/projects')->assertUnauthorized();
    }

    public function test_projects_can_be_sorted_by_title_asc(): void
    {
        $user = $this->professionalUser();
        $portfolio = Portfolio::factory()->create(['user_id' => $user->id]);

        Project::factory()->create(['portfolio_id' => $portfolio->id, 'title' => 'Zebra Project']);
        Project::factory()->create(['portfolio_id' => $portfolio->id, 'title' => 'Alpha Project']);

        $response = $this->actingAs($user)
            ->getJson('/api/v1/projects?sort_by=title&sort_dir=asc');

        $response->assertOk();
        $titles = collect($response->json('data'))->pluck('title')->values();
        $this->assertEquals('Alpha Project', $titles[0]);
        $this->assertEquals('Zebra Project', $titles[1]);
    }

    // -------------------------------------------------------------------------
    // GET /api/v1/projects/skills
    // -------------------------------------------------------------------------

    public function test_authenticated_user_can_get_project_skills_catalog(): void
    {
        $user = $this->professionalUser();
        Skill::factory()->create(['name' => 'Laravel', 'type' => 'tecnica', 'category' => 'Frameworks & Librerías']);
        Skill::factory()->create(['name' => 'Web Development', 'type' => 'project_category', 'category' => 'Categoría de Proyecto']);
        // Soft skills must be excluded
        Skill::factory()->create(['name' => 'Trabajo en equipo', 'type' => 'blanda', 'category' => 'Colaboración']);

        $response = $this->actingAs($user)->getJson('/api/v1/projects/skills');

        $response->assertOk()
            ->assertJsonStructure(['data' => ['tecnica', 'project_category']])
            ->assertJsonMissingPath('data.blanda');
    }

    public function test_unauthenticated_user_cannot_get_project_skills_catalog(): void
    {
        $this->getJson('/api/v1/projects/skills')->assertUnauthorized();
    }

    // -------------------------------------------------------------------------
    // POST /api/v1/projects
    // -------------------------------------------------------------------------

    public function test_user_can_create_a_project(): void
    {
        $user = $this->professionalUser();
        Portfolio::factory()->create(['user_id' => $user->id]);

        $payload = [
            'title'       => 'My Portfolio App',
            'description' => 'A full-stack portfolio application.',
            'project_url' => 'https://example.com',
        ];

        $response = $this->actingAs($user)->postJson('/api/v1/projects', $payload);

        $response->assertCreated()
            ->assertJsonPath('data.title', 'My Portfolio App')
            ->assertJsonStructure(['data' => ['id', 'title', 'description', 'project_url', 'archived', 'skills']]);

        $this->assertDatabaseHas('projects', ['title' => 'My Portfolio App']);
    }

    public function test_user_can_create_a_project_with_skills(): void
    {
        $user = $this->professionalUser();
        Portfolio::factory()->create(['user_id' => $user->id]);

        $skill1 = Skill::factory()->create(['name' => 'Laravel', 'type' => 'tecnica', 'category' => 'Frameworks']);
        $skill2 = Skill::factory()->create(['name' => 'PostgreSQL', 'type' => 'tecnica', 'category' => 'Bases de Datos']);

        $response = $this->actingAs($user)->postJson('/api/v1/projects', [
            'title'     => 'Project with Skills',
            'skill_ids' => [$skill1->id, $skill2->id],
        ]);

        $response->assertCreated()
            ->assertJsonCount(2, 'data.skills');

        $this->assertDatabaseHas('project_skills', ['skill_id' => $skill1->id]);
        $this->assertDatabaseHas('project_skills', ['skill_id' => $skill2->id]);
    }

    public function test_project_creation_fails_without_title(): void
    {
        $user = $this->professionalUser();
        Portfolio::factory()->create(['user_id' => $user->id]);

        $response = $this->actingAs($user)->postJson('/api/v1/projects', [
            'description' => 'No title provided.',
        ]);

        $response->assertUnprocessable()
            ->assertJsonValidationErrors(['title']);
    }

    public function test_project_creation_fails_with_invalid_skill_id(): void
    {
        $user = $this->professionalUser();
        Portfolio::factory()->create(['user_id' => $user->id]);

        $response = $this->actingAs($user)->postJson('/api/v1/projects', [
            'title'     => 'Test',
            'skill_ids' => [99999],
        ]);

        $response->assertUnprocessable()
            ->assertJsonValidationErrors(['skill_ids.0']);
    }

    public function test_user_without_portfolio_cannot_create_project(): void
    {
        $user = $this->professionalUser();

        $response = $this->actingAs($user)->postJson('/api/v1/projects', [
            'title' => 'Some Project',
        ]);

        $response->assertNotFound();
    }

    public function test_title_is_sanitized_on_create(): void
    {
        $user = $this->professionalUser();
        Portfolio::factory()->create(['user_id' => $user->id]);

        $response = $this->actingAs($user)->postJson('/api/v1/projects', [
            'title' => '<b>My Project</b>',
        ]);

        $response->assertCreated();
        $this->assertDatabaseHas('projects', ['title' => 'My Project']);
    }

    // -------------------------------------------------------------------------
    // PUT /api/v1/projects/{project}
    // -------------------------------------------------------------------------

    public function test_user_can_update_their_project(): void
    {
        $user = $this->professionalUser();
        $portfolio = Portfolio::factory()->create(['user_id' => $user->id]);
        $project = Project::factory()->create(['portfolio_id' => $portfolio->id]);

        $response = $this->actingAs($user)->putJson("/api/v1/projects/{$project->id}", [
            'title'       => 'Updated Title',
            'description' => 'Updated description.',
        ]);

        $response->assertOk()
            ->assertJsonPath('data.title', 'Updated Title');

        $this->assertDatabaseHas('projects', ['id' => $project->id, 'title' => 'Updated Title']);
    }

    public function test_user_can_update_project_skills(): void
    {
        $user = $this->professionalUser();
        $portfolio = Portfolio::factory()->create(['user_id' => $user->id]);
        $project = Project::factory()->create(['portfolio_id' => $portfolio->id]);

        $old = Skill::factory()->create(['name' => 'Vue', 'type' => 'tecnica', 'category' => 'Frameworks']);
        $new = Skill::factory()->create(['name' => 'React', 'type' => 'tecnica', 'category' => 'Frameworks']);
        $project->skills()->sync([$old->id]);

        $response = $this->actingAs($user)->putJson("/api/v1/projects/{$project->id}", [
            'title'     => $project->title,
            'skill_ids' => [$new->id],
        ]);

        $response->assertOk()
            ->assertJsonCount(1, 'data.skills')
            ->assertJsonPath('data.skills.0.name', 'React');

        $this->assertDatabaseMissing('project_skills', ['project_id' => $project->id, 'skill_id' => $old->id]);
        $this->assertDatabaseHas('project_skills', ['project_id' => $project->id, 'skill_id' => $new->id]);
    }

    public function test_user_cannot_update_another_users_project(): void
    {
        $owner = $this->professionalUser();
        $portfolio = Portfolio::factory()->create(['user_id' => $owner->id]);
        $project = Project::factory()->create(['portfolio_id' => $portfolio->id]);

        $attacker = $this->professionalUser();
        Portfolio::factory()->create(['user_id' => $attacker->id]);

        $response = $this->actingAs($attacker)->putJson("/api/v1/projects/{$project->id}", [
            'title' => 'Hacked',
        ]);

        $response->assertNotFound();
    }

    // -------------------------------------------------------------------------
    // DELETE /api/v1/projects/{project}  (archives, does not delete)
    // -------------------------------------------------------------------------

    public function test_user_can_archive_their_project(): void
    {
        $user = $this->professionalUser();
        $portfolio = Portfolio::factory()->create(['user_id' => $user->id]);
        $project = Project::factory()->create(['portfolio_id' => $portfolio->id]);

        $response = $this->actingAs($user)->deleteJson("/api/v1/projects/{$project->id}");

        $response->assertOk()
            ->assertJsonPath('message', 'Project archived successfully.');

        // Row still exists but is archived
        $this->assertDatabaseHas('projects', ['id' => $project->id, 'archived' => true]);
    }

    public function test_archived_project_does_not_appear_in_listing(): void
    {
        $user = $this->professionalUser();
        $portfolio = Portfolio::factory()->create(['user_id' => $user->id]);
        $project = Project::factory()->create(['portfolio_id' => $portfolio->id]);

        $this->actingAs($user)->deleteJson("/api/v1/projects/{$project->id}");

        $response = $this->actingAs($user)->getJson('/api/v1/projects');
        $response->assertOk()->assertJsonCount(0, 'data');
    }

    public function test_user_cannot_archive_another_users_project(): void
    {
        $owner = $this->professionalUser();
        $portfolio = Portfolio::factory()->create(['user_id' => $owner->id]);
        $project = Project::factory()->create(['portfolio_id' => $portfolio->id]);

        $attacker = $this->professionalUser();
        Portfolio::factory()->create(['user_id' => $attacker->id]);

        $response = $this->actingAs($attacker)->deleteJson("/api/v1/projects/{$project->id}");

        $response->assertNotFound();
        $this->assertDatabaseHas('projects', ['id' => $project->id, 'archived' => false]);
    }

    public function test_unauthenticated_user_cannot_archive_project(): void
    {
        $user = $this->professionalUser();
        $portfolio = Portfolio::factory()->create(['user_id' => $user->id]);
        $project = Project::factory()->create(['portfolio_id' => $portfolio->id]);

        $this->deleteJson("/api/v1/projects/{$project->id}")->assertUnauthorized();
    }
}
