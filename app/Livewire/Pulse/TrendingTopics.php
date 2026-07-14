<?php

namespace App\Livewire\Pulse;

use App\Models\PulseHashtag;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Livewire\Attributes\Computed;
use Livewire\Component;

class TrendingTopics extends Component
{
    #[Computed]
    public function hashtags(): Collection
    {
        return Cache::remember('pulse:trending_hashtags', 300, fn () =>
            PulseHashtag::orderByDesc('posts_count')
                ->where('posts_count', '>', 0)
                ->limit(10)
                ->get()
        );
    }

    public function render(): \Illuminate\View\View
    {
        return view('livewire.pulse.trending-topics');
    }
}
