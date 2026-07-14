<div>
    {{-- Search input --}}
    <div style="position:relative;margin-bottom:1.5rem;">
        <span class="material-symbols-rounded" style="position:absolute;left:12px;top:50%;transform:translateY(-50%);font-size:18px;color:#52525b;pointer-events:none;">search</span>
        <input
            wire:model.live.debounce.300ms="query"
            type="text"
            placeholder="Search posts, communities, people…"
            class="dot-input"
            style="padding-left:40px;font-size:15px;height:46px;"
            autofocus
        />
    </div>

    @if(strlen(trim($query)) < 2)
        <div style="text-align:center;padding:4rem 1rem;color:#3f3f46;">
            <span class="material-symbols-rounded" style="font-size:48px;display:block;margin-bottom:0.75rem;">manage_search</span>
            <p style="font-size:14px;">Type at least 2 characters to search</p>
        </div>
    @else
        @php $results = $this->results; @endphp

        {{-- Result tabs --}}
        <div style="display:flex;gap:2px;border-bottom:1px solid rgba(255,255,255,0.06);margin-bottom:1.25rem;">
            @foreach(['posts' => 'Posts (' . $results['posts']->count() . ')', 'communities' => 'Communities (' . $results['communities']->count() . ')', 'users' => 'People (' . $results['users']->count() . ')'] as $key => $label)
                <button wire:click="setTab('{{ $key }}')" style="padding:8px 16px;font-size:13px;font-weight:600;border:none;cursor:pointer;background:none;border-bottom:2px solid {{ $tab === $key ? '#c084fc' : 'transparent' }};color:{{ $tab === $key ? '#c084fc' : '#71717a' }};transition:color .13s;">
                    {{ $label }}
                </button>
            @endforeach
        </div>

        @if($tab === 'posts')
            @if($results['posts']->isEmpty())
                <p style="font-size:13px;color:#3f3f46;text-align:center;padding:2rem;">No posts found for "{{ $query }}"</p>
            @else
                <div style="display:flex;flex-direction:column;gap:0.75rem;">
                    @foreach($results['posts'] as $post)
                        @php
                            $typeColors = ['discussion'=>'#60a5fa','announcement'=>'#fb923c','question'=>'#22d3ee','idea'=>'#34d399','bug_report'=>'#f87171','release'=>'#a78bfa','success_story'=>'#4ade80','showcase'=>'#f472b6','tutorial'=>'#818cf8','agent'=>'#c084fc','integration'=>'#2dd4bf','event'=>'#fb923c','article'=>'#94a3b8','poll'=>'#fbbf24','video'=>'#f87171','job'=>'#38bdf8','marketplace'=>'#4ade80'];
                            $color = $typeColors[$post->type] ?? '#71717a';
                        @endphp
                        <a href="{{ route('posts.show', $post->id) }}" class="dot-card" style="padding:1rem 1.5rem;display:block;text-decoration:none;">
                            <div style="display:flex;align-items:center;gap:8px;margin-bottom:5px;">
                                <span style="font-size:11px;font-weight:600;padding:2px 8px;border-radius:100px;background:{{ $color }}18;color:{{ $color }};">{{ str_replace('_',' ',ucfirst($post->type)) }}</span>
                                <span style="font-size:11px;color:#3f3f46;">{{ $post->created_at->diffForHumans() }}</span>
                            </div>
                            @if($post->title)
                                <div style="font-size:14px;font-weight:600;color:#f4f4f5;margin-bottom:3px;">{{ $post->title }}</div>
                            @endif
                            <p style="font-size:13px;color:#71717a;margin:0;overflow:hidden;display:-webkit-box;-webkit-line-clamp:2;-webkit-box-orient:vertical;">{{ $post->body }}</p>
                            <div style="font-size:11px;color:#52525b;margin-top:6px;">by {{ $post->author->name }}@if($post->community) · {{ $post->community->name }}@endif</div>
                        </a>
                    @endforeach
                </div>
            @endif

        @elseif($tab === 'communities')
            @if($results['communities']->isEmpty())
                <p style="font-size:13px;color:#3f3f46;text-align:center;padding:2rem;">No communities found for "{{ $query }}"</p>
            @else
                <div style="display:grid;grid-template-columns:repeat(auto-fill,minmax(220px,1fr));gap:0.75rem;">
                    @foreach($results['communities'] as $community)
                        <a href="{{ route('communities.show', $community->slug) }}" class="dot-card" style="padding:1.1rem;display:block;text-decoration:none;">
                            <div style="font-size:14px;font-weight:600;color:#f4f4f5;margin-bottom:3px;">{{ $community->name }}</div>
                            @if($community->industry)
                                <div style="font-size:11px;color:#c084fc;margin-bottom:5px;">{{ $community->industry }}</div>
                            @endif
                            <p style="font-size:12px;color:#71717a;margin:0 0 6px;overflow:hidden;display:-webkit-box;-webkit-line-clamp:2;-webkit-box-orient:vertical;">{{ $community->description }}</p>
                            <div style="font-size:11px;color:#52525b;">{{ number_format($community->members_count) }} members</div>
                        </a>
                    @endforeach
                </div>
            @endif

        @elseif($tab === 'users')
            @if($results['users']->isEmpty())
                <p style="font-size:13px;color:#3f3f46;text-align:center;padding:2rem;">No users found for "{{ $query }}"</p>
            @else
                <div style="display:grid;grid-template-columns:repeat(auto-fill,minmax(200px,1fr));gap:0.75rem;">
                    @foreach($results['users'] as $user)
                        <a href="{{ route('profile.public', $user->name) }}" class="dot-card" style="padding:1rem;display:flex;align-items:center;gap:0.75rem;text-decoration:none;">
                            <div style="width:38px;height:38px;border-radius:50%;background:rgba(192,132,252,0.12);border:1px solid rgba(192,132,252,0.2);display:flex;align-items:center;justify-content:center;font-size:15px;font-weight:700;color:#c084fc;flex-shrink:0;font-family:'Syne',sans-serif;">
                                {{ strtoupper(substr($user->name, 0, 1)) }}
                            </div>
                            <div>
                                <div style="font-size:13px;font-weight:600;color:#f4f4f5;">{{ $user->name }}</div>
                                <div style="font-size:11px;color:#52525b;">{{ $user->email }}</div>
                            </div>
                        </a>
                    @endforeach
                </div>
            @endif
        @endif
    @endif
</div>
