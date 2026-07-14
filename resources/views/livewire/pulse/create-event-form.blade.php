<div>
    <form wire:submit="create" style="max-width:640px;display:flex;flex-direction:column;gap:1.25rem;">
        <div>
            <label style="display:block;font-size:11px;font-weight:600;color:#71717a;text-transform:uppercase;letter-spacing:0.08em;margin-bottom:6px;">Event Title *</label>
            <input wire:model="title" type="text" placeholder="e.g. Live Product Demo: Fleet Tracker v2" class="dot-input" />
            @error('title') <p style="font-size:11px;color:#f87171;margin-top:3px;">{{ $message }}</p> @enderror
        </div>

        <div style="display:grid;grid-template-columns:1fr 1fr;gap:1rem;">
            <div>
                <label style="display:block;font-size:11px;font-weight:600;color:#71717a;text-transform:uppercase;letter-spacing:0.08em;margin-bottom:6px;">Type *</label>
                <select wire:model="type" class="dot-input">
                    @foreach(\App\Livewire\Pulse\CreateEventForm::$types as $t)
                        <option value="{{ $t }}">{{ ucfirst(str_replace('_', ' ', $t)) }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label style="display:block;font-size:11px;font-weight:600;color:#71717a;text-transform:uppercase;letter-spacing:0.08em;margin-bottom:6px;">Community (optional)</label>
                <select wire:model="communityId" class="dot-input">
                    <option value="">No community</option>
                    @foreach($this->communities as $c)
                        <option value="{{ $c->id }}">{{ $c->name }}</option>
                    @endforeach
                </select>
            </div>
        </div>

        <div style="display:grid;grid-template-columns:1fr 1fr;gap:1rem;">
            <div>
                <label style="display:block;font-size:11px;font-weight:600;color:#71717a;text-transform:uppercase;letter-spacing:0.08em;margin-bottom:6px;">Starts At *</label>
                <input wire:model="startsAt" type="datetime-local" class="dot-input" />
                @error('startsAt') <p style="font-size:11px;color:#f87171;margin-top:3px;">{{ $message }}</p> @enderror
            </div>
            <div>
                <label style="display:block;font-size:11px;font-weight:600;color:#71717a;text-transform:uppercase;letter-spacing:0.08em;margin-bottom:6px;">Ends At *</label>
                <input wire:model="endsAt" type="datetime-local" class="dot-input" />
                @error('endsAt') <p style="font-size:11px;color:#f87171;margin-top:3px;">{{ $message }}</p> @enderror
            </div>
        </div>

        <div>
            <label style="display:block;font-size:11px;font-weight:600;color:#71717a;text-transform:uppercase;letter-spacing:0.08em;margin-bottom:6px;">Registration / Stream URL</label>
            <input wire:model="url" type="url" placeholder="https://meet.google.com/…" class="dot-input" />
            @error('url') <p style="font-size:11px;color:#f87171;margin-top:3px;">{{ $message }}</p> @enderror
        </div>

        <div>
            <label style="display:block;font-size:11px;font-weight:600;color:#71717a;text-transform:uppercase;letter-spacing:0.08em;margin-bottom:6px;">Description</label>
            <textarea wire:model="description" rows="3" placeholder="What will attendees learn?" class="dot-input" style="resize:none;"></textarea>
        </div>

        <div style="display:flex;gap:0.75rem;">
            <button type="submit" class="dot-btn dot-btn-primary" wire:loading.attr="disabled">
                <span wire:loading.remove><span class="material-symbols-rounded" style="font-size:14px;">event</span> Create Event</span>
                <span wire:loading>Creating…</span>
            </button>
            <a href="{{ route('events.index') }}" class="dot-btn dot-btn-ghost" style="text-decoration:none;">Cancel</a>
        </div>
    </form>
</div>
