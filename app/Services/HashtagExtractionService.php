<?php

namespace App\Services;

use App\Models\PulseHashtag;
use App\Models\PulsePost;

/**
 * PulseHashtag/pulse_post_hashtag has existed since the platform's first
 * migration -- README advertises "trending topics and discovery feed" but
 * nothing ever created a PulseHashtag row. Distinct from
 * PulsePostEnrichment.tags (Claude's own topic classification, a JSON
 * blob, not relational) -- these are literal #word tokens the author
 * typed, the same convention as Twitter/X, mapped to a real countable
 * entity so "trending" can actually be queried.
 *
 * Runs at the moment a post becomes visible in the feed (both
 * App\Events\PostPublished call sites), not at creation time -- a
 * pending/held post's hashtags shouldn't count toward what's trending in
 * a feed nobody but its author can see yet.
 */
class HashtagExtractionService
{
    private const PATTERN = '/#([a-zA-Z][a-zA-Z0-9_]{1,49})/';

    public function extractAndAttach(PulsePost $post): void
    {
        $names = $this->extractNames("{$post->title} {$post->body}");

        if (empty($names)) {
            return;
        }

        $hashtagIds = [];

        foreach ($names as $name) {
            $hashtag = PulseHashtag::firstOrCreate(['name' => $name]);
            $hashtagIds[] = $hashtag->id;
        }

        // syncWithoutDetaching so re-running this (e.g. a post edited and
        // re-published later, if that flow is ever built) never double
        // counts a tag it already attached.
        $newlyAttached = array_diff($hashtagIds, $post->hashtags()->pluck('pulse_hashtags.id')->all());
        $post->hashtags()->syncWithoutDetaching($hashtagIds);

        if (! empty($newlyAttached)) {
            PulseHashtag::whereIn('id', $newlyAttached)->increment('posts_count');
        }
    }

    /**
     * @return list<string>
     */
    private function extractNames(string $text): array
    {
        preg_match_all(self::PATTERN, $text, $matches);

        return collect($matches[1] ?? [])
            ->map(fn (string $tag) => strtolower($tag))
            ->unique()
            ->values()
            ->all();
    }
}
