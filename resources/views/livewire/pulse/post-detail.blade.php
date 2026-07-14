<div>
    {{-- Poll widget --}}
    @if($this->poll)
        @php
            $results  = $this->pollResults;
            $userVote = $this->userVote;
            $total    = array_sum(array_column($results, 'votes'));
            $closed   = $this->poll->closes_at && $this->poll->closes_at->isPast();
        @endphp
        <div class="dot-card" style="padding:1.25rem 1.5rem;margin-bottom:1rem;">
            <div style="display:flex;align-items:center;gap:8px;margin-bottom:1rem;">
                <span class="material-symbols-rounded" style="font-size:16px;color:#fbbf24;">poll</span>
                <span style="font-size:11px;font-weight:600;text-transform:uppercase;letter-spacing:0.08em;color:#52525b;">Poll · {{ $total }} votes</span>
                @if($closed) <span style="font-size:10px;padding:2px 7px;background:rgba(248,113,113,0.1);color:#f87171;border-radius:100px;">Closed</span>
                @elseif($this->poll->closes_at) <span style="font-size:10px;color:#3f3f46;">Closes {{ $this->poll->closes_at->diffForHumans() }}</span>
                @endif
            </div>
            <div style="display:flex;flex-direction:column;gap:0.625rem;">
                @foreach($results as $index => $option)
                    @php $pct = $total > 0 ? round(($option['votes'] / $total) * 100) : 0; @endphp
                    <button wire:click="votePoll({{ $index }})" @if($closed || $userVote !== null) disabled @endif
                        style="position:relative;text-align:left;padding:0.625rem 0.875rem;border-radius:8px;border:1px solid {{ $userVote === $index ? 'rgba(192,132,252,0.4)' : 'rgba(255,255,255,0.08)' }};background:transparent;cursor:{{ ($closed || $userVote !== null) ? 'default' : 'pointer' }};overflow:hidden;width:100%;">
                        <div style="position:absolute;left:0;top:0;height:100%;width:{{ $pct }}%;background:{{ $userVote === $index ? 'rgba(192,132,252,0.12)' : 'rgba(255,255,255,0.04)' }};transition:width .4s;"></div>
                        <div style="position:relative;display:flex;align-items:center;justify-content:space-between;">
                            <span style="font-size:13px;color:{{ $userVote === $index ? '#c084fc' : '#d4d4d8' }};font-weight:{{ $userVote === $index ? '600' : '400' }};">{{ $option['label'] }}</span>
                            <span style="font-size:12px;color:#52525b;font-family:'JetBrains Mono',monospace;">{{ $pct }}%</span>
                        </div>
                    </button>
                @endforeach
            </div>
        </div>
    @endif

    {{-- Post Header --}}
    <div class="dot-card" style="padding:1.75rem 2rem;margin-bottom:1rem;">
        <div style="display:flex;align-items:flex-start;gap:1rem;">
            <div style="width:42px;height:42px;border-radius:50%;background:rgba(192,132,252,0.15);border:1px solid rgba(192,132,252,0.25);display:flex;align-items:center;justify-content:center;font-size:16px;font-weight:700;color:#c084fc;flex-shrink:0;font-family:'Syne',sans-serif;">
                {{ strtoupper(substr($post->author->name, 0, 1)) }}
            </div>
            <div style="flex:1;min-width:0;">
                <div style="display:flex;align-items:center;flex-wrap:wrap;gap:8px;margin-bottom:4px;">
                    <a href="{{ route('profile.public', $post->author->name) }}" style="font-size:14px;font-weight:600;color:#f4f4f5;text-decoration:none;">{{ $post->author->name }}</a>
                    @php
                        $typeColors = [
                            'discussion'    => '#60a5fa',
                            'announcement'  => '#fb923c',
                            'question'      => '#22d3ee',
                            'idea'          => '#34d399',
                            'bug_report'    => '#f87171',
                            'release'       => '#a78bfa',
                            'success_story' => '#4ade80',
                            'showcase'      => '#f472b6',
                            'tutorial'      => '#818cf8',
                            'agent'         => '#c084fc',
                            'integration'   => '#2dd4bf',
                            'event'         => '#fb923c',
                            'article'       => '#94a3b8',
                            'poll'          => '#fbbf24',
                            'video'         => '#f87171',
                            'job'           => '#38bdf8',
                            'marketplace'   => '#4ade80',
                        ];
                        $color = $typeColors[$post->type] ?? '#71717a';
                    @endphp
                    <span style="font-size:11px;font-weight:600;padding:2px 9px;border-radius:100px;background:{{ $color }}18;color:{{ $color }};">
                        {{ str_replace('_', ' ', ucfirst($post->type)) }}
                    </span>
                    @if($post->community)
                        <a href="{{ route('communities.show', $post->community->slug) }}" style="font-size:12px;color:#71717a;text-decoration:none;">in {{ $post->community->name }}</a>
                    @endif
                    <span style="font-size:11px;color:#3f3f46;margin-left:auto;">{{ $post->created_at->diffForHumans() }}</span>
                </div>

                @if($post->title)
                    <h1 style="font-family:'Syne',sans-serif;font-size:1.35rem;font-weight:700;color:#f4f4f5;margin:0.6rem 0 0.75rem;letter-spacing:-0.01em;">{{ $post->title }}</h1>
                @endif

                <div style="font-size:14px;color:#a1a1aa;line-height:1.7;white-space:pre-wrap;">{{ $post->body }}</div>

                {{-- Hashtags --}}
                @if($post->hashtags->isNotEmpty())
                    <div style="display:flex;flex-wrap:wrap;gap:6px;margin-top:1rem;">
                        @foreach($post->hashtags as $tag)
                            <a href="{{ route('search', ['q' => $tag->name]) }}" style="font-size:12px;color:#c084fc;text-decoration:none;padding:2px 8px;background:rgba(192,132,252,0.08);border-radius:100px;border:1px solid rgba(192,132,252,0.15);">#{{ $tag->name }}</a>
                        @endforeach
                    </div>
                @endif

                {{-- Enrichment Tags --}}
                @if($post->enrichment?->tags)
                    <div style="display:flex;flex-wrap:wrap;gap:6px;margin-top:0.75rem;">
                        @foreach(array_slice($post->enrichment->tags, 0, 6) as $tag)
                            <span style="font-size:11px;color:#52525b;padding:2px 7px;background:rgba(255,255,255,0.04);border:1px solid rgba(255,255,255,0.07);border-radius:6px;">#{{ $tag }}</span>
                        @endforeach
                    </div>
                @endif

                {{-- AI Summary --}}
                @if($post->enrichment?->summary)
                    <div style="margin-top:1rem;padding:0.875rem 1rem;background:rgba(192,132,252,0.06);border:1px solid rgba(192,132,252,0.12);border-radius:8px;">
                        <div style="font-size:10px;font-weight:600;text-transform:uppercase;letter-spacing:0.09em;color:#c084fc;margin-bottom:4px;">AI Summary</div>
                        <p style="font-size:13px;color:#a1a1aa;margin:0;line-height:1.6;">{{ $post->enrichment->summary }}</p>
                    </div>
                @endif

                {{-- Actions row --}}
                <div style="display:flex;align-items:center;gap:16px;margin-top:1.25rem;padding-top:1rem;border-top:1px solid rgba(255,255,255,0.06);">
                    <button wire:click="react('{{ \App\Models\PulsePost::class }}', {{ $post->id }})" style="display:flex;align-items:center;gap:5px;font-size:13px;color:#71717a;background:none;border:none;cursor:pointer;padding:0;transition:color .13s;" onmouseover="this.style.color='#c084fc'" onmouseout="this.style.color='#71717a'">
                        <span class="material-symbols-rounded" style="font-size:16px;">thumb_up</span>
                        <span>{{ $post->reactions_count }}</span>
                    </button>
                    <span style="display:flex;align-items:center;gap:5px;font-size:13px;color:#71717a;">
                        <span class="material-symbols-rounded" style="font-size:16px;">chat_bubble_outline</span>
                        <span>{{ $post->comments_count }}</span>
                    </span>
                    <span style="display:flex;align-items:center;gap:5px;font-size:13px;color:#3f3f46;">
                        <span class="material-symbols-rounded" style="font-size:16px;">visibility</span>
                        <span>{{ number_format($post->views_count) }}</span>
                    </span>
                    @if($post->enrichment)
                        @php $sentimentColors = ['positive'=>'#4ade80','negative'=>'#f87171','neutral'=>'#71717a','mixed'=>'#fbbf24']; $sc = $sentimentColors[$post->enrichment->sentiment] ?? '#71717a'; @endphp
                        <span style="font-size:11px;padding:2px 8px;border-radius:100px;background:{{ $sc }}18;color:{{ $sc }};">{{ ucfirst($post->enrichment->sentiment ?? 'neutral') }}</span>
                    @endif
                    <button wire:click="$set('showReport', true)" style="display:flex;align-items:center;gap:4px;font-size:12px;color:#52525b;background:none;border:none;cursor:pointer;margin-left:auto;" onmouseover="this.style.color='#f87171'" onmouseout="this.style.color='#52525b'">
                        <span class="material-symbols-rounded" style="font-size:14px;">flag</span> Report
                    </button>
                </div>
            </div>
        </div>
    </div>

    {{-- Report form --}}
    @if($showReport)
        <div class="dot-card" style="padding:1.25rem 1.5rem;margin-bottom:1rem;border-color:rgba(248,113,113,0.25);">
            <div style="font-size:12px;font-weight:600;color:#f87171;margin-bottom:0.75rem;"><span class="material-symbols-rounded" style="font-size:14px;vertical-align:-2px;">flag</span> Report this post</div>
            <form wire:submit="submitReport">
                <div style="margin-bottom:0.75rem;">
                    <input wire:model="reportReason" type="text" placeholder="Reason (spam, harassment, misinformation…)" class="dot-input" />
                    @error('reportReason') <p style="font-size:11px;color:#f87171;margin-top:3px;">{{ $message }}</p> @enderror
                </div>
                <textarea wire:model="reportDetails" rows="2" placeholder="Additional details (optional)" class="dot-input" style="resize:none;margin-bottom:0.75rem;"></textarea>
                <div style="display:flex;gap:0.5rem;">
                    <button type="submit" class="dot-btn" style="font-size:12px;background:rgba(248,113,113,0.1);color:#f87171;border:1px solid rgba(248,113,113,0.2);">Submit</button>
                    <button type="button" wire:click="$set('showReport', false)" class="dot-btn dot-btn-ghost" style="font-size:12px;">Cancel</button>
                </div>
            </form>
        </div>
    @endif

    {{-- Comment Box --}}
    <div class="dot-card" style="padding:1.25rem 1.5rem;margin-bottom:1rem;">
        <div style="font-size:12px;font-weight:600;color:#52525b;text-transform:uppercase;letter-spacing:0.08em;margin-bottom:0.75rem;">Add a comment</div>
        <form wire:submit="addComment">
            <textarea wire:model="commentBody" rows="3" placeholder="Share your thoughts, experience or solution…" class="dot-input" style="resize:none;"></textarea>
            @error('commentBody') <p style="font-size:11px;color:#f87171;margin-top:4px;">{{ $message }}</p> @enderror
            <div style="display:flex;justify-content:flex-end;margin-top:0.6rem;">
                <button type="submit" class="dot-btn dot-btn-primary" wire:loading.attr="disabled">
                    <span wire:loading.remove>Comment</span>
                    <span wire:loading>Posting…</span>
                </button>
            </div>
        </form>
    </div>

    {{-- Comments --}}
    <div class="dot-card" style="padding:1.25rem 1.5rem;">
        <div style="font-size:12px;font-weight:600;color:#52525b;text-transform:uppercase;letter-spacing:0.08em;margin-bottom:1rem;">
            {{ $post->comments_count }} {{ Str::plural('comment', $post->comments_count) }}
        </div>

        @if($this->comments->isEmpty())
            <p style="font-size:13px;color:#3f3f46;text-align:center;padding:2rem 0;">No comments yet. Start the discussion.</p>
        @else
            <div style="display:flex;flex-direction:column;gap:1rem;">
                @foreach($this->comments as $comment)
                    <div style="display:flex;gap:0.75rem;" id="comment-{{ $comment->id }}">
                        <div style="width:32px;height:32px;border-radius:50%;background:rgba(255,255,255,0.06);border:1px solid rgba(255,255,255,0.08);display:flex;align-items:center;justify-content:center;font-size:12px;font-weight:700;color:#a1a1aa;flex-shrink:0;font-family:'Syne',sans-serif;">
                            {{ strtoupper(substr($comment->author->name, 0, 1)) }}
                        </div>
                        <div style="flex:1;min-width:0;">
                            <div style="display:flex;align-items:center;gap:8px;margin-bottom:4px;">
                                <a href="{{ route('profile.public', $comment->author->name) }}" style="font-size:13px;font-weight:600;color:#d4d4d8;text-decoration:none;">{{ $comment->author->name }}</a>
                                @if($comment->is_solution)
                                    <span style="font-size:10px;font-weight:600;padding:2px 7px;border-radius:100px;background:rgba(74,222,128,0.1);color:#4ade80;border:1px solid rgba(74,222,128,0.2);">
                                        <span class="material-symbols-rounded" style="font-size:11px;vertical-align:-1px;">check_circle</span> Solution
                                    </span>
                                @endif
                                <span style="font-size:11px;color:#3f3f46;margin-left:auto;">{{ $comment->created_at->diffForHumans() }}</span>
                            </div>
                            <p style="font-size:13px;color:#a1a1aa;line-height:1.6;margin:0 0 0.6rem;white-space:pre-wrap;">{{ $comment->body }}</p>
                            <div style="display:flex;align-items:center;gap:12px;">
                                <button wire:click="react('{{ \App\Models\PulseComment::class }}', {{ $comment->id }})" style="display:flex;align-items:center;gap:4px;font-size:12px;color:#52525b;background:none;border:none;cursor:pointer;padding:0;" onmouseover="this.style.color='#c084fc'" onmouseout="this.style.color='#52525b'">
                                    <span class="material-symbols-rounded" style="font-size:14px;">thumb_up</span>
                                    <span>{{ $comment->reactions_count }}</span>
                                </button>
                                <button wire:click="setReplyingTo({{ $comment->id }})" style="font-size:12px;color:#52525b;background:none;border:none;cursor:pointer;padding:0;" onmouseover="this.style.color='#c084fc'" onmouseout="this.style.color='#52525b'">Reply</button>
                                @if($post->user_id === auth()->id() && !$comment->is_solution)
                                    <button wire:click="markSolution({{ $comment->id }})" style="font-size:12px;color:#52525b;background:none;border:none;cursor:pointer;padding:0;" onmouseover="this.style.color='#4ade80'" onmouseout="this.style.color='#52525b'">Mark as solution</button>
                                @endif
                            </div>

                            {{-- Reply form --}}
                            @if($replyingTo === $comment->id)
                                <form wire:submit="addReply" style="margin-top:0.75rem;">
                                    <textarea wire:model="replyBody" rows="2" placeholder="Write a reply…" class="dot-input" style="resize:none;font-size:12px;"></textarea>
                                    <div style="display:flex;gap:8px;margin-top:4px;">
                                        <button type="submit" class="dot-btn dot-btn-primary" style="font-size:12px;padding:5px 12px;">Reply</button>
                                        <button type="button" wire:click="setReplyingTo(null)" class="dot-btn dot-btn-ghost" style="font-size:12px;padding:5px 12px;">Cancel</button>
                                    </div>
                                </form>
                            @endif

                            {{-- Nested replies --}}
                            @if($comment->replies->isNotEmpty())
                                <div style="margin-top:0.75rem;padding-left:1rem;border-left:2px solid rgba(255,255,255,0.06);display:flex;flex-direction:column;gap:0.75rem;">
                                    @foreach($comment->replies as $reply)
                                        <div style="display:flex;gap:0.6rem;">
                                            <div style="width:26px;height:26px;border-radius:50%;background:rgba(255,255,255,0.05);display:flex;align-items:center;justify-content:center;font-size:10px;font-weight:700;color:#71717a;flex-shrink:0;font-family:'Syne',sans-serif;">
                                                {{ strtoupper(substr($reply->author->name, 0, 1)) }}
                                            </div>
                                            <div>
                                                <div style="display:flex;align-items:center;gap:8px;margin-bottom:3px;">
                                                    <span style="font-size:12px;font-weight:600;color:#d4d4d8;">{{ $reply->author->name }}</span>
                                                    <span style="font-size:10px;color:#3f3f46;">{{ $reply->created_at->diffForHumans() }}</span>
                                                </div>
                                                <p style="font-size:12px;color:#71717a;line-height:1.6;margin:0;">{{ $reply->body }}</p>
                                            </div>
                                        </div>
                                    @endforeach
                                </div>
                            @endif
                        </div>
                    </div>
                @endforeach
            </div>
        @endif
    </div>
</div>
