<?php

namespace Tests\Feature\Pulse;

use App\Livewire\Pulse\HomeFeed;
use App\Models\PulsePost;
use App\Models\PulseProfile;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class HomeFeedTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    protected function setUp(): void
    {
        parent::setUp();
        $this->user = User::factory()->withPersonalTeam()->create();
        PulseProfile::factory()->create(['user_id' => $this->user->id]);
    }

    public function test_dashboard_feed_requires_authentication(): void
    {
        $this->get('/dashboard')->assertRedirect('/login');
    }

    public function test_authenticated_user_sees_empty_feed_state(): void
    {
        $this->actingAs($this->user)
            ->get('/dashboard')
            ->assertOk()
            ->assertSeeLivewire('pulse.home-feed');

        Livewire::test(HomeFeed::class)
            ->assertSee('No posts yet');
    }

    public function test_authenticated_user_sees_published_posts_in_feed(): void
    {
        PulsePost::factory()->create([
            'user_id' => $this->user->id,
            'team_id' => $this->user->currentTeam->id,
            'type' => 'discussion',
            'body' => 'A published discussion post visible in the feed.',
            'status' => 'published',
        ]);

        PulsePost::factory()->create([
            'user_id' => $this->user->id,
            'team_id' => $this->user->currentTeam->id,
            'type' => 'discussion',
            'body' => 'A pending post that should not appear yet.',
            'status' => 'pending',
        ]);

        Livewire::test(HomeFeed::class)
            ->assertSee('A published discussion post visible in the feed.')
            ->assertDontSee('A pending post that should not appear yet.');
    }

    public function test_feed_can_be_filtered_by_type(): void
    {
        PulsePost::factory()->create([
            'user_id' => $this->user->id,
            'team_id' => $this->user->currentTeam->id,
            'type' => 'question',
            'body' => 'A question post about the platform.',
            'status' => 'published',
        ]);

        PulsePost::factory()->create([
            'user_id' => $this->user->id,
            'team_id' => $this->user->currentTeam->id,
            'type' => 'idea',
            'body' => 'An idea post about the platform.',
            'status' => 'published',
        ]);

        Livewire::test(HomeFeed::class)
            ->set('filterType', 'question')
            ->assertSee('A question post about the platform.')
            ->assertDontSee('An idea post about the platform.');
    }

    public function test_dashboard_shows_kpi_totals(): void
    {
        PulsePost::factory()->create([
            'user_id' => $this->user->id,
            'team_id' => $this->user->currentTeam->id,
            'status' => 'published',
        ]);

        $this->actingAs($this->user)
            ->get('/dashboard')
            ->assertOk()
            ->assertViewHas('totalPosts', 1);
    }
}
