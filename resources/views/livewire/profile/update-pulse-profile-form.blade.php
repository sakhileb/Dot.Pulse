<x-form-section submit="save">
    <x-slot name="title">
        {{ __('Pulse Profile') }}
    </x-slot>

    <x-slot name="description">
        {{ __('Shown on your public Dot.Pulse profile — headline, bio, and skills.') }}
    </x-slot>

    <x-slot name="form">
        <div class="col-span-6 sm:col-span-4">
            <x-label for="headline" value="{{ __('Headline') }}" />
            <x-input id="headline" type="text" class="mt-1 block w-full" wire:model="headline" placeholder="e.g. Product designer at Dot.Design" maxlength="120" />
            <x-input-error for="headline" class="mt-2" />
        </div>

        <div class="col-span-6 sm:col-span-4">
            <x-label for="bio" value="{{ __('Bio') }}" />
            <textarea id="bio" wire:model="bio" rows="3" maxlength="1000"
                class="border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300 focus:border-indigo-500 dark:focus:border-indigo-600 focus:ring-indigo-500 dark:focus:ring-indigo-600 rounded-md shadow-sm mt-1 block w-full"
            ></textarea>
            <x-input-error for="bio" class="mt-2" />
        </div>

        <div class="col-span-6 sm:col-span-4">
            <x-label for="newSkill" value="{{ __('Skills') }}" />

            @if(count($skills))
                <div class="flex flex-wrap gap-2 mt-2">
                    @foreach($skills as $index => $skill)
                        <span class="dot-badge dot-badge-accent" style="gap:6px;padding-right:6px;">
                            {{ $skill }}
                            <button type="button" wire:click="removeSkill({{ $index }})" aria-label="Remove {{ $skill }}"
                                style="background:none;border:none;color:inherit;cursor:pointer;font-size:13px;line-height:1;padding:0;opacity:0.7;">
                                &times;
                            </button>
                        </span>
                    @endforeach
                </div>
            @endif

            <x-input id="newSkill" type="text" class="mt-2 block w-full" wire:model="newSkill"
                wire:keydown.enter.prevent="addSkill" placeholder="Type a skill and press Enter" maxlength="40" />
            <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">{{ count($skills) }}/12 skills</p>
            <x-input-error for="skills" class="mt-2" />
        </div>
    </x-slot>

    <x-slot name="actions">
        <x-action-message class="me-3" on="saved">
            {{ __('Saved.') }}
        </x-action-message>

        <x-button>
            {{ __('Save') }}
        </x-button>
    </x-slot>
</x-form-section>
