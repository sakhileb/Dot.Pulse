<?php

namespace App\Livewire\Pulse;

use App\Actions\Pulse\CreateEvent as CreateEventAction;
use App\Models\Community;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;
use Livewire\Attributes\Computed;
use Livewire\Component;

class CreateEventForm extends Component
{
    public string $title = '';

    public string $description = '';

    public string $type = 'webinar';

    public string $startsAt = '';

    public string $endsAt = '';

    public string $url = '';

    public ?int $communityId = null;

    public static array $types = ['webinar', 'ama', 'launch', 'demo', 'training', 'town_hall', 'meetup'];

    protected function rules(): array
    {
        return [
            'title' => 'required|min:3|max:150',
            'description' => 'nullable|max:2000',
            'type' => 'required|in:webinar,ama,launch,demo,training,town_hall,meetup',
            'startsAt' => 'required|date|after:now',
            'endsAt' => 'required|date|after:startsAt',
            'url' => 'nullable|url|max:300',
            'communityId' => 'nullable|exists:communities,id',
        ];
    }

    #[Computed]
    public function communities(): Collection
    {
        return Community::where('visibility', 'public')
            ->orWhereHas('memberships', fn ($q) => $q->where('user_id', Auth::id()))
            ->orderBy('name')->get();
    }

    public function create(): void
    {
        $this->validate();

        app(CreateEventAction::class)->handle(
            userId: Auth::id(),
            title: $this->title,
            description: $this->description,
            type: $this->type,
            startsAt: $this->startsAt,
            endsAt: $this->endsAt,
            url: $this->url,
            communityId: $this->communityId,
        );

        $this->redirect(route('events.index'), navigate: true);
    }

    public function render(): View
    {
        return view('livewire.pulse.create-event-form');
    }
}
