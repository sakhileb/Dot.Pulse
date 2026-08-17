<x-app-layout>

{{-- data-post-id anchors resources/js/pulse-realtime.js's per-post
     comment-channel subscription; a stable page-level element rather than
     something inside the Livewire component's own re-rendered root. --}}
<div data-post-id="{{ $post->id }}" style="padding:2rem 2.5rem 3rem;max-width:1100px;">

    <a href="{{ route('dashboard') }}" style="font-size:0.78rem;color:#52525b;text-decoration:none;">&larr; Back to feed</a>

    <div style="margin-top:1rem;">
        <livewire:pulse.post-show :post="$post" />
    </div>

</div>

</x-app-layout>
