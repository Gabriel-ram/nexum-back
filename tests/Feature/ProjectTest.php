<?php

namespace Tests\Feature;

use App\Models\Portfolio;
use App\Models\Project;
use App\Models\ProjectCategory;
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
            ->assertJsonCount(3, 'data')
            ->assertJsonStructure(['data' => [['id', 'title', 'category', 'skills', 'archived']]]);
    }

    public function test_archived_projects_are_excluded_from_listing(): void
    {
        $user = $this->professionalUser();
        $portfolio = Portfolio::factory()->create(['user_id' => $user->id]);

        Project::factory()->count(2)->create(['portfolio_id' => $portfolio->id, 'archived' => false]);
        Project::factory()->count(1)->create(['portfolio_id' => $portfolio->id, 'archived' => true]);

        $response = $this->actingAs($user)->getJson('/api/v1/projects');

        $response->assertOk()->assertJsonCount(2, 'data');
    }

    public function test_user_without_portfolio_gets_empty_project_list(): void
    {
        $user = $this->professionalUser();

        $this->actingAs($user)->getJson('/api/v1/projects')
            ->assertOk()
            ->assertJsonCount(0, 'data');
    }

    public function test_unauthenticated_user_cannot_list_projects(): void
    {
        $this->getJson('/api/v1/projects')->assertUnauthorized();
    }

    public function test_projects_are_returned_ordered_by_most_recent(): void
    {
        $user = $this->professionalUser();
        $portfolio = Portfolio::factory()->create(['user_id' => $user->id]);

        $older = Project::factory()->create(['portfolio_id' => $portfolio->id, 'created_at' => now()->subDays(5)]);
        $newer = Project::factory()->create(['portfolio_id' => $portfolio->id, 'created_at' => now()]);

        $response = $this->actingAs($user)->getJson('/api/v1/projects');

        $response->assertOk();
        $ids = collect($response->json('data'))->pluck('id')->values();
        $this->assertEquals($newer->id, $ids[0]);
        $this->assertEquals($older->id, $ids[1]);
    }

    // -------------------------------------------------------------------------
    // GET /api/v1/project-categories
    // -------------------------------------------------------------------------

    public function test_authenticated_user_can_list_project_categories(): void
    {
        $user = $this->professionalUser();
        ProjectCategory::factory()->count(3)->create();

        $response = $this->actingAs($user)->getJson('/api/v1/project-categories');

        $response->assertOk()
            ->assertJsonCount(3, 'data')
            ->assertJsonStructure(['data' => [['id', 'name']]]);
    }

    public function test_unauthenticated_user_can_list_project_categories(): void
    {
        $this->getJson('/api/v1/project-categories')->assertOk();
    }

    // -------------------------------------------------------------------------
    // GET /api/v1/projects/skills
    // -------------------------------------------------------------------------

    public function test_authenticated_user_can_get_project_skills_catalog(): void
    {
        $user = $this->professionalUser();
        Skill::factory()->create(['name' => 'Laravel',         'type' => 'tecnica', 'category' => 'Frameworks & Librerías']);
        Skill::factory()->create(['name' => 'Trabajo en equipo','type' => 'blanda',  'category' => 'Colaboración']);

        $response = $this->actingAs($user)->getJson('/api/v1/projects/skills');

        $response->assertOk()
            ->assertJsonStructure(['data'])
            ->assertJsonMissingPath('data.blanda');

        $this->assertArrayHasKey('Frameworks & Librerías', $response->json('data'));
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

        $response = $this->actingAs($user)->postJson('/api/v1/projects', [
            'title'       => 'My Portfolio App',
            'description' => 'A full-stack portfolio application.',
            'project_url' => 'https://example.com',
        ]);

        $response->assertCreated()
            ->assertJsonPath('data.title', 'My Portfolio App')
            ->assertJsonPath('data.category', null)
            ->assertJsonPath('data.archived', false);

        $this->assertDatabaseHas('projects', ['title' => 'My Portfolio App']);
    }

    public function test_user_can_create_a_project_with_category_and_skills(): void
    {
        $user = $this->professionalUser();
        Portfolio::factory()->create(['user_id' => $user->id]);

        $category = ProjectCategory::factory()->create(['name' => 'Web Development']);
        $skill    = Skill::factory()->create(['name' => 'Laravel', 'type' => 'tecnica', 'category' => 'Frameworks']);

        $response = $this->actingAs($user)->postJson('/api/v1/projects', [
            'title'       => 'My App',
            'category_id' => $category->id,
            'skill_ids'   => [$skill->id],
        ]);

        $response->assertCreated()
            ->assertJsonPath('data.category.id', $category->id)
            ->assertJsonPath('data.category.name', 'Web Development')
            ->assertJsonCount(1, 'data.skills');
    }

    public function test_project_creation_fails_with_invalid_category(): void
    {
        $user = $this->professionalUser();
        Portfolio::factory()->create(['user_id' => $user->id]);

        $this->actingAs($user)->postJson('/api/v1/projects', [
            'title'       => 'Test',
            'category_id' => 9999,
        ])->assertUnprocessable()->assertJsonValidationErrors(['category_id']);
    }

    public function test_project_creation_fails_with_invalid_skill_id(): void
    {
        $user = $this->professionalUser();
        Portfolio::factory()->create(['user_id' => $user->id]);

        $this->actingAs($user)->postJson('/api/v1/projects', [
            'title'     => 'Test',
            'skill_ids' => [99999],
        ])->assertUnprocessable()->assertJsonValidationErrors(['skill_ids.0']);
    }

    public function test_project_creation_fails_without_title(): void
    {
        $user = $this->professionalUser();
        Portfolio::factory()->create(['user_id' => $user->id]);

        $this->actingAs($user)->postJson('/api/v1/projects', ['description' => 'No title.'])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['title']);
    }

    public function test_user_without_portfolio_cannot_create_project(): void
    {
        $user = $this->professionalUser();

        $this->actingAs($user)->postJson('/api/v1/projects', ['title' => 'Some Project'])
            ->assertNotFound();
    }

    public function test_title_is_sanitized_on_create(): void
    {
        $user = $this->professionalUser();
        Portfolio::factory()->create(['user_id' => $user->id]);

        $this->actingAs($user)->postJson('/api/v1/projects', ['title' => '<b>My Project</b>'])
            ->assertCreated();

        $this->assertDatabaseHas('projects', ['title' => 'My Project']);
    }

    // -------------------------------------------------------------------------
    // PUT /api/v1/projects/{project}
    // -------------------------------------------------------------------------

    public function test_user_can_update_their_project(): void
    {
        $user = $this->professionalUser();
        $portfolio = Portfolio::factory()->create(['user_id' => $user->id]);
        $project  = Project::factory()->create(['portfolio_id' => $portfolio->id]);
        $category = ProjectCategory::factory()->create(['name' => 'DevOps']);

        $response = $this->actingAs($user)->putJson("/api/v1/projects/{$project->id}", [
            'title'       => 'Updated Title',
            'category_id' => $category->id,
        ]);

        $response->assertOk()
            ->assertJsonPath('data.title', 'Updated Title')
            ->assertJsonPath('data.category.name', 'DevOps');

        $this->assertDatabaseHas('projects', ['id' => $project->id, 'title' => 'Updated Title']);
    }

    public function test_user_can_update_project_skills(): void
    {
        $user = $this->professionalUser();
        $portfolio = Portfolio::factory()->create(['user_id' => $user->id]);
        $project  = Project::factory()->create(['portfolio_id' => $portfolio->id]);

        $old = Skill::factory()->create(['name' => 'Vue',   'type' => 'tecnica', 'category' => 'Frameworks']);
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
        $owner   = $this->professionalUser();
        $portfolio = Portfolio::factory()->create(['user_id' => $owner->id]);
        $project  = Project::factory()->create(['portfolio_id' => $portfolio->id]);

        $attacker = $this->professionalUser();
        Portfolio::factory()->create(['user_id' => $attacker->id]);

        $this->actingAs($attacker)->putJson("/api/v1/projects/{$project->id}", ['title' => 'Hacked'])
            ->assertNotFound();
    }

    // -------------------------------------------------------------------------
    // DELETE /api/v1/projects/{project}  (archives, does not delete)
    // -------------------------------------------------------------------------

    public function test_user_can_archive_their_project(): void
    {
        $user = $this->professionalUser();
        $portfolio = Portfolio::factory()->create(['user_id' => $user->id]);
        $project  = Project::factory()->create(['portfolio_id' => $portfolio->id]);

        $this->actingAs($user)->deleteJson("/api/v1/projects/{$project->id}")
            ->assertOk()
            ->assertJsonPath('message', 'Project archived successfully.');

        $this->assertDatabaseHas('projects', ['id' => $project->id, 'archived' => true]);
    }

    public function test_archived_project_does_not_appear_in_listing(): void
    {
        $user = $this->professionalUser();
        $portfolio = Portfolio::factory()->create(['user_id' => $user->id]);
        $project  = Project::factory()->create(['portfolio_id' => $portfolio->id]);

        $this->actingAs($user)->deleteJson("/api/v1/projects/{$project->id}");

        $this->actingAs($user)->getJson('/api/v1/projects')
            ->assertOk()
            ->assertJsonCount(0, 'data');
    }

    public function test_user_cannot_archive_another_users_project(): void
    {
        $owner   = $this->professionalUser();
        $portfolio = Portfolio::factory()->create(['user_id' => $owner->id]);
        $project  = Project::factory()->create(['portfolio_id' => $portfolio->id]);

        $attacker = $this->professionalUser();
        Portfolio::factory()->create(['user_id' => $attacker->id]);

        $this->actingAs($attacker)->deleteJson("/api/v1/projects/{$project->id}")
            ->assertNotFound();

        $this->assertDatabaseHas('projects', ['id' => $project->id, 'archived' => false]);
    }

    public function test_unauthenticated_user_cannot_archive_project(): void
    {
        $user = $this->professionalUser();
        $portfolio = Portfolio::factory()->create(['user_id' => $user->id]);
        $project  = Project::factory()->create(['portfolio_id' => $portfolio->id]);

        $this->deleteJson("/api/v1/projects/{$project->id}")->assertUnauthorized();
    }
}
