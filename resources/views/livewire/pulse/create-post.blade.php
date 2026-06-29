<div class="bg-white rounded-xl shadow p-5 mb-6">
    <button
        wire:click="$toggle('showForm')"
        class="w-full text-left text-sm text-gray-400 border border-gray-200 rounded-lg px-4 py-3 hover:border-indigo-300 hover:text-gray-600 transition-colors"
    >
        Share something with the community...
    </button>

    @if($showForm)
        <form wire:submit="publish" class="mt-4 space-y-3">
            <div class="grid grid-cols-2 gap-3">
                <div>
                    <label class="block text-xs text-gray-500 mb-1">Type</label>
                    <select wire:model="type" class="w-full border border-gray-300 rounded px-3 py-2 text-sm focus:ring-2 focus:ring-indigo-500 focus:outline-none">
                        @foreach(\App\Models\PulsePost::$types as $t)
                            <option value="{{ $t }}">{{ ucfirst(str_replace('_', ' ', $t)) }}</option>
                        @endforeach
                    </select>
                    @error('type') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
                </div>
                <div>
                    <label class="block text-xs text-gray-500 mb-1">Community (optional)</label>
                    <select wire:model="communityId" class="w-full border border-gray-300 rounded px-3 py-2 text-sm focus:ring-2 focus:ring-indigo-500 focus:outline-none">
                        <option value="">No community</option>
                        @foreach($this->communities as $community)
                            <option value="{{ $community->id }}">{{ $community->name }}</option>
                        @endforeach
                    </select>
                </div>
            </div>

            <div>
                <label class="block text-xs text-gray-500 mb-1">Title (optional)</label>
                <input wire:model="title" type="text" placeholder="Give your post a title..." class="w-full border border-gray-300 rounded px-3 py-2 text-sm focus:ring-2 focus:ring-indigo-500 focus:outline-none" />
                @error('title') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
            </div>

            <div>
                <label class="block text-xs text-gray-500 mb-1">Content</label>
                <textarea wire:model="body" rows="4" placeholder="What do you want to share?" class="w-full border border-gray-300 rounded px-3 py-2 text-sm focus:ring-2 focus:ring-indigo-500 focus:outline-none resize-none"></textarea>
                @error('body') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
            </div>

            <div class="flex items-center gap-3 pt-1">
                <button type="submit"
                    class="bg-indigo-600 text-white px-5 py-2 rounded-lg text-sm font-medium hover:bg-indigo-700 disabled:opacity-50"
                    wire:loading.attr="disabled">
                    <span wire:loading.remove>Publish</span>
                    <span wire:loading>Processing...</span>
                </button>
                <button type="button" wire:click="$set('showForm', false)" class="text-sm text-gray-400 hover:text-gray-600">Cancel</button>
                <span class="text-xs text-gray-300 ml-auto">AI moderation runs automatically</span>
            </div>
        </form>
    @endif
</div>
