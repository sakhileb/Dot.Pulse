<div class="max-w-3xl">
    @if($this->heldPosts->isEmpty())
        <div class="bg-white rounded-xl shadow p-5 text-sm text-gray-400">
            Nothing waiting for review.
        </div>
    @endif

    @foreach($this->heldPosts as $post)
        <div class="bg-white rounded-xl shadow p-5 mb-4">
            <div class="flex items-center justify-between mb-2">
                <div>
                    <p class="text-sm font-medium text-gray-800">{{ $post->author->name }}</p>
                    <p class="text-xs text-gray-400">
                        {{ $post->community?->name ?? 'No community' }} &middot; {{ ucfirst($post->type) }}
                    </p>
                </div>
                <span class="text-xs px-2 py-0.5 rounded-full {{ $post->enrichment?->isRejected() ? 'bg-red-100 text-red-600' : 'bg-yellow-100 text-yellow-700' }}">
                    {{ $post->enrichment?->isRejected() ? 'AI recommended reject' : 'AI flagged for review' }}
                </span>
            </div>

            @if($post->title)
                <p class="text-sm font-semibold text-gray-800 mb-1">{{ $post->title }}</p>
            @endif
            <p class="text-sm text-gray-600 mb-3">{{ $post->body }}</p>

            @if($post->enrichment)
                <div class="text-xs text-gray-400 mb-3 space-y-1">
                    @if($post->enrichment->moderation_rationale)
                        <p>AI rationale: {{ $post->enrichment->moderation_rationale }}</p>
                    @endif
                    <p>Spam score: {{ number_format($post->enrichment->spam_score, 2) }} &middot; Safety score: {{ number_format($post->enrichment->safety_score, 2) }}</p>
                </div>
            @endif

            @if($rejectingPostId === $post->id)
                <div class="flex items-center gap-2">
                    <input type="text" wire:model="rejectReason" placeholder="Reason for rejecting"
                        class="flex-1 border border-gray-300 rounded px-3 py-1.5 text-sm focus:ring-2 focus:ring-indigo-500 focus:outline-none" />
                    <button wire:click="confirmReject({{ $post->id }})" class="text-xs px-3 py-1.5 rounded bg-red-600 hover:bg-red-700 text-white font-medium">
                        Confirm Reject
                    </button>
                    <button wire:click="cancelReject" class="text-xs px-3 py-1.5 rounded bg-gray-200 hover:bg-gray-300 text-gray-700 font-medium">
                        Cancel
                    </button>
                </div>
            @else
                <div class="flex items-center gap-2">
                    <button wire:click="approve({{ $post->id }})" class="text-xs px-3 py-1.5 rounded bg-green-600 hover:bg-green-700 text-white font-medium">
                        Approve
                    </button>
                    <button wire:click="promptReject({{ $post->id }})" class="text-xs px-3 py-1.5 rounded bg-red-600 hover:bg-red-700 text-white font-medium">
                        Reject
                    </button>
                </div>
            @endif
        </div>
    @endforeach
</div>
