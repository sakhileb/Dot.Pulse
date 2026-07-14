<?php

namespace Tests\Feature\Pulse;

use App\Models\PulseComment;
use App\Models\PulsePost;
use App\Models\PulseProfile;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class CommentTest extends TestCase
{
    use RefreshDatabase;

    private User $user;
    private PulsePost $post;

    protected function setUp(): void
    {
        parent::setUp();
        $this->user = User::factory()->withPersonalTeam()->create();
        PulseProfile::factory()->create(['user_id' => $this->user->id]);
        $this->post = PulsePost::factory()->create([
            'user_id' => $this->user->id,
            'status'  => 'published',
        ]);
    }

    public function test_authenticated_user_can_add_comment(): void
    {
        $commenter = User::factory()->withPersonalTeam()->create();
        PulseProfile::factory()->create(['user_id' => $commenter->id]);

        $this->actingAs($commenter)
            ->postJson("/api/v1/posts/{$this->post->id}/comments", ['body' => 'Great post, very insightful!'])
            ->assertCreated();

        $this->assertDatabaseHas('pulse_comments', [
            'pulse_post_id' => $this->post->id,
            'user_id'       => $commenter->id,
        ]);
    }

    public function test_comment_increments_post_count(): void
    {
        $commenter = User::factory()->withPersonalTeam()->create();
        PulseProfile::factory()->create(['user_id' => $commenter->id]);

        $before = $this->post->comments_count;

        $this->actingAs($commenter)
            ->postJson("/api/v1/posts/{$this->post->id}/comments", ['body' => 'Interesting perspective on this topic.'])
            ->assertCreated();

        $this->assertEquals($before + 1, $this->post->fresh()->comments_count);
    }

    public function test_comment_awards_points_to_commenter(): void
    {
        $commenter = User::factory()->withPersonalTeam()->create();
        $profile   = PulseProfile::factory()->create(['user_id' => $commenter->id, 'community_points' => 0]);

        $this->actingAs($commenter)
            ->postJson("/api/v1/posts/{$this->post->id}/comments", ['body' => 'This helped me a lot, thank you!'])
            ->assertCreated();

        $this->assertEquals(2, $profile->fresh()->community_points);
    }

    public function test_comment_notifies_post_author(): void
    {
        Notification::fake();

        $commenter = User::factory()->withPersonalTeam()->create();
        PulseProfile::factory()->create(['user_id' => $commenter->id]);

        $this->actingAs($commenter)
            ->postJson("/api/v1/posts/{$this->post->id}/comments", ['body' => 'Well explained, thanks for sharing this!'])
            ->assertCreated();

        Notification::assertSentTo($this->user, \App\Notifications\NewCommentOnPost::class);
    }

    public function test_comment_does_not_notify_self(): void
    {
        Notification::fake();

        $this->actingAs($this->user)
            ->postJson("/api/v1/posts/{$this->post->id}/comments", ['body' => 'Adding my own note to this post for context.'])
            ->assertCreated();

        Notification::assertNotSentTo($this->user, \App\Notifications\NewCommentOnPost::class);
    }

    public function test_author_can_delete_own_comment(): void
    {
        $comment = PulseComment::factory()->create([
            'pulse_post_id' => $this->post->id,
            'user_id'       => $this->user->id,
        ]);

        $this->actingAs($this->user)
            ->deleteJson("/api/v1/comments/{$comment->id}")
            ->assertOk();

        $this->assertDatabaseMissing('pulse_comments', ['id' => $comment->id]);
    }

    public function test_user_cannot_delete_another_users_comment(): void
    {
        // Use a third user who is neither the comment author nor the post author
        $commenter   = User::factory()->withPersonalTeam()->create();
        $uninvolved  = User::factory()->withPersonalTeam()->create();
        PulseProfile::factory()->create(['user_id' => $commenter->id]);
        PulseProfile::factory()->create(['user_id' => $uninvolved->id]);

        $comment = PulseComment::factory()->create([
            'pulse_post_id' => $this->post->id,
            'user_id'       => $commenter->id,
        ]);

        $this->actingAs($uninvolved)
            ->deleteJson("/api/v1/comments/{$comment->id}")
            ->assertForbidden();
    }

    public function test_mention_in_comment_sends_notification(): void
    {
        Notification::fake();

        $mentioned = User::factory()->withPersonalTeam()->create(['name' => 'JohnDoe']);
        PulseProfile::factory()->create(['user_id' => $mentioned->id]);

        $commenter = User::factory()->withPersonalTeam()->create();
        PulseProfile::factory()->create(['user_id' => $commenter->id]);

        $this->actingAs($commenter)
            ->postJson("/api/v1/posts/{$this->post->id}/comments", [
                'body' => 'Hey @JohnDoe check this out, it might help you!',
            ])
            ->assertCreated();

        Notification::assertSentTo($mentioned, \App\Notifications\MentionNotification::class);
    }
}
