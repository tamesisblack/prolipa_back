<?php

namespace App\Http\Controllers;

use App\Events\MessageSent;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class AiMessageController extends Controller
{
    public function sendMessage(Request $request)
    {
        $messages = $request->input('messages');
        $chatId = $request->input('chatId'); // Nuevo campo para identificar el chat

        $event = new MessageSent($messages, $chatId);
        broadcast($event)->toOthers();

        return response()->json([
            'status' => 'Mensaje enviado'
        ]);
    }
}
