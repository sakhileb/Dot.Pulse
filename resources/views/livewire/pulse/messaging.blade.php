<div style="display:grid;grid-template-columns:280px 1fr;height:calc(100vh - 54px);overflow:hidden;">

    {{-- Left: Conversation list --}}
    <div style="border-right:1px solid rgba(255,255,255,0.06);display:flex;flex-direction:column;overflow:hidden;">
        <div style="padding:1rem;border-bottom:1px solid rgba(255,255,255,0.06);flex-shrink:0;">
            <div style="font-family:'Syne',sans-serif;font-size:0.875rem;font-weight:700;color:#f4f4f5;margin-bottom:0.75rem;">Messages</div>
            {{-- New conversation search --}}
            <div style="position:relative;">
                <span class="material-symbols-rounded" style="position:absolute;left:9px;top:50%;transform:translateY(-50%);font-size:14px;color:#52525b;pointer-events:none;">person_search</span>
                <input wire:model.live.debounce.300ms="searchUser" type="text" placeholder="Find someone…" class="dot-input" style="font-size:12px;padding-left:30px;" />
            </div>

            {{-- Search results --}}
            @if($this->userSearchResults->isNotEmpty())
                <div style="position:absolute;z-index:50;background:#1a1a1f;border:1px solid rgba(255,255,255,0.1);border-radius:8px;width:248px;margin-top:4px;overflow:hidden;">
                    @foreach($this->userSearchResults as $u)
                        <button wire:click="startConversationWith({{ $u->id }})" style="display:flex;align-items:center;gap:0.6rem;padding:0.6rem 0.875rem;width:100%;border:none;background:none;cursor:pointer;text-align:left;transition:background .13s;" onmouseover="this.style.background='rgba(255,255,255,0.05)'" onmouseout="this.style.background='none'">
                            <div style="width:28px;height:28px;border-radius:50%;background:rgba(192,132,252,0.12);display:flex;align-items:center;justify-content:center;font-size:11px;font-weight:700;color:#c084fc;flex-shrink:0;font-family:'Syne',sans-serif;">{{ strtoupper(substr($u->name,0,1)) }}</div>
                            <span style="font-size:13px;color:#f4f4f5;">{{ $u->name }}</span>
                        </button>
                    @endforeach
                </div>
            @endif
        </div>

        <div style="flex:1;overflow-y:auto;scrollbar-width:thin;scrollbar-color:rgba(255,255,255,0.07) transparent;">
            @if($this->conversations->isEmpty())
                <div style="padding:2rem 1rem;text-align:center;color:#3f3f46;">
                    <span class="material-symbols-rounded" style="font-size:32px;display:block;margin-bottom:0.5rem;">chat_bubble_outline</span>
                    <p style="font-size:12px;">No conversations yet. Search for someone to start chatting.</p>
                </div>
            @else
                @foreach($this->conversations as $conv)
                    @php $other = $this->otherParticipant($conv); @endphp
                    <button wire:click="openConversation({{ $conv->id }})" style="display:flex;align-items:center;gap:0.75rem;padding:0.875rem 1rem;width:100%;border:none;cursor:pointer;text-align:left;background:{{ $conversationId === $conv->id ? 'rgba(192,132,252,0.08)' : 'none' }};border-left:3px solid {{ $conversationId === $conv->id ? '#c084fc' : 'transparent' }};transition:background .13s;">
                        <div style="width:36px;height:36px;border-radius:50%;background:rgba(192,132,252,0.12);border:1px solid rgba(192,132,252,0.2);display:flex;align-items:center;justify-content:center;font-size:14px;font-weight:700;color:#c084fc;flex-shrink:0;font-family:'Syne',sans-serif;">
                            {{ $other ? strtoupper(substr($other->name,0,1)) : '?' }}
                        </div>
                        <div style="flex:1;min-width:0;">
                            <div style="font-size:13px;font-weight:600;color:#f4f4f5;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;">{{ $other?->name ?? 'Group' }}</div>
                            @if($conv->lastMessage)
                                <div style="font-size:11px;color:#52525b;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;">{{ $conv->lastMessage->sender->name }}: {{ \Str::limit($conv->lastMessage->body, 30) }}</div>
                            @endif
                        </div>
                        <div style="font-size:10px;color:#3f3f46;flex-shrink:0;">{{ $conv->updated_at->diffForHumans(short: true) }}</div>
                    </button>
                @endforeach
            @endif
        </div>
    </div>

    {{-- Right: Active conversation --}}
    <div style="display:flex;flex-direction:column;overflow:hidden;">
        @if(! $this->activeConversation)
            <div style="flex:1;display:flex;flex-direction:column;align-items:center;justify-content:center;color:#3f3f46;">
                <span class="material-symbols-rounded" style="font-size:48px;margin-bottom:0.75rem;">forum</span>
                <p style="font-size:14px;">Select a conversation or search for someone to message</p>
            </div>
        @else
            @php
                $conv  = $this->activeConversation;
                $other = $this->otherParticipant($conv);
            @endphp

            {{-- Chat header --}}
            <div style="padding:0.875rem 1.25rem;border-bottom:1px solid rgba(255,255,255,0.06);display:flex;align-items:center;gap:0.75rem;flex-shrink:0;background:rgba(9,9,11,0.4);">
                <div style="width:34px;height:34px;border-radius:50%;background:rgba(192,132,252,0.12);border:1px solid rgba(192,132,252,0.2);display:flex;align-items:center;justify-content:center;font-size:13px;font-weight:700;color:#c084fc;flex-shrink:0;font-family:'Syne',sans-serif;">
                    {{ $other ? strtoupper(substr($other->name,0,1)) : '?' }}
                </div>
                <div>
                    <div style="font-size:13px;font-weight:600;color:#f4f4f5;">{{ $other?->name ?? 'Group' }}</div>
                    @if($other)
                        <a href="{{ route('profile.public', $other->name) }}" style="font-size:11px;color:#52525b;text-decoration:none;">View profile</a>
                    @endif
                </div>
            </div>

            {{-- Messages --}}
            <div style="flex:1;overflow-y:auto;padding:1rem 1.25rem;display:flex;flex-direction:column;gap:0.625rem;scrollbar-width:thin;scrollbar-color:rgba(255,255,255,0.07) transparent;" id="msg-scroll">
                @if($conv->messages->isEmpty())
                    <p style="font-size:12px;color:#3f3f46;text-align:center;margin-top:2rem;">No messages yet. Say hello!</p>
                @else
                    @foreach($conv->messages as $msg)
                        @php $isMine = $msg->user_id === auth()->id(); @endphp
                        <div style="display:flex;align-items:flex-end;gap:0.5rem;{{ $isMine ? 'flex-direction:row-reverse;' : '' }}">
                            @if(!$isMine)
                                <div style="width:26px;height:26px;border-radius:50%;background:rgba(255,255,255,0.05);display:flex;align-items:center;justify-content:center;font-size:10px;font-weight:700;color:#71717a;flex-shrink:0;font-family:'Syne',sans-serif;">{{ strtoupper(substr($msg->sender->name,0,1)) }}</div>
                            @endif
                            <div style="max-width:65%;padding:8px 12px;border-radius:{{ $isMine ? '12px 12px 4px 12px' : '12px 12px 12px 4px' }};background:{{ $isMine ? 'rgba(192,132,252,0.18)' : 'rgba(255,255,255,0.06)' }};border:1px solid {{ $isMine ? 'rgba(192,132,252,0.25)' : 'rgba(255,255,255,0.07)' }};">
                                <p style="font-size:13px;color:#f4f4f5;margin:0;line-height:1.5;word-break:break-word;">{{ $msg->body }}</p>
                                <div style="font-size:10px;color:{{ $isMine ? 'rgba(192,132,252,0.6)' : '#3f3f46' }};margin-top:3px;text-align:right;">{{ $msg->created_at->format('g:i A') }}</div>
                            </div>
                        </div>
                    @endforeach
                @endif
            </div>

            {{-- Message input --}}
            <div style="padding:0.875rem 1.25rem;border-top:1px solid rgba(255,255,255,0.06);flex-shrink:0;">
                <form wire:submit="sendMessage" style="display:flex;gap:0.625rem;align-items:flex-end;">
                    <textarea wire:model="messageBody" rows="1" placeholder="Write a message…" class="dot-input" style="resize:none;flex:1;max-height:100px;" onkeydown="if(event.key==='Enter'&&!event.shiftKey){event.preventDefault();$wire.sendMessage();}"></textarea>
                    <button type="submit" class="dot-btn dot-btn-primary" style="padding:8px 14px;flex-shrink:0;" wire:loading.attr="disabled">
                        <span class="material-symbols-rounded" style="font-size:16px;">send</span>
                    </button>
                </form>
            </div>
        @endif
    </div>
</div>

<script>
    // Auto-scroll messages to bottom
    document.addEventListener('livewire:navigated', scrollMessages);
    document.addEventListener('livewire:update', scrollMessages);
    function scrollMessages() {
        const el = document.getElementById('msg-scroll');
        if (el) el.scrollTop = el.scrollHeight;
    }
    scrollMessages();
</script>
