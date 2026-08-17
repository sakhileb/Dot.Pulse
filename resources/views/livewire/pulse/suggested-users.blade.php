<div>
    @if($this->suggestions->isEmpty())
        <p style="font-size:12.5px;color:#52525b;margin:0;">No suggestions yet — check back once more people join.</p>
    @else
        <div style="display:flex;flex-direction:column;gap:2px;">
            @foreach($this->suggestions as $person)
                <div class="dot-row" style="display:flex;align-items:center;gap:10px;padding:8px 10px;">
                    <a href="{{ route('users.show', $person) }}" class="dot-avatar" style="width:32px;height:32px;font-size:12px;">
                        {{ strtoupper(substr($person->name, 0, 1)) }}
                    </a>
                    <div style="flex:1;min-width:0;">
                        <a href="{{ route('users.show', $person) }}" style="display:block;font-size:13px;font-weight:600;color:#f4f4f5;text-decoration:none;">{{ $person->name }}</a>
                        @if($person->pulseProfile?->headline)
                            <p style="font-size:11.5px;color:#71717a;margin:1px 0 0;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;">{{ $person->pulseProfile->headline }}</p>
                        @endif
                    </div>
                    <livewire:pulse.follow-button :user="$person" :compact="true" wire:key="follow-{{ $person->id }}-suggested" />
                </div>
            @endforeach
        </div>
    @endif
</div>
