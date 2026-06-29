<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            Community Feed
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-5xl mx-auto sm:px-6 lg:px-8">

            {{-- KPI Strip --}}
            <div class="grid grid-cols-3 gap-4 mb-6">
                <div class="bg-white rounded-xl shadow p-5">
                    <div class="text-sm text-gray-500">Total Posts</div>
                    <div class="text-3xl font-bold text-indigo-600 mt-1">{{ $totalPosts }}</div>
                </div>
                <div class="bg-white rounded-xl shadow p-5">
                    <div class="text-sm text-gray-500">Communities</div>
                    <div class="text-3xl font-bold text-purple-600 mt-1">{{ $totalCommunities }}</div>
                </div>
                <div class="bg-white rounded-xl shadow p-5">
                    <div class="text-sm text-gray-500">My Points</div>
                    <div class="text-3xl font-bold text-amber-500 mt-1">{{ $myPoints }}</div>
                </div>
            </div>

            {{-- Create Post --}}
            <livewire:pulse.create-post />

            {{-- Feed --}}
            <livewire:pulse.home-feed />

            {{-- Communities --}}
            <div class="mt-6">
                <livewire:pulse.community-list />
            </div>
        </div>
    </div>
</x-app-layout>
