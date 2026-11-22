<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class File extends Model
{
    protected $fillable = ['file_name', 'file_original_name', 'extension', 'file_size', 'type'];

    /**
     * Get the folder that owns the file.
     */
    public function folder()
    {
        return $this->belongsTo(Folder::class);
    }

    /**
     * Scope a query to only include images.
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeImages($query)
    {
        return $query->where('type', 'image');
    }

    /**
     * Scope a query to only include videos.
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeVideos($query)
    {
        return $query->where('type', 'video');
    }

    /**
     * Scope a query to only include files of a specific extension.
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @param string $extension
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeOfExtension($query, $extension)
    {
        return $query->where('extension', $extension);
    }
}

