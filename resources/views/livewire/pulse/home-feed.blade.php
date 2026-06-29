<div>
    <div class="flex items-center justify-between mb-4">
        <h3 class="text-lg font-semibold text-gray-800">Community Feed</h3>
        <select wire:model.live="filterType" class="border border-gray-300 rounded text-xs px-2 py-1">
            <option value="">All types</option>
            @foreach(\App\Models\PulsePost::$types as $type)
                <option value="{{ $type }}">{{ ucfirst(str_replace('_', ' ', $type)) }}</option>
            @endforeach
        </select>
    </div>

    @if($this->posts->isEmpty())
        <div class="text-center py-16 text-gray-400">
            <p class="text-sm">No posts yet. Be the first to share something with the community.</p>
        </div>
    @else
        <div class="space-y-4">
            @foreach($this->posts as $post)
                <div class="bg-white rounded-xl shadow p-5">
                    <div class="flex items-start gap-3">
                        <div class="w-9 h-9 rounded-full bg-indigo-500 flex items-center justify-center text-white text-sm font-bold shrink-0">
                            {{ strtoupper(substr($post->author->name, 0, 1)) }}
                        </div>
                        <div class="flex-1 min-w-0">
                            <div class="flex items-center gap-2 mb-0.5">
                                <span class="text-sm font-semibold text-gray-900">{{ $post->author->name }}</span>
                                <span class="text-xs px-2 py-0.5 rounded-full bg-indigo-100 text-indigo-700 font-medium">
                                    {{ str_replace('_', ' ', ucfirst($post->type)) }}
                                </span>
                                @if($post->community)
                                    <span class="text-xs text-gray-400">in {{ $post->community->name }}</span>
                                @endif
                                <span class="text-xs text-gray-400 ml-auto">{{ $post->created_at->diffForHumans() }}</span>
                            </div>

                            @if($post->title)
                                <h4 class="text-base font-semibold text-gray-800 mb-1">{{ $post->title }}</h4>
                            @endif

                            <p class="text-sm text-gray-600 leading-relaxed line-clamp-3">{{ $post->body }}</p>

                            @if($post->enrichment?->tags)
                                <div class="flex flex-wrap gap-1 mt-2">
                                    @foreach(array_slice($post->enrichment->tags, 0, 4) as $tag)
                                        <span class="text-xs px-1.5 py-0.5 bg-gray-100 text-gray-500 rounded">#{{ $tag }}</span>
                                    @endforeach
                                </div>
                            @endif

                            <div class="flex items-center gap-4 mt-3">
                                <button wire:click="react({{ $post->id }})" class="flex items-center gap-1 text-xs text-gray-400 hover:text-indigo-600 transition-colors">
                                    <span>👍</span>
                                    <span>{{ $post->reactions_count }}</span>
                                </button>
                                <span class="flex items-center gap-1 text-xs text-gray-400">
                                    <span>💬</span>
                                    <span>{{ $post->comments_count }}</span>
                                </span>
                                @if($post->enrichment)
                                    <span class="text-xs px-1.5 py-0.5 rounded-full ml-auto
                                        @if($post->enrichment->sentiment === 'positive') bg-green-50 text-green-600
                                        @elseif($post->enrichment->sentiment === 'negative') bg-red-50 text-red-500
                                        @else bg-gray-50 text-gray-400 @endif">
                                        {{ ucfirst($post->enrichment->sentiment ?? 'neutral') }}
                                    </span>
                                @endif
                            </div>
                        </div>
                    </div>
                </div>
            @endforeach
        </div>
    @endif
</div>
