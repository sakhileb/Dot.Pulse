<div>
    {{-- Header + Filter --}}
    <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:1.25rem;flex-wrap:wrap;gap:0.75rem;">
        <div style="display:flex;gap:2px;">
            @foreach(['upcoming' => 'Upcoming', 'past' => 'Past'] as $key => $label)
                <button wire:click="$set('filter','{{ $key }}')" style="padding:7px 14px;font-size:13px;font-weight:600;border:none;cursor:pointer;background:none;border-bottom:2px solid {{ $filter === $key ? '#c084fc' : 'transparent' }};color:{{ $filter === $key ? '#c084fc' : '#71717a' }};transition:color .13s;">
                    {{ $label }}
                </button>
            @endforeach
        </div>
    </div>

    @if($this->events->isEmpty())
        <div style="text-align:center;padding:4rem;color:#3f3f46;">
            <span class="material-symbols-rounded" style="font-size:40px;display:block;margin-bottom:0.5rem;">event</span>
            <p style="font-size:13px;">No {{ $filter }} events.</p>
        </div>
    @else
        <div style="display:flex;flex-direction:column;gap:0.75rem;">
            @foreach($this->events as $event)
                @php
                    $typeIcons = ['webinar' => 'videocam', 'ama' => 'question_answer', 'launch' => 'rocket_launch', 'demo' => 'desktop_windows', 'training' => 'school', 'town_hall' => 'groups', 'meetup' => 'handshake'];
                    $icon = $typeIcons[$event->type] ?? 'event';
                @endphp
                <div class="dot-card" style="padding:1.25rem 1.5rem;display:flex;gap:1rem;align-items:flex-start;flex-wrap:wrap;">
                    <div style="width:48px;height:48px;border-radius:12px;background:rgba(192,132,252,0.1);border:1px solid rgba(192,132,252,0.18);display:flex;align-items:center;justify-content:center;flex-shrink:0;">
                        <span class="material-symbols-rounded" style="font-size:22px;color:#c084fc;">{{ $icon }}</span>
                    </div>
                    <div style="flex:1;min-width:0;">
                        <div style="display:flex;align-items:center;gap:8px;flex-wrap:wrap;margin-bottom:4px;">
                            <span style="font-size:11px;font-weight:600;padding:2px 8px;border-radius:100px;background:rgba(192,132,252,0.1);color:#c084fc;">{{ str_replace('_', ' ', ucfirst($event->type)) }}</span>
                            @if($event->community)
                                <span style="font-size:11px;color:#52525b;">{{ $event->community->name }}</span>
                            @endif
                        </div>
                        <div style="font-size:14px;font-weight:600;color:#f4f4f5;margin-bottom:4px;">{{ $event->title }}</div>
                        @if($event->description)
                            <p style="font-size:13px;color:#71717a;margin:0 0 0.6rem;overflow:hidden;display:-webkit-box;-webkit-line-clamp:2;-webkit-box-orient:vertical;">{{ $event->description }}</p>
                        @endif
                        <div style="display:flex;align-items:center;gap:16px;font-size:12px;color:#52525b;flex-wrap:wrap;">
                            <span style="display:flex;align-items:center;gap:4px;">
                                <span class="material-symbols-rounded" style="font-size:13px;">schedule</span>
                                {{ $event->starts_at->format('D, M j · g:i A') }}
                            </span>
                            <span style="display:flex;align-items:center;gap:4px;">
                                <span class="material-symbols-rounded" style="font-size:13px;">person</span>
                                {{ $event->organiser->name }}
                            </span>
                            <span style="display:flex;align-items:center;gap:4px;">
                                <span class="material-symbols-rounded" style="font-size:13px;">people</span>
                                {{ $event->rsvps_count }} RSVPs
                            </span>
                        </div>
                    </div>
                    @if($filter === 'upcoming')
                        <div style="display:flex;flex-direction:column;gap:5px;flex-shrink:0;">
                            <button wire:click="rsvp({{ $event->id }}, 'going')" class="dot-btn dot-btn-primary" style="font-size:12px;">Going</button>
                            <button wire:click="rsvp({{ $event->id }}, 'maybe')" class="dot-btn dot-btn-ghost" style="font-size:12px;">Maybe</button>
                        </div>
                    @endif
                </div>
            @endforeach
        </div>
    @endif
</div>
