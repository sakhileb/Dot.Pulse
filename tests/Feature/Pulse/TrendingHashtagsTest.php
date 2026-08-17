<?php

namespace Tests\Feature\Pulse;

use App\Livewire\Pulse\HomeFeed;
use App\Livewire\Pulse\TrendingHashtags;
use App\Models\PulsePost;
use App\Models\User;
use App\Services\HashtagExtractionService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class TrendingHashtagsTest extends TestCase
{
    use RefreshDatabase;

    private function publishedPostTagged(string $body, ?\DateTimeInterface $createdAt = null): PulsePost
    {
        $user = User::factory()->withPersonalTeam()->create();
        $post = PulsePost::create([
            'user_id' => $user->id,
            'team_id' => $user->currentTeam->id,
            'type' => 'discussion',
            'body' => $body,
            'status' => 'published',
        ]);

        if ($createdAt) {
            $post->forceFill(['created_at' => $createdAt])->save();
        }

        (new HashtagExtractionService)->extractAndAttach($post);

        return $post;
    }

    public function test_it_lists_hashtags_used_in_the_last_seven_days(): void
    {
        $this->publishedPostTagged('Excited about #dotagents today.');

        $viewer = User::factory()->withPersonalTeam()->create();
        $component = Livewire::actingAs($viewer)->test(TrendingHashtags::class);

        $this->assertSame('dotagents', $component->get('trending')->first()->name);
    }

    public function test_it_excludes_hashtags_only_used_more_than_seven_days_ago(): void
    {
        $this->publishedPostTagged('An old post about #stale.', now()->subDays(10));

        $viewer = User::factory()->withPersonalTeam()->create();
        $component = Livewire::actingAs($viewer)->test(TrendingHashtags::class);

        $this->assertCount(0, $component->get('trending'));
    }

    public function test_it_orders_by_recent_usage_count_descending(): void
    {
        $this->publishedPostTagged('Post one about #popular.');
        $this->publishedPostTagged('Post two also about #popular.');
        $this->publishedPostTagged('Post three about #rare.');

        $viewer = User::factory()->withPersonalTeam()->create();
        $component = Livewire::actingAs($viewer)->test(TrendingHashtags::class);

        $this->assertSame('popular', $component->get('trending')->first()->name);
    }

    public function test_filter_by_hashtag_dispatches_the_cross_component_browser_event(): void
    {
        $this->publishedPostTagged('A post about #dotagents.');
        $viewer = User::factory()->withPersonalTeam()->create();

        Livewire::actingAs($viewer)
            ->test(TrendingHashtags::class)
            ->call('filterByHashtag', 'dotagents')
            ->assertDispatched('filter-by-hashtag', name: 'dotagents');
    }

    public function test_the_feed_filters_to_the_hashtag_when_it_receives_the_browser_event(): void
    {
        $tagged = $this->publishedPostTagged('A post about #dotagents.');
        $untagged = $this->publishedPostTagged('An unrelated post.');
        $viewer = User::factory()->withPersonalTeam()->create();

        Livewire::actingAs($viewer)
            ->test(HomeFeed::class)
            ->assertSee($tagged->body)
            ->assertSee($untagged->body)
            ->dispatch('filter-by-hashtag', name: 'dotagents')
            ->assertSee($tagged->body)
            ->assertDontSee($untagged->body);
    }
}
