<div class="dot-card" style="padding:1.5rem;">
    <div class="flex items-center justify-between mb-4">
        <h3 style="font-family:'Syne',sans-serif;font-size:0.875rem;font-weight:700;color:#f4f4f5;">Communities</h3>
        <input
            wire:model.live="search"
            type="text"
            placeholder="Search communities…"
            class="dot-input"
            style="width:200px;font-size:12px;padding:6px 10px;"
        />
    </div>

    @if($this->communities->isEmpty())
        <p style="font-size:13px;color:#52525b;padding:1.5rem 0;text-align:center;margin:0;">No communities found.</p>
    @else
        <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
            @foreach($this->communities as $community)
                @php $isMember = $community->hasMember(auth()->user()); @endphp
                <div class="dot-row" style="padding:14px;display:flex;align-items:flex-start;justify-content:space-between;gap:12px;">
                    <div style="min-width:0;">
                        <a href="{{ route('communities.show', $community) }}" style="display:block;font-size:13.5px;font-weight:600;color:#f4f4f5;text-decoration:none;margin:0;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;">{{ $community->name }}</a>
                        @if($community->industry)
                            <p style="font-size:11px;color:#f1c62e;font-weight:600;margin:3px 0;">{{ $community->industry }}</p>
                        @endif
                        <p style="font-size:12px;color:#71717a;line-height:1.5;margin:3px 0 0;" class="line-clamp-2">{{ $community->description }}</p>
                        <p style="font-size:11px;color:#52525b;margin:6px 0 0;">{{ number_format($community->members_count) }} members</p>
                    </div>
                    <div style="flex-shrink:0;">
                        @if($isMember)
                            <button wire:click="leave({{ $community->id }})" class="dot-btn dot-btn-ghost" style="font-size:11px;padding:5px 11px;">
                                Leave
                            </button>
                        @else
                            <button wire:click="join({{ $community->id }})" class="dot-btn dot-btn-primary" style="font-size:11px;padding:5px 11px;">
                                Join
                            </button>
                        @endif
                    </div>
                </div>
            @endforeach
        </div>
    @endif
</div>
