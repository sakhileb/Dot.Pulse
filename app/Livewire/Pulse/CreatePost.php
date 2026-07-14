<?php

namespace App\Livewire\Pulse;

use App\Jobs\EnrichPost;
use App\Models\Community;
use App\Models\PulsePost;
use App\Models\PulseProfile;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\RateLimiter;
use Livewire\Attributes\Computed;
use Livewire\Component;
use Livewire\WithFileUploads;
use Illuminate\Support\Facades\Auth;

class CreatePost extends Component
{
    use WithFileUploads;

    public string $type        = 'discussion';
    public string $title       = '';
    public string $body        = '';
    public ?int   $communityId = null;
    public bool   $showForm    = false;
    public mixed  $attachment  = null;

    protected array $rules = [
        'type'        => 'required|string|in:discussion,announcement,question,idea,bug_report,release,success_story,showcase,tutorial,agent,integration,event,article,poll,video,job,marketplace',
        'title'       => 'nullable|string|max:200',
        'body'        => 'required|string|min:10|max:10000',
        'communityId' => 'nullable|exists:communities,id',
        'attachment'  => 'nullable|file|max:10240|mimes:jpg,jpeg,png,gif,webp,pdf,mp4',
    ];

    #[Computed]
    public function communities(): Collection
    {
        return Community::where('visibility', 'public')
            ->orWhere(fn ($q) => $q->where('visibility', 'private')
                ->whereHas('memberships', fn ($q) => $q->where('user_id', Auth::id())))
            ->orderBy('name')
            ->get();
    }

    public function publish(): void
    {
        // Rate limiting
        $executed = RateLimiter::attempt(
            'pulse-post:' . Auth::id(),
            $perHour = 10,
            function () {},
        );

        if (! $executed) {
            $this->addError('body', 'You are posting too fast. Please wait a moment before creating another post.');
            return;
        }

        $this->validate();

        $mediaPath = null;
        if ($this->attachment) {
            $mediaPath = $this->attachment->store('posts', 'public');
        }

        $post = PulsePost::create([
            'user_id'      => Auth::id(),
            'community_id' => $this->communityId ?: null,
            'team_id'      => Auth::user()->currentTeam?->id,
            'type'         => $this->type,
            'title'        => $this->title ?: null,
            'body'         => $this->body,
            'status'       => 'pending',
        ]);

        // Award 5 points for creating a post
        $profile = PulseProfile::where('user_id', Auth::id())->first();
        $profile?->addPoints(5);

        // Dispatch AI enrichment job (async)
        EnrichPost::dispatch($post);

        $this->reset(['type', 'title', 'body', 'communityId', 'showForm', 'attachment']);
        $this->dispatch('post-created');
    }

    public function render(): \Illuminate\View\View
    {
        return view('livewire.pulse.create-post');
    }
}

