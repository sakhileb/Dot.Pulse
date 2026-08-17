<div style="max-width:760px;">
    @if($this->heldPosts->isEmpty())
        <div class="dot-card" style="padding:2.5rem 1.5rem;text-align:center;">
            <p style="font-size:13px;color:#71717a;margin:0;">Nothing waiting for review.</p>
        </div>
    @endif

    @foreach($this->heldPosts as $post)
        <div class="dot-card" style="padding:1.25rem 1.5rem;margin-bottom:1rem;">
            <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:10px;">
                <div>
                    <p style="font-size:13.5px;font-weight:600;color:#f4f4f5;margin:0;">{{ $post->author->name }}</p>
                    <p style="font-size:11.5px;color:#71717a;margin:2px 0 0;">
                        {{ $post->community?->name ?? 'No community' }} &middot; {{ ucfirst($post->type) }}
                    </p>
                </div>
                <span class="dot-badge {{ $post->enrichment?->isRejected() ? 'dot-badge-danger' : 'dot-badge-accent' }}">
                    {{ $post->enrichment?->isRejected() ? 'AI recommended reject' : 'AI flagged for review' }}
                </span>
            </div>

            @if($post->title)
                <p style="font-family:'Syne',sans-serif;font-size:14.5px;font-weight:600;color:#f4f4f5;margin:0 0 4px;">{{ $post->title }}</p>
            @endif
            <p style="font-size:13px;color:#a1a1aa;line-height:1.6;margin:0 0 12px;">{{ $post->body }}</p>

            @if($post->enrichment)
                <div style="font-size:11.5px;color:#52525b;margin-bottom:12px;display:flex;flex-direction:column;gap:3px;">
                    @if($post->enrichment->moderation_rationale)
                        <p style="margin:0;">AI rationale: {{ $post->enrichment->moderation_rationale }}</p>
                    @endif
                    <p style="margin:0;">Spam score: {{ number_format($post->enrichment->spam_score, 2) }} &middot; Safety score: {{ number_format($post->enrichment->safety_score, 2) }}</p>
                </div>
            @endif

            @if($rejectingPostId === $post->id)
                <div style="display:flex;align-items:center;gap:10px;">
                    <input type="text" wire:model="rejectReason" placeholder="Reason for rejecting" class="dot-input" style="flex:1;" />
                    <button wire:click="confirmReject({{ $post->id }})" class="dot-btn" style="background:#e24b4a;color:#fff;flex-shrink:0;">
                        Confirm reject
                    </button>
                    <button wire:click="cancelReject" class="dot-btn dot-btn-ghost" style="flex-shrink:0;">
                        Cancel
                    </button>
                </div>
            @else
                <div style="display:flex;align-items:center;gap:10px;">
                    <button wire:click="approve({{ $post->id }})" class="dot-btn" style="background:#22c55e;color:#052e10;">
                        Approve
                    </button>
                    <button wire:click="promptReject({{ $post->id }})" class="dot-btn" style="background:#e24b4a;color:#fff;">
                        Reject
                    </button>
                </div>
            @endif
        </div>
    @endforeach
</div>
