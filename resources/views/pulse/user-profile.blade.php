<x-app-layout>

<div style="padding:2rem 2.5rem 3rem;max-width:1100px;">

    <a href="{{ route('dashboard') }}" style="font-size:0.78rem;color:#52525b;text-decoration:none;">&larr; Back to feed</a>

    <div style="margin-top:1rem;">
        <livewire:pulse.user-profile :profile-user="$profileUser" />
    </div>

</div>

</x-app-layout>
