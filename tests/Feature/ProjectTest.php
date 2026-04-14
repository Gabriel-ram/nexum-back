<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Portfolio;
use App\Models\Project;
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

    public function test_projects_can_be_filtered_by_category(): void
    {
        $user = $this->professionalUser();
        $portfolio = Portfolio::factory()->create(['user_id' => $user->id]);
        $category = Category::factory()->create();

        Project::factory()->count(2)->create(['portfolio_id' => $portfolio->id, 'category_id' => $category->id]);
        Project::factory()->count(3)->create(['portfolio_id' => $portfolio->id, 'category_id' => null]);

        $response = $this->actingAs($user)
            ->getJson("/api/v1/projects?category_id={$category->id}");

        $response->assertOk()
            ->assertJsonCount(2, 'data');
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
    // POST /api/v1/projects
    // -------------------------------------------------------------------------

    public function test_user_can_create_a_project(): void
    {
        $user = $this->professionalUser();
        Portfolio::factory()->create(['user_id' => $user->id]);

        $payload = [
            'title'        => 'My Portfolio App',
            'description'  => 'A full-stack portfolio application.',
            'project_url'  => 'https://example.com',
            'technologies' => ['Laravel', 'Vue', 'PostgreSQL'],
        ];

        $response = $this->actingAs($user)->postJson('/api/v1/projects', $payload);

        $response->assertCreated()
            ->assertJsonPath('data.title', 'My Portfolio App')
            ->assertJsonPath('data.technologies', ['Laravel', 'Vue', 'PostgreSQL']);

        $this->assertDatabaseHas('projects', ['title' => 'My Portfolio App']);
    }

    public function test_project_can_be_created_with_category(): void
    {
        $user = $this->professionalUser();
        Portfolio::factory()->create(['user_id' => $user->id]);
        $category = Category::factory()->create();

        $response = $this->actingAs($user)->postJson('/api/v1/projects', [
            'title'       => 'Categorized Project',
            'category_id' => $category->id,
        ]);

        $response->assertCreated()
            ->assertJsonPath('data.category.id', $category->id);
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

    public function test_project_creation_fails_with_invalid_category(): void
    {
        $user = $this->professionalUser();
        Portfolio::factory()->create(['user_id' => $user->id]);

        $response = $this->actingAs($user)->postJson('/api/v1/projects', [
            'title'       => 'Test',
            'category_id' => 9999,
        ]);

        $response->assertUnprocessable()
            ->assertJsonValidationErrors(['category_id']);
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

        // strip_tags() removes HTML tags, keeping inner text
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
    // DELETE /api/v1/projects/{project}
    // -------------------------------------------------------------------------

    public function test_user_can_delete_their_project(): void
    {
        $user = $this->professionalUser();
        $portfolio = Portfolio::factory()->create(['user_id' => $user->id]);
        $project = Project::factory()->create(['portfolio_id' => $portfolio->id]);

        $response = $this->actingAs($user)->deleteJson("/api/v1/projects/{$project->id}");

        $response->assertOk()
            ->assertJsonPath('message', 'Project deleted successfully.');

        $this->assertDatabaseMissing('projects', ['id' => $project->id]);
    }

    public function test_user_cannot_delete_another_users_project(): void
    {
        $owner = $this->professionalUser();
        $portfolio = Portfolio::factory()->create(['user_id' => $owner->id]);
        $project = Project::factory()->create(['portfolio_id' => $portfolio->id]);

        $attacker = $this->professionalUser();
        Portfolio::factory()->create(['user_id' => $attacker->id]);

        $response = $this->actingAs($attacker)->deleteJson("/api/v1/projects/{$project->id}");

        $response->assertNotFound();
        $this->assertDatabaseHas('projects', ['id' => $project->id]);
    }

    public function test_unauthenticated_user_cannot_delete_project(): void
    {
        $user = $this->professionalUser();
        $portfolio = Portfolio::factory()->create(['user_id' => $user->id]);
        $project = Project::factory()->create(['portfolio_id' => $portfolio->id]);

        $this->deleteJson("/api/v1/projects/{$project->id}")->assertUnauthorized();
    }
}
