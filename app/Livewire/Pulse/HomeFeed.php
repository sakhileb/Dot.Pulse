<?php

namespace App\Livewire\Pulse;

use App\Models\PulsePost;
use Illuminate\Support\Collection;
use Livewire\Attributes\Computed;
use Livewire\Attributes\On;
use Livewire\Component;

class HomeFeed extends Component
{
    public string $filterType = '';

    #[Computed]
    public function posts(): Collection
    {
        return PulsePost::with(['author', 'community', 'enrichment'])
            ->published()
            ->when($this->filterType, fn ($q) => $q->where('type', $this->filterType))
            ->orderByDesc('ai_relevance_score')
            ->orderByDesc('created_at')
            ->limit(30)
            ->get();
    }

    #[On('post-created')]
    public function refresh(): void
    {
        unset($this->posts);
    }

    public function react(int $postId, string $emoji = '👍'): void
    {
        $user = auth()->user();
        $post = PulsePost::findOrFail($postId);

        $existing = $post->reactions()->where('user_id', $user->id)->first();

        if ($existing) {
            $existing->delete();
            $post->decrement('reactions_count');
        } else {
            $post->reactions()->create(['user_id' => $user->id, 'emoji' => $emoji]);
            $post->increment('reactions_count');
        }

        unset($this->posts);
    }

    public function render(): \Illuminate\View\View
    {
        return view('livewire.pulse.home-feed');
    }
}
