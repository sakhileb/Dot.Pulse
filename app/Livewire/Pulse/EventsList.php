<?php

namespace App\Livewire\Pulse;

use App\Models\PulseEvent;
use App\Models\PulseEventRsvp;
use Illuminate\Support\Collection;
use Livewire\Attributes\Computed;
use Livewire\Component;
use Illuminate\Support\Facades\Auth;

class EventsList extends Component
{
    public string $filter = 'upcoming';

    #[Computed]
    public function events(): Collection
    {
        return PulseEvent::with(['organiser', 'community'])
            ->when(
                $this->filter === 'upcoming',
                fn ($q) => $q->where('starts_at', '>=', now())->orderBy('starts_at'),
            )
            ->when(
                $this->filter === 'past',
                fn ($q) => $q->where('starts_at', '<', now())->orderByDesc('starts_at'),
            )
            ->limit(20)
            ->get();
    }

    public function rsvp(int $eventId, string $status = 'going'): void
    {
        $event = PulseEvent::findOrFail($eventId);

        // No explicit user_id filter needed: PulseEventRsvp's HasUserScope
        // trait applies it automatically to every query against this model.
        $existing = PulseEventRsvp::where('pulse_event_id', $eventId)->first();

        if ($existing) {
            $existing->update(['status' => $status]);
        } else {
            PulseEventRsvp::create([
                'pulse_event_id' => $eventId,
                'user_id'        => Auth::id(),
                'status'         => $status,
            ]);
            $event->increment('rsvps_count');
        }

        unset($this->events);
    }

    public function render(): \Illuminate\View\View
    {
        return view('livewire.pulse.events-list');
    }
}
