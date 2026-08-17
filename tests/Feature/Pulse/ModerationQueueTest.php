<?php

namespace Tests\Feature\Pulse;

use App\Events\PostPublished;
use App\Livewire\Pulse\ModerationQueue;
use App\Models\PulsePost;
use App\Models\PulsePostEnrichment;
use App\Models\PulseProfile;
use App\Models\User;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Livewire\Livewire;
use Tests\TestCase;

class ModerationQueueTest extends TestCase
{
    use RefreshDatabase;

    private function heldPost(string $moderationStatus = 'rejected', string $body = 'A post awaiting moderation.'): PulsePost
    {
        $author = User::factory()->withPersonalTeam()->create();
        $post = PulsePost::create([
            'user_id' => $author->id,
            'team_id' => $author->currentTeam->id,
            'type' => 'discussion',
            'body' => $body,
            'status' => 'flagged',
        ]);
        PulsePostEnrichment::create([
            'pulse_post_id' => $post->id,
            'moderation_status' => $moderationStatus,
            'moderation_rationale' => 'Test rationale.',
        ]);

        return $post;
    }

    private function moderator(): User
    {
        $moderator = User::factory()->withPersonalTeam()->create();
        PulseProfile::create(['user_id' => $moderator->id, 'role' => 'moderator']);

        return $moderator;
    }

    public function test_moderator_can_approve_a_held_post(): void
    {
        Event::fake([PostPublished::class]);

        $post = $this->heldPost(body: 'A post about #dotagents awaiting moderation.');
        $moderator = $this->moderator();

        Livewire::actingAs($moderator)
            ->test(ModerationQueue::class)
            ->call('approve', $post->id);

        $this->assertSame('published', $post->fresh()->status);
        $this->assertDatabaseHas('pulse_moderation_logs', [
            'target_type' => PulsePost::class,
            'target_id' => $post->id,
            'action' => 'approve',
            'is_ai_decision' => false,
            'moderator_id' => $moderator->id,
        ]);
        Event::assertDispatched(PostPublished::class, fn ($event) => $event->post->is($post));
        $this->assertDatabaseHas('pulse_hashtags', ['name' => 'dotagents']);
    }

    public function test_moderator_can_reject_with_a_reason(): void
    {
        $post = $this->heldPost();
        $moderator = $this->moderator();

        Livewire::actingAs($moderator)
            ->test(ModerationQueue::class)
            ->call('promptReject', $post->id)
            ->set('rejectReason', 'Confirmed spam.')
            ->call('confirmReject', $post->id);

        $this->assertSame('removed', $post->fresh()->status);
        $this->assertDatabaseHas('pulse_moderation_logs', [
            'target_type' => PulsePost::class,
            'target_id' => $post->id,
            'action' => 'reject',
            'rationale' => 'Confirmed spam.',
            'is_ai_decision' => false,
            'moderator_id' => $moderator->id,
        ]);
    }

    public function test_rejecting_without_a_reason_is_a_noop(): void
    {
        $post = $this->heldPost();
        $moderator = $this->moderator();

        Livewire::actingAs($moderator)
            ->test(ModerationQueue::class)
            ->call('promptReject', $post->id)
            ->set('rejectReason', '')
            ->call('confirmReject', $post->id);

        $this->assertSame('flagged', $post->fresh()->status);
    }

    public function test_acting_on_a_post_no_longer_held_is_a_noop(): void
    {
        $post = $this->heldPost();
        $moderator = $this->moderator();
        $post->update(['status' => 'published']); // already resolved by someone else

        Livewire::actingAs($moderator)
            ->test(ModerationQueue::class)
            ->call('approve', $post->id);

        $this->assertSame('published', $post->fresh()->status);
        $this->assertDatabaseCount('pulse_moderation_logs', 0);
    }

    public function test_non_moderator_is_blocked(): void
    {
        $this->heldPost();
        $regularUser = User::factory()->withPersonalTeam()->create();
        PulseProfile::create(['user_id' => $regularUser->id, 'role' => 'customer']);

        $this->withoutExceptionHandling();
        $this->expectException(AuthorizationException::class);

        Livewire::actingAs($regularUser)->test(ModerationQueue::class);
    }

    public function test_moderation_route_requires_authentication(): void
    {
        $this->get('/moderation')->assertRedirect('/login');
    }
}
