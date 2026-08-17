<?php

namespace Tests\Feature\Pulse;

use App\Livewire\Pulse\HomeFeed;
use App\Livewire\Pulse\UserProfile;
use App\Models\PulsePost;
use App\Models\PulseProfile;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

/**
 * Follow graph — pulse_followers has existed since the platform's first
 * migration (unique(follower_id, following_id)) with no relation ever
 * declared on User and no UI reading or writing it. README advertises
 * "follow users and communities" but nothing let a user follow anyone.
 */
class UserProfileTest extends TestCase
{
    use RefreshDatabase;

    public function test_a_profile_page_is_viewable(): void
    {
        $profileUser = User::factory()->withPersonalTeam()->create();
        $viewer = User::factory()->withPersonalTeam()->create();

        $this->actingAs($viewer)
            ->get("/u/{$profileUser->id}")
            ->assertOk();
    }

    public function test_follower_and_following_counts_are_accurate(): void
    {
        $target = User::factory()->withPersonalTeam()->create();
        $followerOne = User::factory()->withPersonalTeam()->create();
        $followerTwo = User::factory()->withPersonalTeam()->create();

        $followerOne->following()->attach($target->id);
        $followerTwo->following()->attach($target->id);

        $component = Livewire::actingAs($followerOne)->test(UserProfile::class, ['profileUser' => $target]);

        $this->assertSame(2, $component->get('followersCount'));
    }

    public function test_follower_count_refreshes_when_it_receives_the_follow_toggled_event(): void
    {
        $target = User::factory()->withPersonalTeam()->create();
        $viewer = User::factory()->withPersonalTeam()->create();

        $component = Livewire::actingAs($viewer)
            ->test(UserProfile::class, ['profileUser' => $target])
            ->assertSet('followersCount', 0);

        $viewer->following()->attach($target->id);

        $component->dispatch('follow-toggled', userId: $target->id)
            ->assertSet('followersCount', 1);
    }

    public function test_profile_shows_only_published_posts(): void
    {
        $author = User::factory()->withPersonalTeam()->create();
        PulseProfile::create(['user_id' => $author->id, 'headline' => 'Fleet ops lead']);

        PulsePost::create([
            'user_id' => $author->id, 'team_id' => $author->currentTeam->id,
            'type' => 'discussion', 'body' => 'Published post', 'status' => 'published',
        ]);
        PulsePost::create([
            'user_id' => $author->id, 'team_id' => $author->currentTeam->id,
            'type' => 'discussion', 'body' => 'Still pending', 'status' => 'pending',
        ]);

        $viewer = User::factory()->withPersonalTeam()->create();
        $component = Livewire::actingAs($viewer)->test(UserProfile::class, ['profileUser' => $author]);

        $this->assertCount(1, $component->get('posts'));
    }

    public function test_the_following_only_feed_filter_only_shows_followed_authors(): void
    {
        $followed = User::factory()->withPersonalTeam()->create();
        $notFollowed = User::factory()->withPersonalTeam()->create();
        $viewer = User::factory()->withPersonalTeam()->create();
        $viewer->following()->attach($followed->id);

        PulsePost::create([
            'user_id' => $followed->id, 'team_id' => $followed->currentTeam->id,
            'type' => 'discussion', 'body' => 'From someone I follow', 'status' => 'published',
        ]);
        PulsePost::create([
            'user_id' => $notFollowed->id, 'team_id' => $notFollowed->currentTeam->id,
            'type' => 'discussion', 'body' => 'From a stranger', 'status' => 'published',
        ]);

        Livewire::actingAs($viewer)
            ->test(HomeFeed::class)
            ->set('followingOnly', true)
            ->assertSee('From someone I follow')
            ->assertDontSee('From a stranger');
    }
}
