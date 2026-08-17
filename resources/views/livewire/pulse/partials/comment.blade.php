@php
    $author = $comment->author;
@endphp
<div style="display:flex;align-items:flex-start;gap:10px;">
    <a href="{{ route('users.show', $author) }}" class="dot-avatar" style="width:28px;height:28px;font-size:11px;">
        {{ strtoupper(substr($author->name, 0, 1)) }}
    </a>
    <div style="flex:1;min-width:0;">
        <div style="display:flex;align-items:center;gap:8px;">
            <a href="{{ route('users.show', $author) }}" style="font-size:12.5px;font-weight:600;color:#f4f4f5;text-decoration:none;">{{ $author->name }}</a>
            <livewire:pulse.follow-button :user="$author" :compact="true" wire:key="follow-{{ $author->id }}-comment-{{ $comment->id }}" />
            @if($comment->is_solution)
                <span class="dot-badge dot-badge-success">✓ Solution</span>
            @endif
            <span style="font-size:11px;color:#3f3f46;">{{ $comment->created_at->diffForHumans() }}</span>
        </div>
        <p style="font-size:13px;color:#a1a1aa;line-height:1.55;margin:4px 0 0;">{{ $comment->body }}</p>
        <div style="display:flex;align-items:center;gap:14px;margin-top:6px;">
            <button wire:click="react({{ $comment->id }})" style="display:flex;align-items:center;gap:4px;background:none;border:none;font-size:11.5px;color:#71717a;cursor:pointer;font-family:'Inter',sans-serif;">
                <span>👍</span>
                <span>{{ $comment->reactions_count }}</span>
            </button>
            @if(! $isReply && $post->user_id === auth()->id() && ! $comment->is_solution)
                <button wire:click="markSolution({{ $comment->id }})" style="background:none;border:none;font-size:11.5px;color:#71717a;cursor:pointer;font-family:'Inter',sans-serif;">Mark as solution</button>
            @endif
        </div>
    </div>
</div>
