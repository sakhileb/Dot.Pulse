<?php

namespace Tests\Feature\Pulse;

use App\Models\PulsePost;
use App\Models\User;
use App\Services\AiModerationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AiModerationServiceTest extends TestCase
{
    use RefreshDatabase;

    private function pendingPost(): PulsePost
    {
        $user = User::factory()->withPersonalTeam()->create();

        return PulsePost::create([
            'user_id' => $user->id,
            'team_id' => $user->currentTeam->id,
            'type' => 'discussion',
            'body' => 'A post awaiting moderation.',
            'status' => 'pending',
        ]);
    }

    public function test_approved_classification_still_auto_publishes(): void
    {
        $post = $this->pendingPost();
        $service = new AiModerationService(mock: true);

        $service->upsertEnrichment($post, [
            'moderation_status' => 'approved',
            'moderation_rationale' => null,
        ]);

        $this->assertSame('published', $post->fresh()->status);
        $this->assertDatabaseHas('pulse_moderation_logs', [
            'target_type' => PulsePost::class,
            'target_id' => $post->id,
            'action' => 'approve',
            'is_ai_decision' => true,
            'moderator_id' => null,
        ]);
    }

    public function test_rejected_classification_holds_for_review_instead_of_removing(): void
    {
        $post = $this->pendingPost();
        $service = new AiModerationService(mock: true);

        $service->upsertEnrichment($post, [
            'moderation_status' => 'rejected',
            'moderation_rationale' => 'Looks like spam.',
        ]);

        $this->assertSame('flagged', $post->fresh()->status);
        $this->assertDatabaseHas('pulse_moderation_logs', [
            'target_type' => PulsePost::class,
            'target_id' => $post->id,
            'action' => 'reject',
            'rationale' => 'Looks like spam.',
            'is_ai_decision' => true,
        ]);
    }

    public function test_flagged_classification_now_holds_for_review_instead_of_being_dropped(): void
    {
        $post = $this->pendingPost();
        $service = new AiModerationService(mock: true);

        $service->upsertEnrichment($post, [
            'moderation_status' => 'flagged',
            'moderation_rationale' => 'Unsure about business relevance.',
        ]);

        $this->assertSame('flagged', $post->fresh()->status);
        $this->assertDatabaseHas('pulse_moderation_logs', [
            'target_type' => PulsePost::class,
            'target_id' => $post->id,
            'action' => 'flag',
            'is_ai_decision' => true,
        ]);
    }
}
