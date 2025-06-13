<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class StoryView extends Model
{
    use HasFactory;

    protected $fillable = [
        'story_id',
        'user_id',
        'is_favorite',
    ];

    protected $casts = [
        'is_favorite' => 'boolean',
    ];

    public function story()
    {
        return $this->belongsTo(Story::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }
} 