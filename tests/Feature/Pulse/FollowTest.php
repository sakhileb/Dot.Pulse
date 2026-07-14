<?php

namespace Tests\Feature\Pulse;

use App\Models\PulseFollower;
use App\Models\PulseProfile;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class FollowTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_can_follow_another_user(): void
    {
        $follower  = User::factory()->withPersonalTeam()->create();
        $following = User::factory()->withPersonalTeam()->create();
        PulseProfile::factory()->create(['user_id' => $follower->id]);
        PulseProfile::factory()->create(['user_id' => $following->id]);

        $this->actingAs($follower)
            ->postJson("/api/v1/users/{$following->id}/follow")
            ->assertOk()
            ->assertJsonPath('following', true);

        $this->assertDatabaseHas('pulse_followers', [
            'follower_id'  => $follower->id,
            'following_id' => $following->id,
        ]);
    }

    public function test_following_again_unfollows(): void
    {
        $follower  = User::factory()->withPersonalTeam()->create();
        $following = User::factory()->withPersonalTeam()->create();
        PulseProfile::factory()->create(['user_id' => $follower->id]);
        PulseProfile::factory()->create(['user_id' => $following->id]);

        $this->actingAs($follower)->postJson("/api/v1/users/{$following->id}/follow");
        $this->actingAs($follower)->postJson("/api/v1/users/{$following->id}/follow")
            ->assertOk()
            ->assertJsonPath('following', false);

        $this->assertDatabaseMissing('pulse_followers', ['follower_id' => $follower->id]);
    }

    public function test_follow_sends_notification_to_followed_user(): void
    {
        Notification::fake();

        $follower  = User::factory()->withPersonalTeam()->create();
        $following = User::factory()->withPersonalTeam()->create();
        PulseProfile::factory()->create(['user_id' => $follower->id]);
        PulseProfile::factory()->create(['user_id' => $following->id]);

        $this->actingAs($follower)->postJson("/api/v1/users/{$following->id}/follow");

        Notification::assertSentTo($following, \App\Notifications\NewFollower::class);
    }

    public function test_user_cannot_follow_themselves(): void
    {
        $user = User::factory()->withPersonalTeam()->create();
        PulseProfile::factory()->create(['user_id' => $user->id]);

        $this->actingAs($user)
            ->postJson("/api/v1/users/{$user->id}/follow")
            ->assertOk()
            ->assertJsonPath('following', false);

        $this->assertDatabaseMissing('pulse_followers', ['follower_id' => $user->id, 'following_id' => $user->id]);
    }
}
