<?php

namespace App\Services\Archives;
use App\Models\Archive;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Livewire\Features\SupportFileUploads\TemporaryUploadedFile;

class ArchiveUploadService
{
    public function upload(
        UploadedFile|TemporaryUploadedFile  $file,
        string $type,
        ?string $category = null,
        string $visibility = 'private',
        ?object $attachable = null,
    ): Archive {
        $disk = $visibility === 'public' ? 'public' : 'local';

        $path = $file->store(
            'archives/' . $type,
            $disk
        );

        return Archive::create([
            'path'           => $path,
            'disk'           => $disk,
            'type'           => $type,
            'category'       => $category,
            'visibility'     => $visibility,

            'original_name'  => $file->getClientOriginalName(),
            'extension'      => $file->getClientOriginalExtension(),
            'mime_type'      => $file->getMimeType(),
            'size'           => $file->getSize(),
        ]);
    }
}
