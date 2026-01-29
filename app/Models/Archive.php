<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Storage;

class Archive extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'path',
        'disk',
        'type',
        'category',
        'visibility',
        'original_name',
        'extension',
        'mime_type',
        'size',
        'status'
    ];

    public function attachable(): \Illuminate\Database\Eloquent\Relations\MorphTo
    {
        return $this->morphTo();
    }


    public function getUrlAttribute(): ?string
    {
        return Storage::disk($this->disk)->url($this->path);
    }

    public function getIsImageAttribute(): bool
    {
        return str_starts_with($this->mime_type, 'image/');
    }
    public function users()
    {
        return $this->hasMany(User::class);
    }

}
