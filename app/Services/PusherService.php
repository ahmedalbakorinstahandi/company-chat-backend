<?php

namespace App\Services;

use Pusher\Pusher;
use Illuminate\Support\Facades\Log;

class PusherService
{
    protected $pusher;

    public function __construct()
    {
        $cluster = config('services.pusher.cluster', 'us3');
        
        // Log the configuration for debugging
        Log::info('Pusher configuration', [
            'key' => config('services.pusher.key'),
            'secret' => config('services.pusher.secret'),
            'app_id' => config('services.pusher.app_id'),
            'cluster' => $cluster,
        ]);

        $this->pusher = new Pusher(
            config('services.pusher.key'),
            config('services.pusher.secret'),
            config('services.pusher.app_id'),
            [
                'cluster' => $cluster,
                'useTLS' => true,
                // Remove custom host to use default Pusher host
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
        try {
            $result = $this->pusher->trigger($channel, $event, $data);
            Log::info('Pusher message sent successfully', [
                'channel' => $channel,
                'event' => $event,
                'result' => $result
            ]);
            return $result;
        } catch (\Exception $e) {
            Log::error('Pusher message failed to send', [
                'channel' => $channel,
                'event' => $event,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            return false;
        }
    }

    public function getPusher()
    {
        return $this->pusher;
    }
}
