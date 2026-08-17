<div style="display:flex;flex-direction:column;gap:1rem;">
    <div class="dot-card" style="padding:1.5rem;">
        <div style="display:flex;align-items:flex-start;justify-content:space-between;gap:16px;">
            <div style="min-width:0;">
                <div style="display:flex;align-items:center;gap:10px;">
                    <h2 style="font-family:'Syne',sans-serif;font-size:19px;font-weight:700;color:#f4f4f5;margin:0;">{{ $community->name }}</h2>
                    @if($community->industry)
                        <span class="dot-badge dot-badge-accent">{{ $community->industry }}</span>
                    @endif
                </div>
                @if($community->description)
                    <p style="font-size:13.5px;color:#a1a1aa;line-height:1.6;margin:8px 0 0;max-width:560px;">{{ $community->description }}</p>
                @endif
                <p style="font-size:12px;color:#71717a;margin:10px 0 0;">{{ number_format($community->members_count) }} members</p>
            </div>

            @auth
                @if($this->isMember)
                    <button wire:click="leave" class="dot-btn dot-btn-ghost" style="flex-shrink:0;">Leave</button>
                @else
                    <button wire:click="join" class="dot-btn dot-btn-primary" style="flex-shrink:0;">Join</button>
                @endif
            @endauth
        </div>
    </div>

    <div class="dot-card" style="padding:1.25rem 1.5rem;">
        <h3 style="font-family:'Syne',sans-serif;font-size:13.5px;font-weight:700;color:#f4f4f5;margin:0 0 0.9rem;">Posts</h3>

        @if($this->posts->isEmpty())
            <p style="font-size:13px;color:#52525b;text-align:center;padding:1.5rem 0;margin:0;">No posts in this community yet.</p>
        @else
            <div style="display:flex;flex-direction:column;gap:2px;">
                @foreach($this->posts as $post)
                    <a href="{{ route('posts.show', $post) }}" class="dot-row" style="display:block;padding:12px 14px;text-decoration:none;">
                        <div style="display:flex;align-items:center;gap:8px;margin-bottom:2px;">
                            <span style="font-size:12.5px;font-weight:600;color:#f4f4f5;">{{ $post->author->name }}</span>
                            <span class="dot-badge dot-badge-accent">{{ str_replace('_', ' ', ucfirst($post->type)) }}</span>
                            <span style="font-size:11px;color:#3f3f46;margin-left:auto;">{{ $post->created_at->diffForHumans() }}</span>
                        </div>
                        @if($post->title)
                            <p style="font-size:13.5px;font-weight:600;color:#f4f4f5;margin:0;">{{ $post->title }}</p>
                        @endif
                        <p style="font-size:12.5px;color:#71717a;line-height:1.5;margin:3px 0 0;" class="line-clamp-2">{{ $post->body }}</p>
                    </a>
                @endforeach
            </div>
        @endif
    </div>
</div>
