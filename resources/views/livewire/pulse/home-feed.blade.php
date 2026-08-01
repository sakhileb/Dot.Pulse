<div>
    <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:1rem;">
        <h3 style="font-family:'Syne',sans-serif;font-size:0.875rem;font-weight:700;color:#f4f4f5;margin:0;">Community Feed</h3>
        <select wire:model.live="filterType" class="dot-input" style="font-size:11px;padding:4px 8px;width:auto;">
            <option value="">All types</option>
            @foreach(\App\Models\PulsePost::$types as $type)
                <option value="{{ $type }}">{{ ucfirst(str_replace('_', ' ', $type)) }}</option>
            @endforeach
        </select>
    </div>

    <div wire:loading wire:target="filterType" style="text-align:center;padding:2rem 1rem;color:#52525b;font-size:12px;">
        Loading posts…
    </div>

    <div wire:loading.remove wire:target="filterType">
    @if($this->posts->isEmpty())
        <div style="text-align:center;padding:4rem 1rem;color:#3f3f46;">
            <span class="material-symbols-rounded" style="font-size:40px;display:block;margin-bottom:0.5rem;">forum</span>
            <p style="font-size:13px;">No posts yet. Be the first to share something with the community.</p>
        </div>
    @else
        <div style="display:flex;flex-direction:column;gap:0.75rem;">
            @foreach($this->posts as $post)
                @php
                    $typeColors = ['discussion'=>'#60a5fa','announcement'=>'#fb923c','question'=>'#22d3ee','idea'=>'#34d399','bug_report'=>'#f87171','release'=>'#a78bfa','success_story'=>'#4ade80','showcase'=>'#f472b6','tutorial'=>'#818cf8','agent'=>'#c084fc','integration'=>'#2dd4bf','event'=>'#fb923c','article'=>'#94a3b8','poll'=>'#fbbf24','video'=>'#f87171','job'=>'#38bdf8','marketplace'=>'#4ade80'];
                    $color = $typeColors[$post->type] ?? '#71717a';
                @endphp
                <a href="{{ route('posts.show', $post->id) }}" class="dot-card" style="padding:1.1rem 1.5rem;display:block;text-decoration:none;transition:border-color .15s;">
                    <div style="display:flex;align-items:flex-start;gap:0.75rem;">
                        <div style="width:34px;height:34px;border-radius:50%;background:rgba(192,132,252,0.12);border:1px solid rgba(192,132,252,0.2);display:flex;align-items:center;justify-content:center;font-size:13px;font-weight:700;color:#c084fc;flex-shrink:0;font-family:'Syne',sans-serif;">
                            {{ strtoupper(substr($post->author->name, 0, 1)) }}
                        </div>
                        <div style="flex:1;min-width:0;">
                            <div style="display:flex;align-items:center;flex-wrap:wrap;gap:6px;margin-bottom:4px;">
                                <span style="font-size:13px;font-weight:600;color:#d4d4d8;">{{ $post->author->name }}</span>
                                <span style="font-size:11px;font-weight:600;padding:2px 8px;border-radius:100px;background:{{ $color }}18;color:{{ $color }};">
                                    {{ str_replace('_', ' ', ucfirst($post->type)) }}
                                </span>
                                @if($post->community)
                                    <span style="font-size:11px;color:#52525b;">in {{ $post->community->name }}</span>
                                @endif
                                <span style="font-size:11px;color:#3f3f46;margin-left:auto;">{{ $post->created_at->diffForHumans() }}</span>
                            </div>

                            @if($post->title)
                                <div style="font-size:14px;font-weight:600;color:#f4f4f5;margin-bottom:4px;">{{ $post->title }}</div>
                            @endif

                            <p style="font-size:13px;color:#71717a;line-height:1.6;margin:0;overflow:hidden;display:-webkit-box;-webkit-line-clamp:3;-webkit-box-orient:vertical;">{{ $post->body }}</p>

                            @if($post->enrichment?->tags)
                                <div style="display:flex;flex-wrap:wrap;gap:5px;margin-top:0.6rem;">
                                    @foreach(array_slice($post->enrichment->tags, 0, 4) as $tag)
                                        <span style="font-size:11px;padding:2px 7px;background:rgba(255,255,255,0.04);border:1px solid rgba(255,255,255,0.07);border-radius:5px;color:#52525b;">#{{ $tag }}</span>
                                    @endforeach
                                </div>
                            @endif

                            <div style="display:flex;align-items:center;gap:14px;margin-top:0.75rem;padding-top:0.6rem;border-top:1px solid rgba(255,255,255,0.05);">
                                <button wire:click.stop="react({{ $post->id }})" style="display:flex;align-items:center;gap:4px;font-size:12px;color:#71717a;background:none;border:none;cursor:pointer;padding:0;transition:color .13s;" onmouseover="this.style.color='#c084fc'" onmouseout="this.style.color='#71717a'">
                                    <span class="material-symbols-rounded" style="font-size:14px;">thumb_up</span>
                                    <span>{{ $post->reactions_count }}</span>
                                </button>
                                <span style="display:flex;align-items:center;gap:4px;font-size:12px;color:#71717a;">
                                    <span class="material-symbols-rounded" style="font-size:14px;">chat_bubble_outline</span>
                                    <span>{{ $post->comments_count }}</span>
                                </span>
                                @if($post->enrichment)
                                    @php
                                        $sentimentColors = ['positive' => '#4ade80', 'negative' => '#f87171', 'neutral' => '#71717a', 'mixed' => '#fbbf24'];
                                        $sc = $sentimentColors[$post->enrichment->sentiment] ?? '#71717a';
                                    @endphp
                                    <span style="font-size:11px;padding:2px 8px;border-radius:100px;background:{{ $sc }}18;color:{{ $sc }};margin-left:auto;">{{ ucfirst($post->enrichment->sentiment ?? 'neutral') }}</span>
                                @endif
                            </div>
                        </div>
                    </div>
                </a>
            @endforeach
        </div>

        <div style="margin-top:1.25rem;">
            {{ $this->posts->links() }}
        </div>
    @endif
    </div>
</div>
