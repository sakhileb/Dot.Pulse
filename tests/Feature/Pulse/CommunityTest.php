<?php

namespace Tests\Feature\Pulse;

use App\Actions\Pulse\CreateCommunity;
use App\Models\Community;
use App\Models\PulseProfile;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CommunityTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    protected function setUp(): void
    {
        parent::setUp();
        $this->user = User::factory()->withPersonalTeam()->create();
        PulseProfile::factory()->create(['user_id' => $this->user->id]);
    }

    public function test_can_create_community_via_action(): void
    {
        $community = app(CreateCommunity::class)->handle(
            userId: $this->user->id,
            name: 'Fleet Managers',
            description: 'For fleet operators',
            industry: 'Fleet Management',
            visibility: 'public',
        );

        $this->assertDatabaseHas('communities', ['slug' => 'fleet-managers']);
        $this->assertEquals(1, $community->members_count);
    }

    public function test_creator_automatically_becomes_admin_member(): void
    {
        $community = app(CreateCommunity::class)->handle(
            userId: $this->user->id,
            name: 'AI Builders',
            visibility: 'public',
        );

        $this->assertDatabaseHas('community_memberships', [
            'community_id' => $community->id,
            'user_id' => $this->user->id,
            'role' => 'admin',
        ]);
    }

    public function test_community_slug_is_unique(): void
    {
        app(CreateCommunity::class)->handle($this->user->id, 'AI Builders', visibility: 'public');
        $second = app(CreateCommunity::class)->handle($this->user->id, 'AI Builders', visibility: 'public');

        $this->assertNotEquals('ai-builders', $second->slug);
        $this->assertStringStartsWith('ai-builders-', $second->slug);
    }

    public function test_api_can_join_community(): void
    {
        $community = Community::factory()->create(['visibility' => 'public']);

        $this->actingAs($this->user)
            ->postJson("/api/v1/communities/{$community->slug}/join")
            ->assertOk();

        $this->assertTrue($community->hasMember($this->user));
    }

    public function test_joining_twice_returns_conflict(): void
    {
        $community = Community::factory()->create(['visibility' => 'public']);

        $this->actingAs($this->user)->postJson("/api/v1/communities/{$community->slug}/join");
        $this->actingAs($this->user)->postJson("/api/v1/communities/{$community->slug}/join")
            ->assertStatus(409);
    }

    public function test_api_can_leave_community(): void
    {
        $community = Community::factory()->create(['visibility' => 'public']);
        $community->memberships()->create(['user_id' => $this->user->id, 'role' => 'member']);

        $this->actingAs($this->user)
            ->deleteJson("/api/v1/communities/{$community->slug}/leave")
            ->assertOk();

        $this->assertFalse($community->hasMember($this->user));
    }

    public function test_community_policy_blocks_private_community_for_non_member(): void
    {
        $private = Community::factory()->create(['visibility' => 'private']);
        $other = User::factory()->withPersonalTeam()->create();

        $this->actingAs($other)
            ->getJson("/api/v1/communities/{$private->slug}")
            ->assertForbidden();
    }
}
