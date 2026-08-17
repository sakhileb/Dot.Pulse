<?php

namespace Tests\Feature\Pulse;

use App\Models\PulseHashtag;
use App\Models\PulsePost;
use App\Models\User;
use App\Services\HashtagExtractionService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * PulseHashtag/pulse_post_hashtag has existed since the platform's first
 * migration with nothing ever creating a row — README advertises
 * "trending topics" but there was no relational hashtag concept in use
 * anywhere (distinct from PulsePostEnrichment.tags, Claude's own JSON
 * topic classification).
 */
class HashtagExtractionServiceTest extends TestCase
{
    use RefreshDatabase;

    private function makePost(string $body, ?string $title = null): PulsePost
    {
        $user = User::factory()->withPersonalTeam()->create();

        return PulsePost::create([
            'user_id' => $user->id,
            'team_id' => $user->currentTeam->id,
            'type' => 'discussion',
            'title' => $title,
            'body' => $body,
            'status' => 'published',
        ]);
    }

    public function test_it_extracts_hashtags_from_the_body_and_attaches_them(): void
    {
        $post = $this->makePost('Loving #DotAgents lately, especially the #buildinpublic energy.');

        (new HashtagExtractionService)->extractAndAttach($post);

        $this->assertDatabaseHas('pulse_hashtags', ['name' => 'dotagents']);
        $this->assertDatabaseHas('pulse_hashtags', ['name' => 'buildinpublic']);
        $this->assertCount(2, $post->hashtags()->get());
    }

    public function test_it_extracts_hashtags_from_the_title_too(): void
    {
        $post = $this->makePost('No tags here.', '#launchday is finally here');

        (new HashtagExtractionService)->extractAndAttach($post);

        $this->assertDatabaseHas('pulse_hashtags', ['name' => 'launchday']);
    }

    public function test_it_reuses_an_existing_hashtag_and_increments_its_count(): void
    {
        $existing = PulseHashtag::create(['name' => 'dotagents', 'posts_count' => 3]);
        $post = $this->makePost('Another post about #DotAgents.');

        (new HashtagExtractionService)->extractAndAttach($post);

        $this->assertSame(4, $existing->fresh()->posts_count);
        $this->assertDatabaseCount('pulse_hashtags', 1);
    }

    public function test_it_does_not_double_count_if_run_twice_on_the_same_post(): void
    {
        $post = $this->makePost('Tagged with #dotagents.');
        $service = new HashtagExtractionService;

        $service->extractAndAttach($post);
        $service->extractAndAttach($post);

        $this->assertSame(1, PulseHashtag::where('name', 'dotagents')->value('posts_count'));
    }

    public function test_it_deduplicates_a_repeated_tag_within_the_same_post(): void
    {
        $post = $this->makePost('#dotagents is great. Have you tried #DotAgents yet?');

        (new HashtagExtractionService)->extractAndAttach($post);

        $this->assertSame(1, PulseHashtag::where('name', 'dotagents')->value('posts_count'));
    }

    public function test_a_post_with_no_hashtags_creates_nothing(): void
    {
        $post = $this->makePost('Just a plain post with no tags.');

        (new HashtagExtractionService)->extractAndAttach($post);

        $this->assertDatabaseCount('pulse_hashtags', 0);
    }
}
