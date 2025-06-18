<?php

use Illuminate\Support\Facades\Broadcast;

/*
|--------------------------------------------------------------------------
| Broadcast Channels
|--------------------------------------------------------------------------
|
| Here you may register all of the event broadcasting channels that your
| application supports. The given channel authorization callbacks are
| used to check if an authenticated user can listen to the channel.
|
*/

Broadcast::channel('App.Models.User.{id}', function ($user, $id) {
    return (int) $user->id === (int) $id;
});

// Private user channels for messages
Broadcast::channel('private-user.{id}', function ($user, $id) {
    return (int) $user->id === (int) $id;
});

// Public stories channel
Broadcast::channel('stories', function ($user) {
    return true; // Anyone can listen to stories
});

// Private company channels
Broadcast::channel('private-company.{id}', function ($user, $id) {
    // Check if user belongs to the company
    return $user->company_id == $id;
});

// Presence channels for online users
Broadcast::channel('presence-users', function ($user) {
    return [
        'id' => $user->id,
        'name' => $user->full_name,
        'email' => $user->email,
    ];
}); 