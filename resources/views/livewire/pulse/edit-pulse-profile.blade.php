<div>
    @if($saved)
        <div style="display:flex;align-items:center;gap:8px;padding:10px 14px;background:rgba(74,222,128,0.08);border:1px solid rgba(74,222,128,0.2);border-radius:8px;margin-bottom:1.25rem;">
            <span class="material-symbols-rounded" style="font-size:16px;color:#4ade80;">check_circle</span>
            <span style="font-size:13px;color:#4ade80;">Profile updated successfully.</span>
        </div>
    @endif

    <form wire:submit="save" style="display:flex;flex-direction:column;gap:1.25rem;max-width:640px;">

        {{-- Avatar + Cover --}}
        <div class="dot-card" style="padding:1.25rem 1.5rem;">
            <div style="font-size:11px;font-weight:600;text-transform:uppercase;letter-spacing:0.08em;color:#52525b;margin-bottom:1rem;">Photos</div>
            <div style="display:grid;grid-template-columns:1fr 1fr;gap:1rem;">
                <div>
                    <label style="display:block;font-size:11px;font-weight:600;color:#71717a;margin-bottom:6px;">Avatar</label>
                    @if($profile->avatar_url)
                        <img src="{{ $profile->avatar_url }}" alt="Current avatar" style="width:48px;height:48px;border-radius:50%;object-fit:cover;margin-bottom:6px;border:2px solid rgba(192,132,252,0.25);">
                    @endif
                    <input wire:model="avatarFile" type="file" accept="image/*" style="font-size:12px;color:#71717a;" />
                    <div wire:loading wire:target="avatarFile" style="font-size:11px;color:#52525b;margin-top:3px;">Uploading…</div>
                    @error('avatarFile') <p style="font-size:11px;color:#f87171;margin-top:3px;">{{ $message }}</p> @enderror
                </div>
                <div>
                    <label style="display:block;font-size:11px;font-weight:600;color:#71717a;margin-bottom:6px;">Cover Photo</label>
                    @if($profile->cover_url)
                        <img src="{{ $profile->cover_url }}" alt="Current cover" style="width:100%;height:40px;object-fit:cover;border-radius:6px;margin-bottom:6px;border:1px solid rgba(255,255,255,0.07);">
                    @endif
                    <input wire:model="coverFile" type="file" accept="image/*" style="font-size:12px;color:#71717a;" />
                    @error('coverFile') <p style="font-size:11px;color:#f87171;margin-top:3px;">{{ $message }}</p> @enderror
                </div>
            </div>
        </div>

        {{-- Bio fields --}}
        <div class="dot-card" style="padding:1.25rem 1.5rem;display:flex;flex-direction:column;gap:1rem;">
            <div style="font-size:11px;font-weight:600;text-transform:uppercase;letter-spacing:0.08em;color:#52525b;">About You</div>

            <div>
                <label style="display:block;font-size:11px;font-weight:600;color:#71717a;margin-bottom:5px;">Headline</label>
                <input wire:model="headline" type="text" placeholder="e.g. Fleet Automation Specialist at Acme Corp" class="dot-input" />
                @error('headline') <p style="font-size:11px;color:#f87171;margin-top:3px;">{{ $message }}</p> @enderror
            </div>

            <div>
                <label style="display:block;font-size:11px;font-weight:600;color:#71717a;margin-bottom:5px;">Bio</label>
                <textarea wire:model="bio" rows="3" placeholder="Tell the community about yourself…" class="dot-input" style="resize:none;"></textarea>
                @error('bio') <p style="font-size:11px;color:#f87171;margin-top:3px;">{{ $message }}</p> @enderror
            </div>

            <div style="display:grid;grid-template-columns:1fr 1fr;gap:1rem;">
                <div>
                    <label style="display:block;font-size:11px;font-weight:600;color:#71717a;margin-bottom:5px;">Location</label>
                    <input wire:model="location" type="text" placeholder="Cape Town, ZA" class="dot-input" />
                    @error('location') <p style="font-size:11px;color:#f87171;margin-top:3px;">{{ $message }}</p> @enderror
                </div>
                <div>
                    <label style="display:block;font-size:11px;font-weight:600;color:#71717a;margin-bottom:5px;">Website</label>
                    <input wire:model="website" type="url" placeholder="https://yoursite.com" class="dot-input" />
                    @error('website') <p style="font-size:11px;color:#f87171;margin-top:3px;">{{ $message }}</p> @enderror
                </div>
            </div>

            <div>
                <label style="display:block;font-size:11px;font-weight:600;color:#71717a;margin-bottom:5px;">Skills <span style="color:#3f3f46;font-weight:400;">(comma separated)</span></label>
                <input wire:model="skillsInput" type="text" placeholder="Laravel, Fleet Management, AI Automation…" class="dot-input" />
                @error('skillsInput') <p style="font-size:11px;color:#f87171;margin-top:3px;">{{ $message }}</p> @enderror
            </div>
        </div>

        <div style="display:flex;gap:0.75rem;">
            <button type="submit" class="dot-btn dot-btn-primary" wire:loading.attr="disabled">
                <span wire:loading.remove><span class="material-symbols-rounded" style="font-size:14px;">save</span> Save Changes</span>
                <span wire:loading>Saving…</span>
            </button>
        </div>
    </form>
</div>
