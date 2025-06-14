<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Facades\Auth;
use Laravel\Sanctum\HasApiTokens;
use Spatie\Permission\Traits\HasRoles;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;

class User extends Authenticatable implements HasMedia
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasApiTokens, HasFactory, Notifiable, InteractsWithMedia;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'first_name',
        'last_name',
        'role',
        'email',
        'password',
        'username',
        'phone_number',
        'avatar',
        'is_verified',
        'otp',
        'otp_expire_at',
        'is_active',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
        'otp',
        'otp_expire_at',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'is_verified' => 'boolean',
            'is_active' => 'boolean',
            'otp_expire_at' => 'datetime',
        ];
    }

    protected $appends = [
        'full_name',
        'avatar_url',
    ];

    // if avatar is none, return letter avatar image ex https://ui-avatars.com/api/?name=ahmed&size=256&background=random
    public function getAvatarUrlAttribute()
    {
        if ($this->avatar == 'none') {
            return "https://ui-avatars.com/api/?name={$this->first_name}&size=256&background=random&length=1";
        }
        return $this->getFirstMediaUrl('avatar') ?: asset('images/default-avatar.png');
    }

    public function getFullNameAttribute()
    {
        return "{$this->first_name} {$this->last_name}";
    }



    public function company()
    {
        return $this->hasOne(Company::class, 'manager_id');
    }

    public function sentMessages()
    {
        return $this->hasMany(Message::class, 'sender_id');
    }

    public function receivedMessages()
    {
        return $this->hasMany(Message::class, 'receiver_id');
    }

    public function stories()
    {
        return $this->hasMany(Story::class);
    }

    public function storyViews()
    {
        return $this->hasMany(StoryView::class);
    }

    public function registerMediaCollections(): void
    {
        $this->addMediaCollection('avatar')
            ->singleFile()
            ->useDisk('public');
    }


    public static function auth()
    {
        if (Auth::guard('sanctum')->check()) {
            $user =  Auth::guard('sanctum')->user();
            return User::where('id', $user->id)->first();
        }

        return null;
    }
}
