<div class="dot-card" style="padding:1rem 1.5rem;margin-bottom:1rem;">
    <button
        wire:click="$toggle('showForm')"
        style="width:100%;text-align:left;font-size:13px;color:#3f3f46;background:rgba(255,255,255,0.03);border:1px solid rgba(255,255,255,0.07);border-radius:8px;padding:10px 14px;cursor:pointer;transition:border-color .15s,color .15s;"
        onmouseover="this.style.borderColor='rgba(192,132,252,0.3)';this.style.color='#71717a';"
        onmouseout="this.style.borderColor='rgba(255,255,255,0.07)';this.style.color='#3f3f46';"
    >
        <span class="material-symbols-rounded" style="font-size:15px;vertical-align:-2px;margin-right:6px;">edit</span>
        Share something with the community…
    </button>

    @if($showForm)
        <form wire:submit="publish" style="margin-top:1rem;display:flex;flex-direction:column;gap:0.875rem;">
            <div style="display:grid;grid-template-columns:1fr 1fr;gap:0.75rem;">
                <div>
                    <label style="display:block;font-size:10px;font-weight:600;color:#52525b;text-transform:uppercase;letter-spacing:0.08em;margin-bottom:5px;">Type</label>
                    <select wire:model="type" class="dot-input" style="font-size:12px;">
                        @foreach(\App\Models\PulsePost::$types as $t)
                            <option value="{{ $t }}">{{ ucfirst(str_replace('_', ' ', $t)) }}</option>
                        @endforeach
                    </select>
                    @error('type') <p style="font-size:11px;color:#f87171;margin-top:3px;">{{ $message }}</p> @enderror
                </div>
                <div>
                    <label style="display:block;font-size:10px;font-weight:600;color:#52525b;text-transform:uppercase;letter-spacing:0.08em;margin-bottom:5px;">Community (optional)</label>
                    <select wire:model="communityId" class="dot-input" style="font-size:12px;">
                        <option value="">No community</option>
                        @foreach($this->communities as $community)
                            <option value="{{ $community->id }}">{{ $community->name }}</option>
                        @endforeach
                    </select>
                </div>
            </div>

            <div>
                <label style="display:block;font-size:10px;font-weight:600;color:#52525b;text-transform:uppercase;letter-spacing:0.08em;margin-bottom:5px;">Title (optional)</label>
                <input wire:model="title" type="text" placeholder="Give your post a title…" class="dot-input" />
                @error('title') <p style="font-size:11px;color:#f87171;margin-top:3px;">{{ $message }}</p> @enderror
            </div>

            <div>
                <label style="display:block;font-size:10px;font-weight:600;color:#52525b;text-transform:uppercase;letter-spacing:0.08em;margin-bottom:5px;">Content</label>
                <textarea wire:model="body" rows="4" placeholder="What do you want to share?" class="dot-input" style="resize:none;"></textarea>
                @error('body') <p style="font-size:11px;color:#f87171;margin-top:3px;">{{ $message }}</p> @enderror
            </div>

            <div>
                <label style="display:block;font-size:10px;font-weight:600;color:#52525b;text-transform:uppercase;letter-spacing:0.08em;margin-bottom:5px;">Attachment (optional)</label>
                <input wire:model="attachment" type="file" accept="image/*,video/mp4,application/pdf" style="font-size:12px;color:#71717a;" />
                @error('attachment') <p style="font-size:11px;color:#f87171;margin-top:3px;">{{ $message }}</p> @enderror
                <div wire:loading wire:target="attachment" style="font-size:11px;color:#52525b;margin-top:3px;">Uploading…</div>
            </div>

            <div style="display:flex;align-items:center;gap:0.75rem;padding-top:0.25rem;">
                <button type="submit" class="dot-btn dot-btn-primary" wire:loading.attr="disabled">
                    <span wire:loading.remove><span class="material-symbols-rounded" style="font-size:14px;">send</span> Publish</span>
                    <span wire:loading>Processing…</span>
                </button>
                <button type="button" wire:click="$set('showForm', false)" class="dot-btn dot-btn-ghost">Cancel</button>
                <span style="font-size:11px;color:#3f3f46;margin-left:auto;display:flex;align-items:center;gap:4px;">
                    <span class="material-symbols-rounded" style="font-size:13px;">auto_awesome</span>
                    AI moderation runs automatically
                </span>
            </div>
        </form>
    @endif
</div>
