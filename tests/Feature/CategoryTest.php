<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CategoryTest extends TestCase
{
    use RefreshDatabase;

    public function test_authenticated_user_can_list_categories(): void
    {
        $user = User::factory()->create();
        Category::factory()->count(5)->create();

        $response = $this->actingAs($user)->getJson('/api/v1/categories');

        $response->assertOk()
            ->assertJsonCount(5, 'data')
            ->assertJsonStructure(['data' => [['id', 'name', 'slug']]]);
    }

    public function test_unauthenticated_user_cannot_list_categories(): void
    {
        $this->getJson('/api/v1/categories')->assertUnauthorized();
    }

    public function test_categories_are_returned_sorted_by_name(): void
    {
        $user = User::factory()->create();

        Category::factory()->create(['name' => 'Zebra', 'slug' => 'zebra']);
        Category::factory()->create(['name' => 'Alpha', 'slug' => 'alpha']);

        $response = $this->actingAs($user)->getJson('/api/v1/categories');

        $response->assertOk();
        $names = collect($response->json('data'))->pluck('name')->values();
        $this->assertEquals('Alpha', $names[0]);
        $this->assertEquals('Zebra', $names[1]);
    }
}
