<div>
    @if($this->hashtags->isEmpty())
        <p style="font-size:11px;color:#3f3f46;text-align:center;padding:0.5rem;">No trending topics yet.</p>
    @else
        <div style="display:flex;flex-direction:column;gap:2px;">
            @foreach($this->hashtags as $i => $tag)
                <a href="{{ route('search', ['q' => $tag->name]) }}" style="display:flex;align-items:center;justify-content:space-between;padding:6px 0;text-decoration:none;group;">
                    <div style="display:flex;align-items:center;gap:8px;">
                        <span style="font-size:10px;color:#3f3f46;width:14px;text-align:right;font-family:'JetBrains Mono',monospace;">{{ $i + 1 }}</span>
                        <span style="font-size:13px;color:#c084fc;font-weight:500;">#{{ $tag->name }}</span>
                    </div>
                    <span style="font-size:10px;color:#3f3f46;font-family:'JetBrains Mono',monospace;">{{ number_format($tag->posts_count) }}</span>
                </a>
            @endforeach
        </div>
    @endif
</div>
