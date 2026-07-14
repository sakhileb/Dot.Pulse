<div>
    {{-- Community Banner --}}
    <div style="background:#141416;border-bottom:1px solid rgba(255,255,255,0.06);margin-bottom:1.5rem;">
        @if($community->banner_url)
            <div style="height:160px;background:url('{{ $community->banner_url }}') center/cover no-repeat;"></div>
        @else
            <div style="height:100px;background:linear-gradient(135deg,rgba(192,132,252,0.08) 0%,rgba(192,132,252,0.02) 100%);"></div>
        @endif
        <div style="padding:1.25rem 2rem 1.5rem;display:flex;align-items:flex-end;justify-content:space-between;gap:1rem;flex-wrap:wrap;">
            <div style="display:flex;align-items:center;gap:1rem;">
                <div style="width:56px;height:56px;border-radius:14px;background:rgba(192,132,252,0.15);border:2px solid rgba(192,132,252,0.25);display:flex;align-items:center;justify-content:center;font-size:22px;font-weight:700;color:#c084fc;font-family:'Syne',sans-serif;flex-shrink:0;">
                    {{ strtoupper(substr($community->name, 0, 1)) }}
                </div>
                <div>
                    <h1 style="font-family:'Syne',sans-serif;font-size:1.2rem;font-weight:700;color:#f4f4f5;margin:0 0 2px;letter-spacing:-0.01em;">{{ $community->name }}</h1>
                    <div style="display:flex;align-items:center;gap:10px;flex-wrap:wrap;">
                        @if($community->industry)
                            <span style="font-size:11px;color:#c084fc;font-weight:600;">{{ $community->industry }}</span>
                        @endif
                        <span style="font-size:11px;color:#52525b;">{{ number_format($community->members_count) }} members</span>
                        <span style="font-size:11px;padding:2px 7px;border-radius:6px;border:1px solid rgba(255,255,255,0.08);color:#71717a;background:rgba(255,255,255,0.04);">{{ ucfirst($community->visibility) }}</span>
                    </div>
                </div>
            </div>
            <div style="display:flex;gap:8px;align-items:center;">
                @error('leave') <span style="font-size:12px;color:#f87171;">{{ $message }}</span> @enderror
                @if($this->isMember)
                    <button wire:click="leave" class="dot-btn dot-btn-ghost" style="font-size:12px;">
                        <span class="material-symbols-rounded" style="font-size:14px;">logout</span>
                        Leave
                    </button>
                    @if(in_array($this->memberRole, ['admin','moderator']))
                        <span style="font-size:11px;padding:2px 8px;border-radius:100px;background:rgba(192,132,252,0.1);color:#c084fc;border:1px solid rgba(192,132,252,0.18);">{{ ucfirst($this->memberRole) }}</span>
                    @endif
                @else
                    <button wire:click="join" class="dot-btn dot-btn-primary">
                        <span class="material-symbols-rounded" style="font-size:14px;">add</span>
                        Join
                    </button>
                @endif
            </div>
        </div>
    </div>

    <div style="display:grid;grid-template-columns:1fr 280px;gap:1.5rem;">
        {{-- Main Column --}}
        <div>
            {{-- Tabs --}}
            <div style="display:flex;gap:2px;border-bottom:1px solid rgba(255,255,255,0.06);margin-bottom:1.25rem;">
                @foreach(['posts' => 'Posts', 'members' => 'Members', 'rules' => 'Rules'] as $key => $label)
                    <button wire:click="setTab('{{ $key }}')" style="padding:8px 16px;font-size:13px;font-weight:600;border:none;cursor:pointer;background:none;border-bottom:2px solid {{ $tab === $key ? '#c084fc' : 'transparent' }};color:{{ $tab === $key ? '#c084fc' : '#71717a' }};transition:color .13s;">
                        {{ $label }}
                    </button>
                @endforeach
            </div>

            @if($tab === 'posts')
                {{-- Post filter --}}
                <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:1rem;">
                    @if($this->isMember)
                        <a href="{{ route('dashboard') }}" class="dot-btn dot-btn-primary" style="font-size:12px;text-decoration:none;">
                            <span class="material-symbols-rounded" style="font-size:14px;">add</span>
                            New Post
                        </a>
                    @else
                        <div></div>
                    @endif
                    <select wire:model.live="filterType" class="dot-input" style="font-size:11px;padding:4px 8px;width:auto;">
                        <option value="">All types</option>
                        @foreach(\App\Models\PulsePost::$types as $t)
                            <option value="{{ $t }}">{{ ucfirst(str_replace('_', ' ', $t)) }}</option>
                        @endforeach
                    </select>
                </div>

                @if($this->posts->isEmpty())
                    <div style="text-align:center;padding:3rem 1rem;color:#3f3f46;">
                        <span class="material-symbols-rounded" style="font-size:40px;display:block;margin-bottom:0.5rem;">forum</span>
                        <p style="font-size:13px;">No posts yet. Be the first to share something.</p>
                    </div>
                @else
                    <div style="display:flex;flex-direction:column;gap:0.75rem;">
                        @foreach($this->posts as $post)
                            @php
                                $typeColors = ['discussion'=>'#60a5fa','announcement'=>'#fb923c','question'=>'#22d3ee','idea'=>'#34d399','bug_report'=>'#f87171','release'=>'#a78bfa','success_story'=>'#4ade80','showcase'=>'#f472b6','tutorial'=>'#818cf8','agent'=>'#c084fc','integration'=>'#2dd4bf','event'=>'#fb923c','article'=>'#94a3b8','poll'=>'#fbbf24','video'=>'#f87171','job'=>'#38bdf8','marketplace'=>'#4ade80'];
                                $color = $typeColors[$post->type] ?? '#71717a';
                            @endphp
                            <a href="{{ route('posts.show', $post->id) }}" class="dot-card" style="padding:1.1rem 1.5rem;display:block;text-decoration:none;transition:border-color .15s;">
                                @if($post->is_pinned)
                                    <div style="display:flex;align-items:center;gap:5px;font-size:10px;font-weight:600;color:#fbbf24;text-transform:uppercase;letter-spacing:0.08em;margin-bottom:6px;">
                                        <span class="material-symbols-rounded" style="font-size:12px;">push_pin</span> Pinned
                                    </div>
                                @endif
                                <div style="display:flex;align-items:center;gap:8px;margin-bottom:6px;">
                                    <span style="font-size:11px;font-weight:600;padding:2px 8px;border-radius:100px;background:{{ $color }}18;color:{{ $color }};">{{ str_replace('_',' ',ucfirst($post->type)) }}</span>
                                    <span style="font-size:11px;color:#3f3f46;">{{ $post->created_at->diffForHumans() }}</span>
                                </div>
                                @if($post->title)
                                    <div style="font-size:14px;font-weight:600;color:#f4f4f5;margin-bottom:4px;">{{ $post->title }}</div>
                                @endif
                                <p style="font-size:13px;color:#71717a;margin:0;line-height:1.5;overflow:hidden;display:-webkit-box;-webkit-line-clamp:2;-webkit-box-orient:vertical;">{{ $post->body }}</p>
                                <div style="display:flex;align-items:center;gap:12px;margin-top:0.6rem;">
                                    <span style="font-size:11px;color:#52525b;">{{ $post->author->name }}</span>
                                    <span style="display:flex;align-items:center;gap:3px;font-size:11px;color:#52525b;"><span class="material-symbols-rounded" style="font-size:12px;">thumb_up</span>{{ $post->reactions_count }}</span>
                                    <span style="display:flex;align-items:center;gap:3px;font-size:11px;color:#52525b;"><span class="material-symbols-rounded" style="font-size:12px;">chat_bubble_outline</span>{{ $post->comments_count }}</span>
                                </div>
                            </a>
                        @endforeach
                    </div>
                @endif

            @elseif($tab === 'members')
                <div style="display:grid;grid-template-columns:repeat(auto-fill,minmax(200px,1fr));gap:0.75rem;">
                    @foreach($this->members as $membership)
                        <a href="{{ route('profile.public', $membership->user->name) }}" class="dot-card" style="padding:1rem;display:flex;align-items:center;gap:0.75rem;text-decoration:none;">
                            <div style="width:36px;height:36px;border-radius:50%;background:rgba(192,132,252,0.12);border:1px solid rgba(192,132,252,0.2);display:flex;align-items:center;justify-content:center;font-size:14px;font-weight:700;color:#c084fc;flex-shrink:0;font-family:'Syne',sans-serif;">
                                {{ strtoupper(substr($membership->user->name, 0, 1)) }}
                            </div>
                            <div style="min-width:0;">
                                <div style="font-size:13px;font-weight:600;color:#f4f4f5;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;">{{ $membership->user->name }}</div>
                                <div style="font-size:11px;color:#52525b;">{{ ucfirst($membership->role) }}</div>
                            </div>
                        </a>
                    @endforeach
                </div>

            @elseif($tab === 'rules')
                @if($community->rules->isEmpty())
                    <p style="font-size:13px;color:#3f3f46;text-align:center;padding:2rem;">No rules defined yet.</p>
                @else
                    <div style="display:flex;flex-direction:column;gap:0.75rem;">
                        @foreach($community->rules->sortBy('sort_order') as $i => $rule)
                            <div class="dot-card" style="padding:1rem 1.25rem;display:flex;gap:1rem;align-items:flex-start;">
                                <div style="width:28px;height:28px;border-radius:50%;background:rgba(192,132,252,0.1);display:flex;align-items:center;justify-content:center;font-size:12px;font-weight:700;color:#c084fc;flex-shrink:0;font-family:'Syne',sans-serif;">{{ $i + 1 }}</div>
                                <div>
                                    <div style="font-size:13px;font-weight:600;color:#f4f4f5;margin-bottom:2px;">{{ $rule->title }}</div>
                                    @if($rule->description)
                                        <p style="font-size:12px;color:#71717a;margin:0;">{{ $rule->description }}</p>
                                    @endif
                                </div>
                            </div>
                        @endforeach
                    </div>
                @endif
            @endif
        </div>

        {{-- Sidebar --}}
        <div style="display:flex;flex-direction:column;gap:1rem;">
            <div class="dot-card" style="padding:1.25rem;">
                <div style="font-size:10px;font-weight:600;text-transform:uppercase;letter-spacing:0.09em;color:#52525b;margin-bottom:0.75rem;">About</div>
                @if($community->description)
                    <p style="font-size:13px;color:#a1a1aa;line-height:1.6;margin:0 0 0.75rem;">{{ $community->description }}</p>
                @endif
                <div style="display:flex;flex-direction:column;gap:6px;">
                    <div style="display:flex;justify-content:space-between;font-size:12px;">
                        <span style="color:#52525b;">Created by</span>
                        <a href="{{ route('profile.public', $community->creator->name) }}" style="color:#c084fc;text-decoration:none;font-weight:500;">{{ $community->creator->name }}</a>
                    </div>
                    <div style="display:flex;justify-content:space-between;font-size:12px;">
                        <span style="color:#52525b;">Members</span>
                        <span style="color:#f4f4f5;font-weight:500;">{{ number_format($community->members_count) }}</span>
                    </div>
                    @if($community->industry)
                        <div style="display:flex;justify-content:space-between;font-size:12px;">
                            <span style="color:#52525b;">Industry</span>
                            <span style="color:#f4f4f5;font-weight:500;">{{ $community->industry }}</span>
                        </div>
                    @endif
                    <div style="display:flex;justify-content:space-between;font-size:12px;">
                        <span style="color:#52525b;">Created</span>
                        <span style="color:#71717a;">{{ $community->created_at->format('M Y') }}</span>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
