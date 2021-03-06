<?php

declare(strict_types=1);

namespace Tests\Feature\API;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Support\Arr;
use Sendportal\Base\Models\Segment;
use Tests\TestCase;

class SegmentsControllerTest extends TestCase
{
    use RefreshDatabase,
        WithFaker;

    /** @test */
    public function a_list_of_a_workspaces_segments_can_be_retrieved()
    {
        $user = $this->createUserWithWorkspace();

        $segment = $this->createSegment($user);

        $route = route('sendportal.api.segments.index', [
            'workspaceId' => $user->currentWorkspace()->id
        ]);

        $this
            ->actingAs($user, 'api')
            ->getJson($route)
            ->assertOk()
            ->assertJson([
                'data' => [
                    Arr::only($segment->toArray(), ['name'])
                ],
            ]);
    }

    /** @test */
    public function a_single_segment_can_be_retrieved()
    {
        $user = $this->createUserWithWorkspace();

        $segment = $this->createSegment($user);

        $route = route('sendportal.api.segments.show', [
            'workspaceId' => $user->currentWorkspace()->id,
            'segment' => $segment->id
        ]);

        $this
            ->actingAs($user, 'api')
            ->getJson($route)
            ->assertOk()
            ->assertJson([
                'data' => Arr::only($segment->toArray(), ['name']),
            ]);
    }

    /** @test */
    public function a_new_segment_can_be_added()
    {
        $user = $this->createUserWithWorkspace();

        $route = route('sendportal.api.segments.store', $user->currentWorkspace()->id);

        $request = [
            'name' => $this->faker->colorName,
        ];

        $this
            ->actingAs($user, 'api')
            ->postJson($route, $request)
            ->assertStatus(201)
            ->assertJson(['data' => $request]);

        $this->assertDatabaseHas('segments', $request);
    }

    /** @test */
    public function a_segment_can_be_updated()
    {
        $user = $this->createUserWithWorkspace();

        $segment = $this->createSegment($user);

        $route = route('sendportal.api.segments.update', [
            'workspaceId' => $user->currentWorkspace()->id,
            'segment' => $segment->id
        ]);

        $request = [
            'name' => 'newName',
        ];

        $this
            ->actingAs($user, 'api')
            ->putJson($route, $request)
            ->assertOk()
            ->assertJson(['data' => $request]);

        $this->assertDatabaseMissing('segments', $segment->toArray());
        $this->assertDatabaseHas('segments', $request);
    }

    /** @test */
    public function a_segment_can_be_deleted()
    {
        $user = $this->createUserWithWorkspace();

        $segment = $this->createSegment($user);

        $route = route('sendportal.api.segments.destroy', [
            'workspaceId' => $user->currentWorkspace()->id,
            'segment' => $segment->id
        ]);

        $this
            ->actingAs($user, 'api')
            ->deleteJson($route)
            ->assertStatus(204);

        $this->assertDatabaseCount('segments', 0);
    }

    /** @test */
    public function a_segment_name_must_be_unique_for_a_workspace()
    {
        $user = $this->createUserWithWorkspace();

        $segment = $this->createSegment($user);

        $route = route('sendportal.api.segments.store', [
            'workspaceId' => $user->currentWorkspace()->id
        ]);

        $request = [
            'name' => $segment->name,
        ];

        $this
            ->actingAs($user, 'api')
            ->postJson($route, $request)
            ->assertStatus(422)
            ->assertJsonValidationErrors('name');

        $this->assertEquals(1, Segment::where('name', $segment->name)->count());
    }

    /** @test */
    public function two_workspaces_can_have_the_same_name_for_a_segment()
    {
        $userA = $this->createUserWithWorkspace();
        $userB = $this->createUserWithWorkspace();

        $segment = $this->createSegment($userA);

        $route = route('sendportal.api.segments.store', [
            'workspaceId' => $userB->currentWorkspace()->id
        ]);

        $request = [
            'name' => $segment->name,
        ];

        $this
            ->actingAs($userB, 'api')
            ->postJson($route, $request)
            ->assertStatus(201);

        $this->assertEquals(2, Segment::where('name', $segment->name)->count());
    }
}
