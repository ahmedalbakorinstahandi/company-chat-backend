<?php

namespace App\Services;

use Pusher\Pusher;

class PusherService
{
    protected $pusher;

    public function __construct()
    {
        $this->pusher = new Pusher(
            'aad0418787f85ad833f7',
            'd82e595a10197a5be4a4', 
            '2007760',
            [
                'cluster' => 'us3',
                'useTLS' => true,
            ]
        );
    }

//     PUSHER_APP_ID="2007760"
// PUSHER_APP_KEY="aad0418787f85ad833f7"
// PUSHER_APP_SECRET="d82e595a10197a5be4a4"
// PUSHER_HOST=
// PUSHER_PORT=443
// PUSHER_SCHEME=https
// PUSHER_APP_CLUSTER="us3"

    public function sendMessage($channel, $event, $data)
    {
        $this->pusher->trigger($channel, $event, $data);
    }
}
