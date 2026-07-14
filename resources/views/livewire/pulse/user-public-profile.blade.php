<div>
    {{-- Profile Header --}}
    <div class="dot-card" style="margin-bottom:1.5rem;overflow:hidden;">
        {{-- Cover --}}
        @if($profile->cover_url)
            <div style="height:140px;background:url('{{ $profile->cover_url }}') center/cover no-repeat;"></div>
        @else
            <div style="height:100px;background:linear-gradient(135deg,rgba(192,132,252,0.06) 0%,rgba(192,132,252,0.01) 100%);"></div>
        @endif

        <div style="padding:0 2rem 1.5rem;">
            <div style="display:flex;align-items:flex-end;justify-content:space-between;gap:1rem;margin-top:-28px;flex-wrap:wrap;">
                {{-- Avatar --}}
                <div style="width:64px;height:64px;border-radius:50%;background:rgba(192,132,252,0.15);border:3px solid #09090b;display:flex;align-items:center;justify-content:center;font-size:26px;font-weight:700;color:#c084fc;font-family:'Syne',sans-serif;flex-shrink:0;overflow:hidden;">
                    @if($profile->avatar_url)
                        <img src="{{ $profile->avatar_url }}" alt="{{ $user->name }}" style="width:100%;height:100%;object-fit:cover;">
                    @else
                        {{ strtoupper(substr($user->name, 0, 1)) }}
                    @endif
                </div>

                {{-- Actions --}}
                <div style="display:flex;gap:8px;margin-bottom:4px;">
                    @if(!$this->isOwnProfile)
                        <button wire:click="toggleFollow" class="dot-btn {{ $this->isFollowing ? 'dot-btn-ghost' : 'dot-btn-primary' }}" style="font-size:12px;">
                            <span class="material-symbols-rounded" style="font-size:14px;">{{ $this->isFollowing ? 'person_remove' : 'person_add' }}</span>
                            {{ $this->isFollowing ? 'Following' : 'Follow' }}
                        </button>
                        <button wire:click="startConversation" class="dot-btn dot-btn-ghost" style="font-size:12px;">
                            <span class="material-symbols-rounded" style="font-size:14px;">chat_bubble_outline</span>
                            Message
                        </button>
                    @else
                        <a href="{{ route('pulse-profile.edit') }}" class="dot-btn dot-btn-ghost" style="font-size:12px;text-decoration:none;">
                            <span class="material-symbols-rounded" style="font-size:14px;">edit</span>
                            Edit Profile
                        </a>
                    @endif
                </div>
            </div>

            <div style="margin-top:0.75rem;">
                <div style="display:flex;align-items:center;gap:8px;flex-wrap:wrap;">
                    <h1 style="font-family:'Syne',sans-serif;font-size:1.15rem;font-weight:700;color:#f4f4f5;margin:0;letter-spacing:-0.01em;">{{ $user->name }}</h1>
                    @if($profile->is_verified)
                        <span style="font-size:14px;color:#c084fc;" title="Verified">
                            <span class="material-symbols-rounded" style="font-size:16px;vertical-align:-2px;">verified</span>
                        </span>
                    @endif
                    <span style="font-size:11px;padding:2px 8px;border-radius:100px;background:rgba(192,132,252,0.08);border:1px solid rgba(192,132,252,0.15);color:#c084fc;">{{ ucfirst($profile->role) }}</span>
                </div>
                @if($profile->headline)
                    <p style="font-size:13px;color:#a1a1aa;margin:4px 0 0;">{{ $profile->headline }}</p>
                @endif
                @if($profile->bio)
                    <p style="font-size:13px;color:#71717a;margin:8px 0 0;line-height:1.6;">{{ $profile->bio }}</p>
                @endif
                @if($profile->location || $profile->website)
                    <div style="display:flex;align-items:center;gap:12px;margin-top:8px;flex-wrap:wrap;">
                        @if($profile->location)
                            <span style="display:flex;align-items:center;gap:4px;font-size:12px;color:#52525b;">
                                <span class="material-symbols-rounded" style="font-size:13px;">location_on</span>
                                {{ $profile->location }}
                            </span>
                        @endif
                        @if($profile->website)
                            <a href="{{ $profile->website }}" target="_blank" rel="noopener noreferrer" style="display:flex;align-items:center;gap:4px;font-size:12px;color:#c084fc;text-decoration:none;">
                                <span class="material-symbols-rounded" style="font-size:13px;">link</span>
                                {{ parse_url($profile->website, PHP_URL_HOST) }}
                            </a>
                        @endif
                    </div>
                @endif
            </div>

            {{-- Stats row --}}
            <div style="display:flex;gap:24px;margin-top:1.25rem;padding-top:1rem;border-top:1px solid rgba(255,255,255,0.06);">
                <div style="text-align:center;">
                    <div style="font-family:'JetBrains Mono',monospace;font-size:1.1rem;font-weight:600;color:#c084fc;">{{ number_format($profile->community_points) }}</div>
                    <div style="font-size:10px;font-weight:600;text-transform:uppercase;letter-spacing:0.08em;color:#52525b;">Points</div>
                </div>
                <div style="text-align:center;">
                    <div style="font-family:'JetBrains Mono',monospace;font-size:1.1rem;font-weight:600;color:#f4f4f5;">{{ $this->followersCount }}</div>
                    <div style="font-size:10px;font-weight:600;text-transform:uppercase;letter-spacing:0.08em;color:#52525b;">Followers</div>
                </div>
                <div style="text-align:center;">
                    <div style="font-family:'JetBrains Mono',monospace;font-size:1.1rem;font-weight:600;color:#f4f4f5;">{{ $this->followingCount }}</div>
                    <div style="font-size:10px;font-weight:600;text-transform:uppercase;letter-spacing:0.08em;color:#52525b;">Following</div>
                </div>
                <div style="text-align:center;">
                    <div style="font-family:'JetBrains Mono',monospace;font-size:1.1rem;font-weight:600;color:#4ade80;">{{ $profile->solutions_accepted }}</div>
                    <div style="font-size:10px;font-weight:600;text-transform:uppercase;letter-spacing:0.08em;color:#52525b;">Solutions</div>
                </div>
            </div>
        </div>
    </div>

    <div style="display:grid;grid-template-columns:1fr 240px;gap:1.5rem;">
        <div>
            {{-- Tabs --}}
            <div style="display:flex;gap:2px;border-bottom:1px solid rgba(255,255,255,0.06);margin-bottom:1.25rem;">
                @foreach(['posts' => 'Posts', 'badges' => 'Badges'] as $key => $label)
                    <button wire:click="setTab('{{ $key }}')" style="padding:8px 16px;font-size:13px;font-weight:600;border:none;cursor:pointer;background:none;border-bottom:2px solid {{ $tab === $key ? '#c084fc' : 'transparent' }};color:{{ $tab === $key ? '#c084fc' : '#71717a' }};transition:color .13s;">
                        {{ $label }}
                    </button>
                @endforeach
            </div>

            @if($tab === 'badges')
                @if($this->badges->isEmpty())
                    <p style="font-size:13px;color:#3f3f46;text-align:center;padding:3rem;">No badges earned yet.</p>
                @else
                    <div style="display:flex;flex-wrap:wrap;gap:0.75rem;">
                        @foreach($this->badges as $ub)
                            <div style="display:flex;align-items:center;gap:0.6rem;padding:0.625rem 0.875rem;background:rgba(192,132,252,0.06);border:1px solid rgba(192,132,252,0.15);border-radius:10px;">
                                <span class="material-symbols-rounded" style="font-size:18px;color:#c084fc;">{{ $ub->badge->icon ?? 'star' }}</span>
                                <div>
                                    <div style="font-size:12px;font-weight:600;color:#f4f4f5;">{{ $ub->badge->label }}</div>
                                    <div style="font-size:10px;color:#52525b;">{{ $ub->awarded_at->format('M Y') }}</div>
                                </div>
                            </div>
                        @endforeach
                    </div>
                @endif
            @else

            @if($this->posts->isEmpty())
                <p style="font-size:13px;color:#3f3f46;text-align:center;padding:3rem;">No posts yet.</p>
            @else
                <div style="display:flex;flex-direction:column;gap:0.75rem;">
                    @foreach($this->posts as $post)
                        @php
                            $typeColors = ['discussion'=>'#60a5fa','announcement'=>'#fb923c','question'=>'#22d3ee','idea'=>'#34d399','bug_report'=>'#f87171','release'=>'#a78bfa','success_story'=>'#4ade80','showcase'=>'#f472b6','tutorial'=>'#818cf8','agent'=>'#c084fc','integration'=>'#2dd4bf','event'=>'#fb923c','article'=>'#94a3b8','poll'=>'#fbbf24','video'=>'#f87171','job'=>'#38bdf8','marketplace'=>'#4ade80'];
                            $color = $typeColors[$post->type] ?? '#71717a';
                        @endphp
                        <a href="{{ route('posts.show', $post->id) }}" class="dot-card" style="padding:1rem 1.5rem;display:block;text-decoration:none;">
                            <div style="display:flex;align-items:center;gap:8px;margin-bottom:5px;">
                                <span style="font-size:11px;font-weight:600;padding:2px 8px;border-radius:100px;background:{{ $color }}18;color:{{ $color }};">{{ str_replace('_',' ',ucfirst($post->type)) }}</span>
                                @if($post->community)
                                    <span style="font-size:11px;color:#52525b;">in {{ $post->community->name }}</span>
                                @endif
                                <span style="font-size:11px;color:#3f3f46;margin-left:auto;">{{ $post->created_at->diffForHumans() }}</span>
                            </div>
                            @if($post->title)
                                <div style="font-size:14px;font-weight:600;color:#f4f4f5;margin-bottom:3px;">{{ $post->title }}</div>
                            @endif
                            <p style="font-size:13px;color:#71717a;margin:0;overflow:hidden;display:-webkit-box;-webkit-line-clamp:2;-webkit-box-orient:vertical;">{{ $post->body }}</p>
                        </a>
                    @endforeach
                </div>
            @endif

            @endif {{-- end tab !== badges --}}
        </div>

        {{-- Sidebar --}}
        <div>
            @if($profile->skills && count($profile->skills))
                <div class="dot-card" style="padding:1.1rem 1.25rem;margin-bottom:0.75rem;">
                    <div style="font-size:10px;font-weight:600;text-transform:uppercase;letter-spacing:0.09em;color:#52525b;margin-bottom:0.75rem;">Skills</div>
                    <div style="display:flex;flex-wrap:wrap;gap:5px;">
                        @foreach($profile->skills as $skill)
                            <span style="font-size:11px;padding:3px 9px;background:rgba(255,255,255,0.04);border:1px solid rgba(255,255,255,0.08);border-radius:6px;color:#a1a1aa;">{{ $skill }}</span>
                        @endforeach
                    </div>
                </div>
            @endif
        </div>
    </div>
</div>
