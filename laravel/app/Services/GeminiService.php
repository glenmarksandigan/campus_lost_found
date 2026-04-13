<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class GeminiService
{
    protected $apiKey;
    protected $baseUrl = 'https://generativelanguage.googleapis.com/v1beta/models/gemini-1.5-flash:generateContent';

    public function __construct()
    {
        $this->apiKey = config('services.gemini.key');
    }

    public function verifyImage($imagePath, $prompt)
    {
        if (!$this->apiKey) {
            Log::error('Gemini API key is not configured.');
            return ['error' => 'API Key missing'];
        }

        try {
            $imageData = base64_encode(file_get_contents(storage_path('app/public/' . $imagePath)));
            $mimeType = mime_content_type(storage_path('app/public/' . $imagePath));

            $response = Http::post($this->baseUrl . '?key=' . $this->apiKey, [
                'contents' => [
                    [
                        'parts' => [
                            ['text' => $prompt],
                            [
                                'inline_data' => [
                                    'mime_type' => $mimeType,
                                    'data' => $imageData
                                ]
                            ]
                        ]
                    ]
                ]
            ]);

            return $response->json();
        } catch (\Exception $e) {
            Log::error('Gemini verification error: ' . $e->getMessage());
            return ['error' => $e->getMessage()];
        }
    }

    public function chat($message, $history = [], $systemInstruction = '')
    {
        if (!$this->apiKey) {
            return ['error' => 'API Key missing'];
        }

        $contents = [];
        foreach ($history as $msg) {
            $contents[] = [
                'role' => $msg['role'] === 'user' ? 'user' : 'model',
                'parts' => [['text' => $msg['text']]]
            ];
        }
        $contents[] = ['role' => 'user', 'parts' => [['text' => $message]]];

        $payload = [
            'contents' => $contents,
            'generationConfig' => [
                'temperature' => 0.7,
                'maxOutputTokens' => 500
            ]
        ];

        if ($systemInstruction) {
            $payload['system_instruction'] = ['parts' => [['text' => $systemInstruction]]];
        }

        try {
            $response = Http::post($this->baseUrl . '?key=' . $this->apiKey, $payload);
            $data = $response->json();
            
            return [
                'success' => true,
                'reply' => $data['candidates'][0]['content']['parts'][0]['text'] ?? 'No response'
            ];
        } catch (\Exception $e) {
            return ['error' => $e->getMessage()];
        }
    }
}
