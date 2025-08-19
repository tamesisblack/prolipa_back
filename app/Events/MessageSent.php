<?php

namespace App\Events;

use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Queue\SerializesModels;
use App\Services\AiService;

class MessageSent implements ShouldBroadcast
{
    use InteractsWithSockets, SerializesModels;

    public $message;
    private $aiService;
    private $chatId;

    /**
     * Create a new event instance.
     *
     * @return void
     */
    public function __construct($message, $chatId)
    {
        $this->chatId = $chatId;
        $this->aiService = new AiService();
        $aiResponse = $this->aiService->makePostRequest($message);
        $this->message = $aiResponse ?? "No se pudo obtener respuesta";
        // $this->message = "¡Mensaje de prueba HOLA MUNDO!";

    }

    /**
     * Get the channels the event should broadcast on.
     *
     * @return \Illuminate\Broadcasting\Channel|array
     */
    public function broadcastOn()
    {
        return new Channel('chat.' . $this->chatId);
    }
}
