<?php

namespace App\Livewire\Profile;

use App\Models\PulseProfile;
use Illuminate\View\View;
use Livewire\Component;

/**
 * PulseProfile::headline/bio/skills existed on the model with no form to
 * write them from anywhere in the app — the public profile page
 * (livewire.pulse.user-profile) could only ever display fields nobody could
 * set. expertise_tags is a second, distinct array field on the same model
 * left out of this form deliberately: its purpose (vs. skills) was never
 * defined, and guessing at a second tag input would be inventing scope, not
 * filling it.
 */
class UpdatePulseProfileForm extends Component
{
    public string $headline = '';

    public string $bio = '';

    public array $skills = [];

    public string $newSkill = '';

    public function mount(): void
    {
        $profile = auth()->user()->pulseProfile;

        $this->headline = $profile?->headline ?? '';
        $this->bio = $profile?->bio ?? '';
        $this->skills = $profile?->skills ?? [];
    }

    public function addSkill(): void
    {
        $skill = trim($this->newSkill);
        $this->newSkill = '';

        if ($skill === '' || in_array($skill, $this->skills, true) || count($this->skills) >= 12) {
            return;
        }

        $this->skills[] = $skill;
    }

    public function removeSkill(int $index): void
    {
        unset($this->skills[$index]);
        $this->skills = array_values($this->skills);
    }

    public function save(): void
    {
        $this->validate([
            'headline' => ['nullable', 'string', 'max:120'],
            'bio' => ['nullable', 'string', 'max:1000'],
            'skills' => ['array', 'max:12'],
            'skills.*' => ['string', 'max:40'],
        ]);

        // firstOrCreate mirrors the dashboard route's own pattern: never write
        // 'role' here directly via updateOrCreate's $values, since that fills
        // on every save and would silently reset a moderator/admin back to
        // 'customer' the next time they edited their bio.
        $profile = PulseProfile::firstOrCreate(
            ['user_id' => auth()->id()],
            ['role' => 'customer', 'community_points' => 0],
        );

        $profile->update([
            'headline' => $this->headline,
            'bio' => $this->bio,
            'skills' => $this->skills,
        ]);

        $this->dispatch('saved');
    }

    public function render(): View
    {
        return view('livewire.profile.update-pulse-profile-form');
    }
}
