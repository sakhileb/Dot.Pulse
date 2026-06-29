<?php

namespace App\Livewire\Pulse;

use App\Models\Community;
use App\Models\PulsePost;
use App\Services\AiModerationService;
use Illuminate\Support\Collection;
use Livewire\Attributes\Computed;
use Livewire\Component;

class CreatePost extends Component
{
    public string $type = 'discussion';
    public string $title = '';
    public string $body = '';
    public ?int $communityId = null;
    public bool $showForm = false;

    protected array $rules = [
        'type'        => 'required|string|in:discussion,announcement,question,idea,bug_report,release,success_story,showcase,tutorial,agent,integration,event,article,poll,video,job,marketplace',
        'title'       => 'nullable|string|max:200',
        'body'        => 'required|string|min:10|max:10000',
        'communityId' => 'nullable|exists:communities,id',
    ];

    #[Computed]
    public function communities(): Collection
    {
        return Community::where('visibility', 'public')
            ->orWhere(fn ($q) => $q->where('visibility', 'private')
                ->whereHas('memberships', fn ($q) => $q->where('user_id', auth()->id())))
            ->orderBy('name')
            ->get();
    }

    public function publish(): void
    {
        $this->validate();

        $post = PulsePost::create([
            'user_id'      => auth()->id(),
            'community_id' => $this->communityId ?: null,
            'team_id'      => auth()->user()->currentTeam->id,
            'type'         => $this->type,
            'title'        => $this->title ?: null,
            'body'         => $this->body,
            'status'       => 'pending',
        ]);

        $service = new AiModerationService(
            apiKey: config('services.anthropic.key', ''),
        );
        $service->enrichPost($post);

        $this->reset(['type', 'title', 'body', 'communityId', 'showForm']);
        $this->dispatch('post-created');
    }

    public function render(): \Illuminate\View\View
    {
        return view('livewire.pulse.create-post');
    }
}
