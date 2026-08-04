<?php

namespace Tests\Feature\Pulse;

use App\Models\PulseProfile;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Regression coverage for a genuine null-currentTeam crash found in a
 * platform-loop audit pass: resources/views/navigation-menu.blade.php
 * (rendered via @livewire('navigation-menu') on every authenticated page
 * through layouts/app.blade.php) dereferenced Auth::user()->currentTeam->name
 * and ->id with no null guard. Jetstream's HasTeams::currentTeam() self-heals
 * by switching to the user's personal team when current_team_id is null —
 * but only if a personal team actually exists. A user with no personal team
 * (e.g. the default User factory state, which deliberately sets
 * current_team_id => null and does not attach an owned team — see
 * UserFactory::withPersonalTeam() as the explicit opt-in used elsewhere)
 * reaches this view with a genuinely null currentTeam, and
 * "Attempt to read property on null" is a PHP warning that Laravel's
 * HandleExceptions bootstrapper (error_reporting(-1) + set_error_handler)
 * converts into a thrown ErrorException — not a silently-ignored notice —
 * so this was a real 500 on every page for such a user, not a
 * theoretical one. Fixed with the nullsafe operator (?->) and an
 * Auth::user()->currentTeam existence check around the "Team Settings"
 * link, matching the ecosystem's Dot.Mines Dashboard.php null-guard
 * convention. Per Dot.Pulse's own prior tenant-scope pass, this is a
 * pure crash fix — no team-based read filtering was added anywhere.
 */
class NavigationMenuTest extends TestCase
{
    use RefreshDatabase;

    public function test_authenticated_user_with_no_team_can_load_dashboard_without_crashing(): void
    {
        $user = User::factory()->create(['current_team_id' => null]);
        PulseProfile::factory()->create(['user_id' => $user->id]);

        $response = $this->actingAs($user)->get('/dashboard');

        $response->assertOk();
        $response->assertDontSee('currentTeam');
    }

    public function test_authenticated_user_with_a_team_still_sees_team_name_in_dashboard(): void
    {
        $user = User::factory()->withPersonalTeam()->create();
        PulseProfile::factory()->create(['user_id' => $user->id]);

        $response = $this->actingAs($user)->get('/dashboard');

        $response->assertOk();
        $response->assertSee($user->fresh()->currentTeam->name);
    }
}
