<?php

namespace Tests\Feature\Pulse;

use App\Jobs\EnrichPost;
use App\Models\Community;
use App\Models\CommunityMembership;
use App\Models\PulsePost;
use App\Models\PulseProfile;
use App\Models\PulseReaction;
use App\Models\Team;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;

class PostTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    protected function setUp(): void
    {
        parent::setUp();
        $this->user = User::factory()->withPersonalTeam()->create();
        PulseProfile::factory()->create(['user_id' => $this->user->id]);
    }

    public function test_post_requires_authentication(): void
    {
        $this->getJson('/api/v1/feed')->assertUnauthorized();
    }

    public function test_feed_returns_published_posts_only(): void
    {
        PulsePost::factory()->create(['user_id' => $this->user->id, 'status' => 'published']);
        PulsePost::factory()->create(['user_id' => $this->user->id, 'status' => 'pending']);

        $this->actingAs($this->user)
            ->getJson('/api/v1/feed')
            ->assertOk()
            ->assertJsonCount(1, 'data');
    }

    public function test_authenticated_user_can_create_post(): void
    {
        Queue::fake();

        $this->actingAs($this->user)
            ->postJson('/api/v1/posts', [
                'type' => 'discussion',
                'body' => 'This is a test discussion post body that meets minimum length.',
            ])
            ->assertCreated()
            ->assertJsonPath('status', 'pending');

        Queue::assertPushed(EnrichPost::class);
        $this->assertDatabaseHas('pulse_posts', ['user_id' => $this->user->id, 'status' => 'pending']);
    }

    public function test_post_creation_awards_points(): void
    {
        Queue::fake();

        $before = $this->user->pulseProfile->community_points;

        $this->actingAs($this->user)
            ->postJson('/api/v1/posts', [
                'type' => 'idea',
                'body' => 'A great idea for the platform that I wanted to share.',
            ])
            ->assertCreated();

        $this->assertEquals($before + 5, $this->user->pulseProfile->fresh()->community_points);
    }

    public function test_post_create_validates_body_length(): void
    {
        Queue::fake();

        $this->actingAs($this->user)
            ->postJson('/api/v1/posts', ['type' => 'discussion', 'body' => 'short'])
            ->assertUnprocessable();
    }

    public function test_user_can_delete_own_post(): void
    {
        $post = PulsePost::factory()->create(['user_id' => $this->user->id, 'status' => 'published']);

        $this->actingAs($this->user)
            ->deleteJson("/api/v1/posts/{$post->id}")
            ->assertOk();

        $this->assertDatabaseMissing('pulse_posts', ['id' => $post->id]);
    }

    public function test_user_cannot_delete_another_users_post(): void
    {
        $other = User::factory()->withPersonalTeam()->create();
        $post  = PulsePost::factory()->create(['user_id' => $other->id, 'status' => 'published']);

        $this->actingAs($this->user)
            ->deleteJson("/api/v1/posts/{$post->id}")
            ->assertForbidden();
    }

    public function test_user_can_react_to_post(): void
    {
        $post = PulsePost::factory()->create(['user_id' => $this->user->id, 'status' => 'published']);

        $this->actingAs($this->user)
            ->postJson("/api/v1/posts/{$post->id}/react")
            ->assertOk()
            ->assertJsonPath('reacted', true);

        $this->assertDatabaseHas('pulse_reactions', [
            'user_id'        => $this->user->id,
            'reactable_type' => PulsePost::class,
            'reactable_id'   => $post->id,
        ]);
    }

    public function test_reacting_twice_removes_reaction(): void
    {
        $post = PulsePost::factory()->create(['user_id' => $this->user->id, 'status' => 'published']);

        $this->actingAs($this->user)->postJson("/api/v1/posts/{$post->id}/react")->assertOk();
        $this->actingAs($this->user)->postJson("/api/v1/posts/{$post->id}/react")
            ->assertOk()
            ->assertJsonPath('reacted', false);

        $this->assertDatabaseMissing('pulse_reactions', ['reactable_id' => $post->id]);
    }

    public function test_user_can_report_post(): void
    {
        $post = PulsePost::factory()->create(['user_id' => $this->user->id, 'status' => 'published']);

        $this->actingAs($this->user)
            ->postJson("/api/v1/posts/{$post->id}/report", ['reason' => 'spam'])
            ->assertCreated();

        $this->assertDatabaseHas('pulse_reports', [
            'reporter_id'    => $this->user->id,
            'reportable_type'=> PulsePost::class,
            'reportable_id'  => $post->id,
            'status'         => 'open',
        ]);
    }

    public function test_enrich_post_job_dispatches_on_create(): void
    {
        Queue::fake();

        $this->actingAs($this->user)
            ->postJson('/api/v1/posts', [
                'type' => 'question',
                'body' => 'How do I integrate Dot.Agents with my existing ERP system in production?',
            ])
            ->assertCreated();

        Queue::assertPushed(EnrichPost::class, fn ($job) => true);
    }
}
