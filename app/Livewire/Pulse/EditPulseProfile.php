<?php

namespace App\Livewire\Pulse;

use App\Actions\Pulse\UploadMedia;
use App\Models\PulseProfile;
use Illuminate\Support\Facades\Storage;
use Livewire\Component;
use Livewire\WithFileUploads;

class EditPulseProfile extends Component
{
    use WithFileUploads;

    public PulseProfile $profile;

    public string  $headline      = '';
    public string  $bio           = '';
    public string  $location      = '';
    public string  $website       = '';
    public string  $skillsInput   = '';  // comma-separated
    public mixed   $avatarFile    = null;
    public mixed   $coverFile     = null;

    public bool    $saved         = false;

    public function mount(): void
    {
        $user          = auth()->user();
        $this->profile = PulseProfile::firstOrCreate(
            ['user_id' => $user->id],
            ['role'    => 'customer'],
        );

        $this->headline    = $this->profile->headline ?? '';
        $this->bio         = $this->profile->bio ?? '';
        $this->location    = $this->profile->location ?? '';
        $this->website     = $this->profile->website ?? '';
        $this->skillsInput = implode(', ', $this->profile->skills ?? []);
    }

    protected function rules(): array
    {
        return [
            'headline'   => 'nullable|max:120',
            'bio'        => 'nullable|max:600',
            'location'   => 'nullable|max:100',
            'website'    => 'nullable|url|max:200',
            'skillsInput'=> 'nullable|string|max:300',
            'avatarFile' => 'nullable|image|max:2048',
            'coverFile'  => 'nullable|image|max:4096',
        ];
    }

    public function save(): void
    {
        $this->validate();

        $uploader   = app(UploadMedia::class);
        $avatarUrl  = $this->profile->avatar_url;
        $coverUrl   = $this->profile->cover_url;

        if ($this->avatarFile) {
            $media     = $uploader->handle($this->avatarFile, auth()->id(), PulseProfile::class, $this->profile->id);
            $avatarUrl = Storage::disk('public')->url($media->path);
        }

        if ($this->coverFile) {
            $media    = $uploader->handle($this->coverFile, auth()->id(), PulseProfile::class, $this->profile->id, 'public');
            $coverUrl = Storage::disk('public')->url($media->path);
        }

        $skills = array_values(array_filter(array_map(
            'trim',
            explode(',', $this->skillsInput),
        )));

        $this->profile->update([
            'headline'   => $this->headline ?: null,
            'bio'        => $this->bio ?: null,
            'location'   => $this->location ?: null,
            'website'    => $this->website ?: null,
            'skills'     => $skills,
            'avatar_url' => $avatarUrl,
            'cover_url'  => $coverUrl,
        ]);

        $this->avatarFile = null;
        $this->coverFile  = null;
        $this->saved      = true;
    }

    public function render(): \Illuminate\View\View
    {
        return view('livewire.pulse.edit-pulse-profile');
    }
}
