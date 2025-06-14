<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class MessageImage extends Model
{
    protected $fillable = ['message_id', 'path'];

    public function message()
    {
        return $this->belongsTo(Message::class);
    }

    public function getUrlAttribute()
    {
        return asset('storage/' . $this->path);
    }
}
