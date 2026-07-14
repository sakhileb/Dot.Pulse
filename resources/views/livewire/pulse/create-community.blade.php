<div>
    <form wire:submit="create" style="max-width:600px;">
        <div style="display:flex;flex-direction:column;gap:1.25rem;">

            <div>
                <label style="display:block;font-size:11px;font-weight:600;color:#71717a;text-transform:uppercase;letter-spacing:0.08em;margin-bottom:6px;">Community Name *</label>
                <input wire:model="name" type="text" placeholder="e.g. Fleet Automation Users" class="dot-input" />
                @error('name') <p style="font-size:11px;color:#f87171;margin-top:4px;">{{ $message }}</p> @enderror
            </div>

            <div>
                <label style="display:block;font-size:11px;font-weight:600;color:#71717a;text-transform:uppercase;letter-spacing:0.08em;margin-bottom:6px;">Description</label>
                <textarea wire:model="description" rows="3" placeholder="What is this community about?" class="dot-input" style="resize:none;"></textarea>
                @error('description') <p style="font-size:11px;color:#f87171;margin-top:4px;">{{ $message }}</p> @enderror
            </div>

            <div style="display:grid;grid-template-columns:1fr 1fr;gap:1rem;">
                <div>
                    <label style="display:block;font-size:11px;font-weight:600;color:#71717a;text-transform:uppercase;letter-spacing:0.08em;margin-bottom:6px;">Industry</label>
                    <select wire:model="industry" class="dot-input">
                        <option value="">Select industry…</option>
                        @foreach(\App\Livewire\Pulse\CreateCommunity::$industries as $ind)
                            <option value="{{ $ind }}">{{ $ind }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label style="display:block;font-size:11px;font-weight:600;color:#71717a;text-transform:uppercase;letter-spacing:0.08em;margin-bottom:6px;">Visibility</label>
                    <select wire:model="visibility" class="dot-input">
                        <option value="public">Public – anyone can join</option>
                        <option value="private">Private – invite only</option>
                        <option value="enterprise">Enterprise – team only</option>
                    </select>
                </div>
            </div>

            <div style="display:flex;gap:0.75rem;padding-top:0.5rem;">
                <button type="submit" class="dot-btn dot-btn-primary" wire:loading.attr="disabled">
                    <span wire:loading.remove><span class="material-symbols-rounded" style="font-size:15px;">add_circle</span> Create Community</span>
                    <span wire:loading>Creating…</span>
                </button>
                <a href="{{ route('communities.index') }}" class="dot-btn dot-btn-ghost" style="text-decoration:none;">Cancel</a>
            </div>
        </div>
    </form>
</div>
