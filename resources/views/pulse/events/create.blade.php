<x-app-layout>
<div style="padding:2rem 2.5rem 3rem;max-width:900px;">
    <div style="margin-bottom:2rem;">
        <a href="{{ route('events.index') }}" style="display:inline-flex;align-items:center;gap:5px;font-size:12px;color:#71717a;text-decoration:none;margin-bottom:1rem;">
            <span class="material-symbols-rounded" style="font-size:14px;">arrow_back</span>
            Events
        </a>
        <h1 style="font-family:'Syne',sans-serif;font-size:1.4rem;font-weight:700;color:#f4f4f5;margin:0 0 0.2rem;letter-spacing:-0.01em;">Create an Event</h1>
        <p style="font-size:0.78rem;color:#52525b;margin:0;">Host a webinar, AMA, product launch or community meetup</p>
    </div>
    <livewire:pulse.create-event-form />
</div>
</x-app-layout>
