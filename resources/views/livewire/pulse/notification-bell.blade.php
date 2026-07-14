<div wire:poll.30s x-data="{ open: $wire.entangle('open') }" style="position:relative;">
    <button @click="$wire.toggleOpen()" style="position:relative;width:30px;height:30px;border-radius:7px;border:1px solid rgba(255,255,255,0.08);background:rgba(255,255,255,0.04);display:flex;align-items:center;justify-content:center;color:#71717a;cursor:pointer;transition:all .13s;" onmouseover="this.style.background='rgba(255,255,255,0.09)';this.style.color='#d4d4d8'" onmouseout="this.style.background='rgba(255,255,255,0.04)';this.style.color='#71717a'">
        <span class="material-symbols-rounded" style="font-size:17px;">notifications</span>
        @if($this->unreadCount > 0)
            <span style="position:absolute;top:-3px;right:-3px;min-width:16px;height:16px;border-radius:100px;background:#c084fc;color:#09090b;font-size:9px;font-weight:700;display:flex;align-items:center;justify-content:center;padding:0 3px;font-family:'JetBrains Mono',monospace;">{{ min($this->unreadCount, 99) }}</span>
        @endif
    </button>

    @if($open)
        <div style="position:absolute;right:0;top:38px;width:340px;background:#1a1a1f;border:1px solid rgba(255,255,255,0.1);border-radius:12px;box-shadow:0 16px 40px rgba(0,0,0,0.5);z-index:100;overflow:hidden;">
            <div style="display:flex;align-items:center;justify-content:space-between;padding:0.875rem 1rem;border-bottom:1px solid rgba(255,255,255,0.07);">
                <span style="font-size:13px;font-weight:600;color:#f4f4f5;">Notifications</span>
                @if($this->unreadCount > 0)
                    <button wire:click="markAllRead" style="font-size:11px;color:#c084fc;background:none;border:none;cursor:pointer;padding:0;">Mark all read</button>
                @endif
            </div>
            <div style="max-height:380px;overflow-y:auto;scrollbar-width:thin;scrollbar-color:rgba(255,255,255,0.07) transparent;">
                @if($this->recent->isEmpty())
                    <p style="font-size:13px;color:#3f3f46;text-align:center;padding:2rem;">No notifications.</p>
                @else
                    @foreach($this->recent as $n)
                        @php $data = is_array($n->data) ? $n->data : []; @endphp
                        <a href="{{ $data['url'] ?? route('notifications.index') }}" style="display:flex;align-items:flex-start;gap:0.75rem;padding:0.75rem 1rem;text-decoration:none;background:{{ $n->read_at ? 'transparent' : 'rgba(192,132,252,0.04)' }};border-bottom:1px solid rgba(255,255,255,0.04);transition:background .13s;" onmouseover="this.style.background='rgba(255,255,255,0.04)'" onmouseout="this.style.background='{{ $n->read_at ? 'transparent' : 'rgba(192,132,252,0.04)' }}'">
                            <span class="material-symbols-rounded" style="font-size:16px;color:#c084fc;margin-top:1px;flex-shrink:0;">
                                {{ match($data['type'] ?? '') { 'comment' => 'chat_bubble', 'follow' => 'person_add', 'solution' => 'check_circle', 'mention' => 'alternate_email', default => 'notifications' } }}
                            </span>
                            <div style="flex:1;min-width:0;">
                                <p style="font-size:12px;color:{{ $n->read_at ? '#71717a' : '#d4d4d8' }};margin:0 0 2px;line-height:1.4;">{{ $data['message'] ?? 'New notification' }}</p>
                                <span style="font-size:10px;color:#3f3f46;">{{ $n->created_at->diffForHumans() }}</span>
                            </div>
                            @if(!$n->read_at)
                                <div style="width:6px;height:6px;border-radius:50%;background:#c084fc;flex-shrink:0;margin-top:4px;"></div>
                            @endif
                        </a>
                    @endforeach
                @endif
            </div>
            <div style="padding:0.75rem 1rem;border-top:1px solid rgba(255,255,255,0.07);text-align:center;">
                <a href="{{ route('notifications.index') }}" style="font-size:12px;color:#c084fc;text-decoration:none;">View all notifications</a>
            </div>
        </div>
        <div @click="$wire.open = false" style="position:fixed;inset:0;z-index:99;"></div>
    @endif
</div>
