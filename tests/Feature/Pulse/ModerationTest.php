<?php

namespace Tests\Feature\Pulse;

use App\Models\PulsePost;
use App\Models\PulsePostEnrichment;
use App\Models\PulseProfile;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ModerationTest extends TestCase
{
    use RefreshDatabase;

    private User $moderator;
    private User $regularUser;

    protected function setUp(): void
    {
        parent::setUp();

        $this->moderator = User::factory()->withPersonalTeam()->create();
        PulseProfile::factory()->create(['user_id' => $this->moderator->id, 'role' => 'moderator']);

        $this->regularUser = User::factory()->withPersonalTeam()->create();
        PulseProfile::factory()->create(['user_id' => $this->regularUser->id, 'role' => 'customer']);
    }

    public function test_moderation_page_requires_moderator_role(): void
    {
        $this->actingAs($this->regularUser)
            ->get('/moderation')
            ->assertForbidden();
    }

    public function test_moderator_can_access_moderation_page(): void
    {
        $this->actingAs($this->moderator)
            ->get('/moderation')
            ->assertOk();
    }

    public function test_moderator_can_delete_any_post(): void
    {
        $post = PulsePost::factory()->create(['user_id' => $this->regularUser->id, 'status' => 'published']);

        $this->actingAs($this->moderator)
            ->deleteJson("/api/v1/posts/{$post->id}")
            ->assertOk();

        $this->assertDatabaseMissing('pulse_posts', ['id' => $post->id]);
    }

    public function test_enrich_post_job_falls_back_to_published_on_failure(): void
    {
        $post = PulsePost::factory()->create(['user_id' => $this->regularUser->id, 'status' => 'pending']);

        $job = new \App\Jobs\EnrichPost($post);
        $job->failed(new \Exception('API unavailable'));

        $this->assertEquals('published', $post->fresh()->status);
    }

    public function test_report_content_is_idempotent(): void
    {
        $post = PulsePost::factory()->create(['user_id' => $this->regularUser->id, 'status' => 'published']);

        $this->actingAs($this->regularUser)
            ->postJson("/api/v1/posts/{$post->id}/report", ['reason' => 'spam'])
            ->assertCreated();

        $this->actingAs($this->regularUser)
            ->postJson("/api/v1/posts/{$post->id}/report", ['reason' => 'spam'])
            ->assertCreated();

        // Should still be only one report
        $this->assertDatabaseCount('pulse_reports', 1);
    }
}
