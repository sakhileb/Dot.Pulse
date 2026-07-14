<div class="dot-card" style="padding:1.25rem 1.5rem;">
    <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:1rem;gap:0.75rem;flex-wrap:wrap;">
        <h3 style="font-family:'Syne',sans-serif;font-size:0.875rem;font-weight:700;color:#f4f4f5;margin:0;">Communities</h3>
        <div style="display:flex;gap:0.5rem;align-items:center;">
            <div style="position:relative;">
                <span class="material-symbols-rounded" style="position:absolute;left:9px;top:50%;transform:translateY(-50%);font-size:14px;color:#52525b;pointer-events:none;">search</span>
                <input wire:model.live="search" type="text" placeholder="Search…" class="dot-input" style="font-size:12px;padding-left:30px;width:160px;" />
            </div>
            <a href="{{ route('communities.index') }}" class="dot-btn dot-btn-ghost" style="font-size:12px;text-decoration:none;">
                View all
            </a>
        </div>
    </div>

    @if($this->communities->isEmpty())
        <p style="font-size:13px;color:#3f3f46;text-align:center;padding:2rem;">No communities found.</p>
    @else
        <div style="display:grid;grid-template-columns:repeat(auto-fill,minmax(200px,1fr));gap:0.625rem;">
            @foreach($this->communities as $community)
                @php $isMember = $community->hasMember(auth()->user()); @endphp
                <div class="dot-card" style="padding:0.875rem 1rem;display:flex;flex-direction:column;gap:0.5rem;">
                    <div style="display:flex;align-items:flex-start;justify-content:space-between;gap:0.5rem;">
                        <div style="min-width:0;">
                            <a href="{{ route('communities.show', $community->slug) }}" style="font-size:13px;font-weight:600;color:#f4f4f5;text-decoration:none;display:block;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;">{{ $community->name }}</a>
                            @if($community->industry)
                                <div style="font-size:10px;color:#c084fc;font-weight:600;margin-top:1px;">{{ $community->industry }}</div>
                            @endif
                        </div>
                        <div style="flex-shrink:0;">
                            @if($isMember)
                                <button wire:click="leave({{ $community->id }})" style="font-size:10px;padding:3px 9px;border:1px solid rgba(255,255,255,0.1);border-radius:6px;color:#52525b;background:transparent;cursor:pointer;transition:all .13s;" onmouseover="this.style.borderColor='rgba(248,113,113,0.4)';this.style.color='#f87171'" onmouseout="this.style.borderColor='rgba(255,255,255,0.1)';this.style.color='#52525b'">Leave</button>
                            @else
                                <button wire:click="join({{ $community->id }})" style="font-size:10px;padding:3px 9px;border:none;border-radius:6px;background:#c084fc;color:#09090b;cursor:pointer;font-weight:600;transition:filter .13s;" onmouseover="this.style.filter='brightness(1.1)'" onmouseout="this.style.filter='none'">Join</button>
                            @endif
                        </div>
                    </div>
                    <p style="font-size:11px;color:#52525b;margin:0;overflow:hidden;display:-webkit-box;-webkit-line-clamp:2;-webkit-box-orient:vertical;line-height:1.5;">{{ $community->description }}</p>
                    <div style="font-size:10px;color:#3f3f46;">{{ number_format($community->members_count) }} members</div>
                </div>
            @endforeach
        </div>
    @endif
</div>
