<x-app-layout>
<div style="padding:2rem 2.5rem 3rem;max-width:1100px;">
    <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:2rem;flex-wrap:wrap;gap:1rem;">
        <div>
            <h1 style="font-family:'Syne',sans-serif;font-size:1.4rem;font-weight:700;color:#f4f4f5;margin:0 0 0.2rem;letter-spacing:-0.01em;">
                <span class="material-symbols-rounded" style="font-size:1.2rem;vertical-align:-2px;color:#f87171;margin-right:4px;">shield</span>
                Moderation Queue
            </h1>
            <p style="font-size:0.78rem;color:#52525b;margin:0;">Review and act on content that requires human moderation</p>
        </div>
    </div>
    <livewire:pulse.moderation-queue />
</div>
</x-app-layout>
