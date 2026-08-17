<?php

namespace Tests\Feature\Pulse;

use App\Livewire\Pulse\SuggestedUsers;
use App\Models\PulseProfile;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class SuggestedUsersTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_excludes_the_viewer_and_orders_by_points_descending(): void
    {
        $viewer = User::factory()->withPersonalTeam()->create();

        $highPoints = User::factory()->withPersonalTeam()->create(['name' => 'High Points']);
        PulseProfile::create(['user_id' => $highPoints->id, 'community_points' => 100]);

        $lowPoints = User::factory()->withPersonalTeam()->create(['name' => 'Low Points']);
        PulseProfile::create(['user_id' => $lowPoints->id, 'community_points' => 5]);

        $component = Livewire::actingAs($viewer)->test(SuggestedUsers::class);
        $names = $component->get('suggestions')->pluck('name')->all();

        $this->assertSame(['High Points', 'Low Points'], $names);
        $this->assertNotContains($viewer->name, $names);
    }

    public function test_it_excludes_users_already_followed(): void
    {
        $viewer = User::factory()->withPersonalTeam()->create();
        $alreadyFollowed = User::factory()->withPersonalTeam()->create(['name' => 'Already Followed']);
        $viewer->following()->attach($alreadyFollowed->id);

        $component = Livewire::actingAs($viewer)->test(SuggestedUsers::class);

        $this->assertNotContains('Already Followed', $component->get('suggestions')->pluck('name')->all());
    }

    public function test_it_caps_at_five_suggestions(): void
    {
        $viewer = User::factory()->withPersonalTeam()->create();
        User::factory()->withPersonalTeam()->count(7)->create();

        $component = Livewire::actingAs($viewer)->test(SuggestedUsers::class);

        $this->assertCount(5, $component->get('suggestions'));
    }
}
