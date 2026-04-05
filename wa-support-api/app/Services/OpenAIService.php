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
                        'content' => 'You are an advanced emergency detection system for a WhatsApp support service. Analyze the given message for ANY indication of payment, admission, or urgent matters that require immediate attention.

                        EMERGENCY INDICATORS (check ANY of these):
                        1. Payment-related terms in ANY language:
                           - English: "payment", "pay", "paid", "invoice", "bill", "cost", "fee", "charge", "transaction", "money", "price", "amount", "credit card", "bank", "account", "deposit", "refund", "receipt"
                           - Kiswahili: "malipo", "kulipa", "gharamia", "kodi", "deni", "ada", "hisa", "mikopo", "pesa", "cheki", "akaunti", "faini", "sare", "mshahara"
                           - French: "paiement", "payer", "facture", "coût", "prix", "montant", "carte bancaire", "banque", "compte", "dépôt", "remboursement", "reçu", "argent"
                        
                        2. Admission-related terms in ANY language:
                           - English: "admission", "admit", "accepted", "enrolled", "registered", "application", "apply", "join", "entrance", "registration", "enrollment"
                           - Kiswahili: "kujiunga", "kuingia", "kusajili", "kuingizwa", "kuingia", "kubali", "kuingia", "kuandikwa", "kuingia"
                           - French: "admission", "admis", "inscrit", "candidature", "inscription", "intégrer", "joindre", "entrer", "enrôlement"
                        
                        3. Urgent terms in ANY language:
                           - English: "urgent", "emergency", "critical", "asap", "immediately", "help", "problem", "issue", "trouble", "broken", "fail", "error", "serious", "severe", "warning", "danger", "sick", "medical", "hospital", "accident", "injury"
                           - Kiswahili: "haraka", "dharura", "tatizo", "hatari", "dhiki", "tatizo", "njaa", "ugonjwa", "dharura", "majeruhi", "hospitalini", "ajali", "vidonge"
                           - French: "urgent", "urgence", "critique", "immédiatement", "aide", "problème", "problème", "panne", "erreur", "sérieux", "grave", "danger", "médical", "hôpital", "accident", "blessure"

                        RULES:
                        - Mark as EMERGENCY if ANY emergency keyword is found
                        - Mark as HIGH PRIORITY if ANY payment/admission keyword is found
                        - Consider context: multiple payment terms, combinations with urgent words
                        - Be language-agnostic: detect keywords regardless of message language
                        - False positives are better than missed emergencies
                        
                        Respond with JSON format:
                        {
                            "is_emergency": true/false,
                            "priority": "emergency/high/normal",
                            "detected_keywords": ["list of keywords found"],
                            "language_detected": "estimated language",
                            "confidence": "high/medium/low"
                        }'
                    ],
                    [
                        'role' => 'user',
                        'content' => $message
                    ]
                ],
                'max_tokens' => 200,
                'temperature' => 0.1
            ]);

            $result = $response->json();
            
            if (isset($result['choices'][0]['message']['content'])) {
                $analysis = json_decode($result['choices'][0]['message']['content'], true);
                
                return [
                    'is_emergency' => $analysis['is_emergency'] ?? false,
                    'priority' => $analysis['priority'] ?? 'normal',
                    'detected_keywords' => $analysis['detected_keywords'] ?? [],
                    'language_detected' => $analysis['language_detected'] ?? 'unknown',
                    'confidence' => $analysis['confidence'] ?? 'low',
                    'reason' => $analysis['reason'] ?? 'No analysis available'
                ];
            }

            return [
                'is_emergency' => false,
                'priority' => 'normal',
                'detected_keywords' => [],
                'language_detected' => 'unknown',
                'confidence' => 'low',
                'reason' => 'Failed to analyze message'
            ];

        } catch (\Exception $e) {
            Log::error('OpenAI API error: ' . $e->getMessage());
            
            return [
                'is_emergency' => false,
                'priority' => 'normal',
                'detected_keywords' => [],
                'language_detected' => 'unknown',
                'confidence' => 'low',
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
