<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class OpenAIService
{
    private string $apiKey;
    private string $baseUrl;

    public function __construct()
    {
        $this->apiKey = env('OPENAI_API_KEY', '');
        $this->baseUrl = 'https://api.openai.com/v1';
    }

    /**
     * Analyze message for emergency keywords
     */
    public function analyzeMessage(string $message): array
    {
        if (empty($this->apiKey)) {
            return [
                'is_emergency' => false,
                'priority' => 'normal',
                'reason' => 'OpenAI API key not configured'
            ];
        }

        try {
            $response = Http::withHeaders([
                'Authorization' => 'Bearer ' . $this->apiKey,
                'Content-Type' => 'application/json',
            ])->post($this->baseUrl . '/chat/completions', [
                'model' => 'gpt-3.5-turbo',
                'messages' => [
                    [
                        'role' => 'system',
                        'content' => 'You are an emergency detection system for a WhatsApp support service. Analyze the given message and determine if it contains emergency keywords. 
                        
                        Emergency keywords to look for: "payment", "admission", "urgent", "emergency", "critical", "asap", "immediately", "help", "problem", "issue"
                        
                        Respond with JSON format:
                        {
                            "is_emergency": true/false,
                            "priority": "emergency/high/normal",
                            "reason": "brief explanation"
                        }
                        
                        Be conservative - only mark as emergency if the message clearly indicates urgency or contains emergency keywords.'
                    ],
                    [
                        'role' => 'user',
                        'content' => $message
                    ]
                ],
                'max_tokens' => 150,
                'temperature' => 0.1
            ]);

            $result = $response->json();
            
            if (isset($result['choices'][0]['message']['content'])) {
                $analysis = json_decode($result['choices'][0]['message']['content'], true);
                
                return [
                    'is_emergency' => $analysis['is_emergency'] ?? false,
                    'priority' => $analysis['priority'] ?? 'normal',
                    'reason' => $analysis['reason'] ?? 'No analysis available'
                ];
            }

            return [
                'is_emergency' => false,
                'priority' => 'normal',
                'reason' => 'Failed to analyze message'
            ];

        } catch (\Exception $e) {
            Log::error('OpenAI API error: ' . $e->getMessage());
            
            return [
                'is_emergency' => false,
                'priority' => 'normal',
                'reason' => 'API error: ' . $e->getMessage()
            ];
        }
    }

    /**
     * Check if API key is configured
     */
    public function isConfigured(): bool
    {
        return !empty($this->apiKey);
    }
}
