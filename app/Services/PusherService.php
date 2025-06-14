<?php

namespace App\Services\Helper;

use Pusher\Pusher;

class PusherService
{
    protected $pusher;

    public function __construct()
    {
        $config = config('services.pusher');

        if (!$config['key'] || !$config['secret'] || !$config['app_id']) {
            throw new \Exception('Missing Pusher configuration');
        }

        $this->pusher = new Pusher(
            $config['key'],
            $config['secret'],
            $config['app_id'],
            [
                'cluster' => $config['cluster'],
                'useTLS' => $config['useTLS'],
            ]
        );
    }

    public function sendMessage($channel, $event, $data)
    {
        $this->pusher->trigger($channel, $event, $data);
    }
}
