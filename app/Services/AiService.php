<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Config;

class AiService
{
    private $url;
    private $apiKey;

    public function __construct()
    {
        $this->url = 'https://generativelanguage.googleapis.com/v1beta/models/gemini-2.5-flash:generateContent';
        $this->apiKey = env('AI_API_KEY');
    }

    public function makePostRequest($message)
    {
    try {
        $payload = $this->build_chat_history($message);
        \Log::info('AI Service Payload', ['payload' => $payload]);

        $response = Http::withHeaders([
            'Content-Type' => 'application/json',
            'x-goog-api-key' => $this->apiKey
        ])->post($this->url, $payload);

            if (!$response->successful()) {
                \Log::error('AI Service Error', [
                    'status' => $response->status(),
                    'body' => $response->body()
                ]);
                return sprintf("payload:");
            }

            return $response['candidates'][0]['content']['parts'][0]['text'] ?? "sin respuesta flaquito, toy moviendo mal la data";

        } catch (\Exception $e) {
            \Log::error('AI Service Exception', [
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            print_r([
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return null;
        }
    }

    private function build_chat_history($messages): array
    {
        $chatHistory = ['contents' => []];
        foreach ($messages as $message) {
            $chatHistory['contents'][] = [
                'role' => $message['role'],
                'parts' => [
                    [
                        'text' => $message['content'] ?? ''
                    ]
                ]
            ];
        }
        return $chatHistory;

    }
}
