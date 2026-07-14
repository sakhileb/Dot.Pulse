<div>
    <form wire:submit="submit" style="max-width:640px;display:flex;flex-direction:column;gap:1.25rem;">
        <div>
            <label style="display:block;font-size:11px;font-weight:600;color:#71717a;text-transform:uppercase;letter-spacing:0.08em;margin-bottom:6px;">Title *</label>
            <input wire:model="title" type="text" placeholder="e.g. Fleet Tracker Pro Agent" class="dot-input" />
            @error('title') <p style="font-size:11px;color:#f87171;margin-top:3px;">{{ $message }}</p> @enderror
        </div>

        <div>
            <label style="display:block;font-size:11px;font-weight:600;color:#71717a;text-transform:uppercase;letter-spacing:0.08em;margin-bottom:6px;">Description *</label>
            <textarea wire:model="description" rows="4" placeholder="What does it do? Who is it for?" class="dot-input" style="resize:none;"></textarea>
            @error('description') <p style="font-size:11px;color:#f87171;margin-top:3px;">{{ $message }}</p> @enderror
        </div>

        <div style="display:grid;grid-template-columns:1fr 1fr;gap:1rem;">
            <div>
                <label style="display:block;font-size:11px;font-weight:600;color:#71717a;text-transform:uppercase;letter-spacing:0.08em;margin-bottom:6px;">Category *</label>
                <select wire:model="category" class="dot-input">
                    <option value="">Select category…</option>
                    <option value="agent">AI Agent</option>
                    <option value="template">Template</option>
                    <option value="dashboard">Dashboard</option>
                    <option value="prompt_library">Prompt Library</option>
                    <option value="automation">Automation</option>
                    <option value="integration">Integration</option>
                    <option value="extension">Extension</option>
                    <option value="workflow">Workflow</option>
                </select>
                @error('category') <p style="font-size:11px;color:#f87171;margin-top:3px;">{{ $message }}</p> @enderror
            </div>
            <div>
                <label style="display:block;font-size:11px;font-weight:600;color:#71717a;text-transform:uppercase;letter-spacing:0.08em;margin-bottom:6px;">Version</label>
                <input wire:model="version" type="text" placeholder="1.0.0" class="dot-input" />
                @error('version') <p style="font-size:11px;color:#f87171;margin-top:3px;">{{ $message }}</p> @enderror
            </div>
        </div>

        <div style="padding:0.875rem 1rem;background:rgba(251,191,36,0.06);border:1px solid rgba(251,191,36,0.15);border-radius:8px;">
            <p style="font-size:12px;color:#fbbf24;margin:0;line-height:1.6;">
                <span class="material-symbols-rounded" style="font-size:14px;vertical-align:-2px;">info</span>
                Your submission will be reviewed before it appears publicly in the marketplace.
            </p>
        </div>

        <div style="display:flex;gap:0.75rem;">
            <button type="submit" class="dot-btn dot-btn-primary" wire:loading.attr="disabled">
                <span wire:loading.remove><span class="material-symbols-rounded" style="font-size:14px;">upload</span> Submit for Review</span>
                <span wire:loading>Submitting…</span>
            </button>
            <a href="{{ route('marketplace.index') }}" class="dot-btn dot-btn-ghost" style="text-decoration:none;">Cancel</a>
        </div>
    </form>
</div>
