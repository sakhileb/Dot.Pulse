<?php

namespace App\Console\Commands;

use App\Models\PulseHashtag;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class RecalculateTrending extends Command
{
    protected $signature   = 'pulse:trending';
    protected $description = 'Recalculate trending hashtag post counts and bust cache.';

    public function handle(): int
    {
        $this->info('Recalculating trending hashtags…');

        $counts = DB::table('pulse_post_hashtag')
            ->join('pulse_posts', 'pulse_post_hashtag.pulse_post_id', '=', 'pulse_posts.id')
            ->where('pulse_posts.status', 'published')
            ->select('pulse_post_hashtag.pulse_hashtag_id', DB::raw('count(*) as cnt'))
            ->groupBy('pulse_post_hashtag.pulse_hashtag_id')
            ->pluck('cnt', 'pulse_hashtag_id');

        foreach ($counts as $id => $count) {
            PulseHashtag::where('id', $id)->update(['posts_count' => $count]);
        }

        Cache::forget('pulse:trending_hashtags');

        $this->info('Done. Updated ' . $counts->count() . ' hashtags.');

        return self::SUCCESS;
    }
}
