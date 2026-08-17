<div style="display:flex;flex-direction:column;gap:1rem;">
    <div class="dot-card" style="padding:1.25rem 1.5rem;">
        <div style="display:flex;align-items:flex-start;gap:12px;">
            <a href="{{ route('users.show', $post->author) }}" class="dot-avatar" style="width:36px;height:36px;font-size:13px;">
                {{ strtoupper(substr($post->author->name, 0, 1)) }}
            </a>
            <div style="flex:1;min-width:0;">
                <div style="display:flex;align-items:center;gap:8px;margin-bottom:2px;">
                    <a href="{{ route('users.show', $post->author) }}" style="font-size:13.5px;font-weight:600;color:#f4f4f5;text-decoration:none;">{{ $post->author->name }}</a>
                    <livewire:pulse.follow-button :user="$post->author" :compact="true" wire:key="follow-{{ $post->author->id }}-postshow" />
                    <span class="dot-badge dot-badge-accent">
                        {{ str_replace('_', ' ', ucfirst($post->type)) }}
                    </span>
                    @if($post->community)
                        <a href="{{ route('communities.show', $post->community) }}" style="font-size:11px;color:#71717a;text-decoration:none;">in {{ $post->community->name }}</a>
                    @endif
                    <span style="font-size:11px;color:#3f3f46;margin-left:auto;white-space:nowrap;">{{ $post->created_at->diffForHumans() }}</span>
                </div>

                @if($post->title)
                    <h4 style="font-family:'Syne',sans-serif;font-size:17px;font-weight:700;color:#f4f4f5;margin:6px 0 6px;">{{ $post->title }}</h4>
                @endif

                <p style="font-size:13.5px;color:#a1a1aa;line-height:1.65;margin:0;white-space:pre-line;">{{ $post->body }}</p>

                @if($post->enrichment?->tags)
                    <div style="display:flex;flex-wrap:wrap;gap:5px;margin-top:10px;">
                        @foreach($post->enrichment->tags as $tag)
                            <span class="dot-badge dot-badge-neutral" style="font-family:'JetBrains Mono',monospace;">#{{ $tag }}</span>
                        @endforeach
                    </div>
                @endif

                <div style="display:flex;align-items:center;gap:16px;margin-top:14px;">
                    <span style="display:flex;align-items:center;gap:5px;font-size:12px;color:#71717a;">
                        <span>👍</span>
                        <span>{{ $post->reactions_count }}</span>
                    </span>
                    <span style="display:flex;align-items:center;gap:5px;font-size:12px;color:#71717a;">
                        <span>💬</span>
                        <span>{{ $post->comments_count }}</span>
                    </span>
                </div>
            </div>
        </div>
    </div>

    <div class="dot-card" style="padding:1.25rem 1.5rem;">
        <h3 style="font-family:'Syne',sans-serif;font-size:13.5px;font-weight:700;color:#f4f4f5;margin:0 0 0.9rem;">{{ $post->comments_count }} {{ Str::plural('comment', $post->comments_count) }}</h3>

        <form wire:submit="postComment" style="margin-bottom:1.5rem;">
            <textarea wire:model="newCommentBody" rows="3" placeholder="Write a comment…" class="dot-input"></textarea>
            @error('newCommentBody')<p style="color:#f87171;font-size:11px;margin:4px 0 0;">{{ $message }}</p>@enderror
            <button type="submit" class="dot-btn dot-btn-primary" style="margin-top:8px;">
                Post comment
            </button>
        </form>

        @if($this->comments->isEmpty())
            <p style="font-size:13px;color:#52525b;text-align:center;padding:1.5rem 0;margin:0;">No comments yet. Start the conversation.</p>
        @else
            <div style="display:flex;flex-direction:column;gap:1.25rem;">
                @foreach($this->comments as $comment)
                    <div>
                        @include('livewire.pulse.partials.comment', ['comment' => $comment, 'isReply' => false])

                        @if($comment->replies->isNotEmpty())
                            <div style="margin-left:32px;margin-top:12px;display:flex;flex-direction:column;gap:12px;border-left:1px solid rgba(255,255,255,0.07);padding-left:16px;">
                                @foreach($comment->replies as $reply)
                                    @include('livewire.pulse.partials.comment', ['comment' => $reply, 'isReply' => true])
                                @endforeach
                            </div>
                        @endif

                        @if($replyingToId === $comment->id)
                            <div style="margin-left:32px;margin-top:12px;">
                                <form wire:submit="postReply({{ $comment->id }})">
                                    <textarea wire:model="replyBody" rows="2" placeholder="Write a reply…" class="dot-input"></textarea>
                                    @error('replyBody')<p style="color:#f87171;font-size:11px;margin:4px 0 0;">{{ $message }}</p>@enderror
                                    <div style="display:flex;gap:10px;margin-top:8px;">
                                        <button type="submit" class="dot-btn dot-btn-primary" style="padding:5px 12px;font-size:12px;">Reply</button>
                                        <button type="button" wire:click="cancelReply" style="background:none;border:none;font-size:12px;color:#71717a;cursor:pointer;font-family:'Inter',sans-serif;">Cancel</button>
                                    </div>
                                </form>
                            </div>
                        @else
                            <button wire:click="startReply({{ $comment->id }})" style="margin-left:4px;margin-top:6px;background:none;border:none;font-size:11.5px;color:#71717a;cursor:pointer;font-family:'Inter',sans-serif;">Reply</button>
                        @endif
                    </div>
                @endforeach
            </div>
        @endif
    </div>
</div>
