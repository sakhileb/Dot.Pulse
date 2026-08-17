<div>
    @unless($this->isSelf)
        @if($compact)
            <button wire:click="toggleFollow" style="background:none;border:none;font-size:11px;font-weight:600;cursor:pointer;font-family:'Inter',sans-serif;color:{{ $this->isFollowing ? '#71717a' : '#f1c62e' }};padding:0;">
                {{ $this->isFollowing ? 'Following' : '+ Follow' }}
            </button>
        @else
            <button wire:click="toggleFollow" class="dot-btn {{ $this->isFollowing ? 'dot-btn-ghost' : 'dot-btn-primary' }}" style="flex-shrink:0;">
                {{ $this->isFollowing ? 'Following' : 'Follow' }}
            </button>
        @endif
    @endunless
</div>
