<?php
// app/Services/PusherService.php

namespace App\Services;

use Pusher\Pusher;

class PusherService
{
    private $pusher;

    public function __construct()
    {
        $options = [
            'cluster' => env('PUSHER_CLUSTER', 'us2'),  // Usando la variable de entorno
            'useTLS' => true
        ];

        $this->pusher = new Pusher(
            env('PUSHER_APP_KEY', '67ad293a1345b6955bf0'),  // Usando la variable de entorno
            env('PUSHER_APP_SECRET', '715db7e905f8d5879967'),
            env('PUSHER_APP_ID', '1960752'),
            $options
        );
    }

    public function triggerNotification($channel, $event, $data)
    {
        try {
            $this->pusher->trigger($channel, $event, $data);
        } catch (\Exception $e) {
            throw new \Exception("No se pudo enviar notificación en Pusher: " . $e->getMessage());
        }
    }
}
