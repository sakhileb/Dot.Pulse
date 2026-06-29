<div class="dot-card" style="padding:1.5rem;">
    <div class="flex items-center justify-between mb-4">
        <h3 style="font-family:'Syne',sans-serif;font-size:0.875rem;font-weight:700;color:#f4f4f5;">Communities</h3>
        <input
            wire:model.live="search"
            type="text"
            placeholder="Search communities..."
            class="border border-gray-300 rounded px-3 py-1.5 text-xs focus:ring-2 focus:ring-indigo-500 focus:outline-none w-48"
        />
    </div>

    @if($this->communities->isEmpty())
        <p class="text-sm text-gray-400 py-6 text-center">No communities found.</p>
    @else
        <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
            @foreach($this->communities as $community)
                @php $isMember = $community->hasMember(auth()->user()); @endphp
                <div class="border border-gray-200 rounded-lg p-4 flex items-start justify-between gap-3">
                    <div class="min-w-0">
                        <p class="text-sm font-semibold text-gray-800 truncate">{{ $community->name }}</p>
                        @if($community->industry)
                            <p class="text-xs text-indigo-500 font-medium mb-1">{{ $community->industry }}</p>
                        @endif
                        <p class="text-xs text-gray-400 line-clamp-2">{{ $community->description }}</p>
                        <p class="text-xs text-gray-400 mt-1">{{ number_format($community->members_count) }} members</p>
                    </div>
                    <div class="shrink-0">
                        @if($isMember)
                            <button wire:click="leave({{ $community->id }})" class="text-xs px-3 py-1 border border-gray-300 rounded text-gray-500 hover:border-red-300 hover:text-red-500 transition-colors">
                                Leave
                            </button>
                        @else
                            <button wire:click="join({{ $community->id }})" class="text-xs px-3 py-1 bg-indigo-600 text-white rounded hover:bg-indigo-700 transition-colors">
                                Join
                            </button>
                        @endif
                    </div>
                </div>
            @endforeach
        </div>
    @endif
</div>
