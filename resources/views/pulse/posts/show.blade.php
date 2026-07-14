<x-app-layout>
<div style="padding:2rem 2.5rem 3rem;max-width:900px;">
    <div style="margin-bottom:1.5rem;">
        <a href="javascript:history.back()" style="display:inline-flex;align-items:center;gap:5px;font-size:12px;color:#71717a;text-decoration:none;">
            <span class="material-symbols-rounded" style="font-size:14px;">arrow_back</span>
            Back
        </a>
    </div>
    <livewire:pulse.post-detail :post="$post" />
</div>
</x-app-layout>
