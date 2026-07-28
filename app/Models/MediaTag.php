<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class MediaTag extends Model
{
    protected $fillable = ['name', 'slug'];

    public function files(): BelongsToMany
    {
        return $this->belongsToMany(MediaFile::class, 'media_file_tag', 'tag_id', 'file_id');
    }
}
