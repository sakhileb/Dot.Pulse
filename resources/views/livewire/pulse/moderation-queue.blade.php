<div>
    {{-- Filter tabs --}}
    <div style="display:flex;gap:2px;border-bottom:1px solid rgba(255,255,255,0.06);margin-bottom:1.25rem;">
        @foreach(['all' => 'All', 'pending' => 'Pending', 'flagged' => 'Flagged'] as $key => $label)
            <button wire:click="setFilter('{{ $key }}')" style="padding:8px 16px;font-size:13px;font-weight:600;border:none;cursor:pointer;background:none;border-bottom:2px solid {{ $filter === $key ? '#c084fc' : 'transparent' }};color:{{ $filter === $key ? '#c084fc' : '#71717a' }};transition:color .13s;">
                {{ $label }}
            </button>
        @endforeach
    </div>

    @if($this->items->isEmpty())
        <div style="text-align:center;padding:4rem;color:#3f3f46;">
            <span class="material-symbols-rounded" style="font-size:40px;display:block;margin-bottom:0.5rem;">check_circle</span>
            <p style="font-size:13px;">Queue is clear. No items need review.</p>
        </div>
    @else
        <div style="display:flex;flex-direction:column;gap:0.75rem;">
            @foreach($this->items as $enrichment)
                @php
                    $post = $enrichment->post;
                    $statusColors = ['pending' => '#fbbf24', 'flagged' => '#f87171', 'approved' => '#4ade80', 'rejected' => '#f87171'];
                    $sc = $statusColors[$enrichment->moderation_status] ?? '#71717a';
                @endphp
                <div class="dot-card" style="padding:1.25rem 1.5rem;">
                    <div style="display:flex;align-items:flex-start;justify-content:space-between;gap:1rem;flex-wrap:wrap;">
                        <div style="flex:1;min-width:0;">
                            <div style="display:flex;align-items:center;gap:8px;flex-wrap:wrap;margin-bottom:6px;">
                                <span style="font-size:11px;font-weight:600;padding:2px 8px;border-radius:100px;background:{{ $sc }}18;color:{{ $sc }};border:1px solid {{ $sc }}28;">{{ ucfirst($enrichment->moderation_status) }}</span>
                                <span style="font-size:11px;color:#52525b;">by {{ $post->author->name }}</span>
                                @if($post->community)
                                    <span style="font-size:11px;color:#52525b;">in {{ $post->community->name }}</span>
                                @endif
                                <span style="font-size:11px;color:#3f3f46;">{{ $post->created_at->diffForHumans() }}</span>
                            </div>
                            @if($post->title)
                                <div style="font-size:14px;font-weight:600;color:#f4f4f5;margin-bottom:4px;">{{ $post->title }}</div>
                            @endif
                            <p style="font-size:13px;color:#71717a;margin:0 0 0.75rem;overflow:hidden;display:-webkit-box;-webkit-line-clamp:3;-webkit-box-orient:vertical;">{{ $post->body }}</p>

                            {{-- AI Scores --}}
                            <div style="display:flex;flex-wrap:wrap;gap:8px;font-size:11px;">
                                <span style="padding:2px 7px;background:rgba(255,255,255,0.04);border:1px solid rgba(255,255,255,0.07);border-radius:5px;color:#71717a;">
                                    Spam <span style="font-weight:600;color:{{ $enrichment->spam_score > 0.6 ? '#f87171' : '#a1a1aa' }};">{{ round($enrichment->spam_score * 100) }}%</span>
                                </span>
                                <span style="padding:2px 7px;background:rgba(255,255,255,0.04);border:1px solid rgba(255,255,255,0.07);border-radius:5px;color:#71717a;">
                                    Safety <span style="font-weight:600;color:{{ $enrichment->safety_score < 0.4 ? '#f87171' : '#a1a1aa' }};">{{ round($enrichment->safety_score * 100) }}%</span>
                                </span>
                                <span style="padding:2px 7px;background:rgba(255,255,255,0.04);border:1px solid rgba(255,255,255,0.07);border-radius:5px;color:#71717a;">
                                    Business <span style="font-weight:600;color:#a1a1aa;">{{ round($enrichment->business_relevance * 100) }}%</span>
                                </span>
                                @if($enrichment->moderation_rationale)
                                    <span style="padding:2px 7px;background:rgba(251,191,36,0.06);border:1px solid rgba(251,191,36,0.15);border-radius:5px;color:#fbbf24;">{{ Str::limit($enrichment->moderation_rationale, 60) }}</span>
                                @endif
                            </div>
                        </div>

                        {{-- Action buttons --}}
                        <div style="display:flex;flex-direction:column;gap:6px;flex-shrink:0;">
                            <a href="{{ route('posts.show', $post->id) }}" class="dot-btn dot-btn-ghost" style="font-size:12px;text-decoration:none;" target="_blank">
                                <span class="material-symbols-rounded" style="font-size:14px;">open_in_new</span>
                                View
                            </a>
                            <button wire:click="approve({{ $enrichment->id }})" class="dot-btn" style="font-size:12px;background:rgba(74,222,128,0.1);color:#4ade80;border:1px solid rgba(74,222,128,0.2);">
                                <span class="material-symbols-rounded" style="font-size:14px;">check</span>
                                Approve
                            </button>
                            <button wire:click="reject({{ $enrichment->id }})" class="dot-btn" style="font-size:12px;background:rgba(248,113,113,0.08);color:#f87171;border:1px solid rgba(248,113,113,0.18);">
                                <span class="material-symbols-rounded" style="font-size:14px;">close</span>
                                Reject
                            </button>
                        </div>
                    </div>
                </div>
            @endforeach
        </div>
    @endif
</div>
