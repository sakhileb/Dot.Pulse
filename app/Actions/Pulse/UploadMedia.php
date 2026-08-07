<?php

namespace App\Actions\Pulse;

use App\Models\PulseMedia;
use Illuminate\Filesystem\FilesystemAdapter;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Livewire\Features\SupportFileUploads\TemporaryUploadedFile;

class UploadMedia
{
    /**
     * Store an uploaded file and record it in pulse_media.
     *
     * @param  UploadedFile|TemporaryUploadedFile  $file
     * @param  string  $morphType  Fully qualified model class
     * @param  string  $disk  'public' or 's3'
     */
    public function handle(
        mixed $file,
        int $userId,
        string $morphType,
        int $morphId,
        string $disk = 'public',
    ): PulseMedia {
        $folder = match (true) {
            str_contains($morphType, 'Profile') => 'avatars',
            str_contains($morphType, 'Community') => 'community',
            default => 'posts',
        };

        $path = $file->store($folder, $disk);

        return PulseMedia::create([
            'user_id' => $userId,
            'mediable_type' => $morphType,
            'mediable_id' => $morphId,
            'disk' => $disk,
            'path' => $path,
            'mime_type' => $file->getMimeType(),
            'size' => $file->getSize(),
        ]);
    }

    public function url(PulseMedia $media): string
    {
        /** @var FilesystemAdapter $disk */
        $disk = Storage::disk($media->disk);

        return $disk->url($media->path);
    }
}
