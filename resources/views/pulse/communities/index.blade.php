<x-app-layout>
<div style="padding:2rem 2.5rem 3rem;max-width:1200px;">
    <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:2rem;flex-wrap:wrap;gap:1rem;">
        <div>
            <h1 style="font-family:'Syne',sans-serif;font-size:1.4rem;font-weight:700;color:#f4f4f5;margin:0 0 0.2rem;letter-spacing:-0.01em;">Communities</h1>
            <p style="font-size:0.78rem;color:#52525b;margin:0;">Discover and join communities across the ecosystem</p>
        </div>
        <a href="{{ route('communities.create') }}" class="dot-btn dot-btn-primary" style="text-decoration:none;">
            <span class="material-symbols-rounded" style="font-size:15px;">add_circle</span>
            Create Community
        </a>
    </div>
    <livewire:pulse.community-list />
</div>
</x-app-layout>
