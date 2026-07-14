<?php

namespace App\Livewire\Pulse;

use App\Models\PulsePost;
use App\Models\PulseReaction;
use App\Notifications\PostReacted;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\RateLimiter;
use Livewire\Attributes\Computed;
use Livewire\Attributes\On;
use Livewire\Component;
use Livewire\WithPagination;

class HomeFeed extends Component
{
    use WithPagination;

    public string $filterType = '';

    #[Computed]
    public function posts()
    {
        return PulsePost::with(['author', 'community', 'enrichment'])
            ->published()
            ->when($this->filterType, fn ($q) => $q->where('type', $this->filterType))
            ->orderByDesc('ai_relevance_score')
            ->orderByDesc('created_at')
            ->paginate(20);
    }

    #[On('post-created')]
    #[On('echo:feed,PostPublished')]
    public function refresh(): void
    {
        unset($this->posts);
        $this->resetPage();
    }

    public function updatedFilterType(): void
    {
        $this->resetPage();
        unset($this->posts);
    }

    public function react(int $postId, string $emoji = '👍'): void
    {
        if (! RateLimiter::attempt('pulse-reaction:' . auth()->id(), 30, fn () => true)) {
            return;
        }

        $user = auth()->user();
        $post = PulsePost::with('author')->findOrFail($postId);

        $existing = $post->reactions()->where('user_id', $user->id)->first();

        if ($existing) {
            $existing->delete();
            $post->decrement('reactions_count');
        } else {
            $post->reactions()->create(['user_id' => $user->id, 'emoji' => $emoji]);
            $post->increment('reactions_count');

            if ($post->user_id !== $user->id) {
                $post->author->notify(new PostReacted($user, $emoji, 'post', $post->id, $post->id));
            }
        }

        unset($this->posts);
    }

    public function render(): \Illuminate\View\View
    {
        return view('livewire.pulse.home-feed');
    }
}
