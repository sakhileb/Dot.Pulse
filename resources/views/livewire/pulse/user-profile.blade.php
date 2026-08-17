<div style="display:flex;flex-direction:column;gap:1rem;">
    <div class="dot-card" style="padding:1.5rem;">
        <div style="display:flex;align-items:flex-start;gap:16px;">
            <div class="dot-avatar" style="width:64px;height:64px;font-size:24px;flex-shrink:0;">
                {{ strtoupper(substr($profileUser->name, 0, 1)) }}
            </div>
            <div style="flex:1;min-width:0;">
                <div style="display:flex;align-items:center;gap:8px;">
                    <h2 style="font-family:'Syne',sans-serif;font-size:18px;font-weight:700;color:#f4f4f5;margin:0;">{{ $profileUser->name }}</h2>
                    @if($profileUser->pulseProfile?->is_verified)
                        <span class="dot-badge dot-badge-accent">Verified</span>
                    @endif
                    @if($profileUser->pulseProfile?->role && $profileUser->pulseProfile->role !== 'customer')
                        <span class="dot-badge dot-badge-neutral">{{ ucfirst($profileUser->pulseProfile->role) }}</span>
                    @endif
                </div>
                @if($profileUser->pulseProfile?->headline)
                    <p style="font-size:13px;color:#a1a1aa;margin:4px 0 0;">{{ $profileUser->pulseProfile->headline }}</p>
                @endif
                @if($profileUser->pulseProfile?->bio)
                    <p style="font-size:13px;color:#a1a1aa;line-height:1.6;margin:10px 0 0;">{{ $profileUser->pulseProfile->bio }}</p>
                @endif

                @if(!empty($profileUser->pulseProfile?->skills))
                    <div style="display:flex;flex-wrap:wrap;gap:6px;margin-top:12px;">
                        @foreach($profileUser->pulseProfile->skills as $skill)
                            <span class="dot-badge dot-badge-neutral">{{ $skill }}</span>
                        @endforeach
                    </div>
                @endif

                <div style="display:flex;align-items:center;gap:18px;margin-top:14px;font-size:12px;color:#71717a;">
                    <span><strong class="metric-val" style="color:#f4f4f5;font-weight:600;">{{ $this->followersCount }}</strong> followers</span>
                    <span><strong class="metric-val" style="color:#f4f4f5;font-weight:600;">{{ $this->followingCount }}</strong> following</span>
                    <span><strong class="metric-val" style="color:var(--accent);font-weight:600;">{{ $profileUser->pulseProfile?->community_points ?? 0 }}</strong> points</span>
                    @if($profileUser->pulseProfile?->solutions_accepted)
                        <span><strong class="metric-val" style="color:#f4f4f5;font-weight:600;">{{ $profileUser->pulseProfile->solutions_accepted }}</strong> solutions</span>
                    @endif
                </div>
            </div>

            <livewire:pulse.follow-button :user="$profileUser" wire:key="follow-{{ $profileUser->id }}" />
        </div>
    </div>

    <div class="dot-card" style="padding:1.5rem;">
        <h3 style="font-family:'Syne',sans-serif;font-size:13.5px;font-weight:700;color:#f4f4f5;margin:0 0 0.9rem;">Posts</h3>

        @if($this->posts->isEmpty())
            <p style="font-size:13px;color:#52525b;text-align:center;padding:1.5rem 0;margin:0;">No published posts yet.</p>
        @else
            <div style="display:flex;flex-direction:column;gap:2px;">
                @foreach($this->posts as $post)
                    <a href="{{ route('posts.show', $post) }}" class="dot-row" style="display:block;padding:12px 14px;text-decoration:none;">
                        @if($post->title)
                            <p style="font-size:13.5px;font-weight:600;color:#f4f4f5;margin:0;">{{ $post->title }}</p>
                        @endif
                        <p style="font-size:12.5px;color:#71717a;line-height:1.5;margin:3px 0 0;" class="line-clamp-2">{{ $post->body }}</p>
                        <p style="font-size:11px;color:#3f3f46;margin:6px 0 0;">
                            {{ str_replace('_', ' ', ucfirst($post->type)) }}
                            @if($post->community) &middot; in {{ $post->community->name }} @endif
                            &middot; {{ $post->created_at->diffForHumans() }}
                        </p>
                    </a>
                @endforeach
            </div>
        @endif
    </div>
</div>
