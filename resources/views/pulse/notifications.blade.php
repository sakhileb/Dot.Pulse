<x-app-layout>
<div style="padding:2rem 2.5rem 3rem;max-width:800px;">
    <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:2rem;">
        <div>
            <h1 style="font-family:'Syne',sans-serif;font-size:1.4rem;font-weight:700;color:#f4f4f5;margin:0 0 0.2rem;letter-spacing:-0.01em;">Notifications</h1>
            <p style="font-size:0.78rem;color:#52525b;margin:0;">Mentions, replies, reactions and ecosystem updates</p>
        </div>
    </div>

    @php
        $notifications = auth()->user()->notifications()->latest()->limit(30)->get();
    @endphp

    @if($notifications->isEmpty())
        <div style="text-align:center;padding:5rem 1rem;color:#3f3f46;">
            <span class="material-symbols-rounded" style="font-size:48px;display:block;margin-bottom:0.75rem;">notifications_none</span>
            <p style="font-size:14px;">You're all caught up. No new notifications.</p>
        </div>
    @else
        <div style="display:flex;flex-direction:column;gap:0.5rem;">
            @foreach($notifications as $notification)
                <div class="dot-card" style="padding:1rem 1.25rem;display:flex;align-items:flex-start;gap:0.75rem;{{ $notification->read_at ? 'opacity:0.65;' : '' }}">
                    <div style="width:36px;height:36px;border-radius:50%;background:rgba(192,132,252,0.1);border:1px solid rgba(192,132,252,0.18);display:flex;align-items:center;justify-content:center;flex-shrink:0;">
                        <span class="material-symbols-rounded" style="font-size:18px;color:#c084fc;">notifications</span>
                    </div>
                    <div style="flex:1;min-width:0;">
                        @if(is_array($notification->data))
                            <p style="font-size:13px;color:#d4d4d8;margin:0 0 3px;">{{ $notification->data['message'] ?? 'You have a new notification.' }}</p>
                        @else
                            <p style="font-size:13px;color:#d4d4d8;margin:0 0 3px;">New notification.</p>
                        @endif
                        <span style="font-size:11px;color:#3f3f46;">{{ $notification->created_at->diffForHumans() }}</span>
                    </div>
                    @if(!$notification->read_at)
                        <div style="width:7px;height:7px;border-radius:50%;background:#c084fc;flex-shrink:0;margin-top:4px;"></div>
                    @endif
                </div>
            @endforeach
        </div>
    @endif
</div>
</x-app-layout>
