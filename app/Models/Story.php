<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;

class Story extends Model implements HasMedia
{
    use HasFactory, InteractsWithMedia;

    protected $fillable = [
        'user_id',
        'image',
        'content',
    ];

    protected $appends = [
        'image_url',
        'views_count',
        'favorites_count',
    ];

    // add url to image

    public function getImageUrlAttribute()
    {
        return asset('storage/' . $this->getFirstMediaUrl('image')) ?: null;
    }

    public function getViewsCountAttribute()
    {
        return $this->views()->count();
    }

    public function getFavoritesCountAttribute()
    {
        return $this->views()->where('is_favorite', true)->count();
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function views()
    {
        return $this->hasMany(StoryView::class);
    }

    public function registerMediaCollections(): void
    {
        $this->addMediaCollection('image')
            ->singleFile()
            ->useDisk('public');
    }
}
