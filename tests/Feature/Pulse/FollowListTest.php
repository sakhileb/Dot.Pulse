<?php

namespace Tests\Feature\Pulse;

use App\Livewire\Pulse\FollowList;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class FollowListTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_lists_a_users_followers(): void
    {
        $target = User::factory()->withPersonalTeam()->create();
        $followerOne = User::factory()->withPersonalTeam()->create(['name' => 'Ada Lovelace']);
        $followerTwo = User::factory()->withPersonalTeam()->create(['name' => 'Grace Hopper']);
        $followerOne->following()->attach($target->id);
        $followerTwo->following()->attach($target->id);

        $viewer = User::factory()->withPersonalTeam()->create();

        Livewire::actingAs($viewer)
            ->test(FollowList::class, ['user' => $target, 'mode' => 'followers'])
            ->assertSee('Ada Lovelace')
            ->assertSee('Grace Hopper');
    }

    public function test_it_lists_who_a_user_is_following(): void
    {
        $viewer = User::factory()->withPersonalTeam()->create();
        $followed = User::factory()->withPersonalTeam()->create(['name' => 'Katherine Johnson']);
        $viewer->following()->attach($followed->id);

        Livewire::actingAs($viewer)
            ->test(FollowList::class, ['user' => $viewer, 'mode' => 'following'])
            ->assertSee('Katherine Johnson');
    }

    public function test_it_shows_an_empty_state_with_no_followers(): void
    {
        $target = User::factory()->withPersonalTeam()->create();
        $viewer = User::factory()->withPersonalTeam()->create();

        Livewire::actingAs($viewer)
            ->test(FollowList::class, ['user' => $target, 'mode' => 'followers'])
            ->assertSee('No followers yet.');
    }
}
