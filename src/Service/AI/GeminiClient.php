<?php
// src/Service/AI/GeminiClient.php

namespace App\Service\AI;

use Psr\Log\LoggerInterface;

class GeminiClient
{
    private ?LoggerInterface $logger;
    private string $apiKey;

    public function __construct(string $apiKey, ?LoggerInterface $logger = null)
    {
        $this->apiKey = $apiKey;
        $this->logger = $logger;
    }

    public function generateContent(string $prompt, int $maxTokens = 800): string
    {
        try {
            $this->log('Tentative d\'appel Gemini API');
            
            // Utilisation du modèle stable gemini-2.5-flash
            $model = "gemini-2.5-flash";
            
            // URL correcte avec le modèle
            $url = "https://generativelanguage.googleapis.com/v1beta/models/{$model}:generateContent?key=" . $this->apiKey;
            
            $this->log('URL: ' . str_replace($this->apiKey, 'HIDDEN_KEY', $url));
            
            // Instructions système strictes pour éviter le blabla
            $systemInstruction = "Tu es un assistant spécialisé dans l'organisation d'événements éducatifs pour enfants.
            
RÈGLES STRICTES À RESPECTER ABSOLUMENT :
1. Réponds UNIQUEMENT avec le contenu demandé (checklist ou planning)
2. AUCUNE introduction, AUCUNE conclusion, AUCUN commentaire
3. Pas de phrases comme 'Absolument !', 'En tant qu'expert', 'Voici', 'Bien sûr', etc.
4. Va droit au but, sois CONCRET et PRATIQUE
5. Utilise des puces et des tableaux pour une lisibilité maximale
6. Inclus des quantités précises quand c'est pertinent
7. Adapte le contenu à l'âge des enfants (événement éducatif)

Le prompt suivant contient la demande spécifique. Réponds UNIQUEMENT avec le contenu demandé, sans rien ajouter.";
            
            // Préparer les données avec le système instruction
            $data = [
                'contents' => [
                    [
                        'parts' => [
                            ['text' => $systemInstruction . "\n\n" . $prompt]
                        ]
                    ]
                ],
                'generationConfig' => [
                    'maxOutputTokens' => 2048,
                    'temperature' => 0.5, 
                    'topP' => 0.9,
                    'topK' => 30
                ]
            ];
            
            // Initialiser cURL
            $ch = curl_init($url);
            
            $jsonData = json_encode($data);
            if ($jsonData === false) {
                throw new \Exception("Erreur d'encodage JSON des données");
            }
            
            curl_setopt_array($ch, [
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_POST => true,
                CURLOPT_HTTPHEADER => [
                    'Content-Type: application/json'
                ],
                CURLOPT_POSTFIELDS => $jsonData,
                CURLOPT_TIMEOUT => 30,
                CURLOPT_SSL_VERIFYPEER => true
            ]);
            
            $response = curl_exec($ch);
            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            $curlError = curl_error($ch);
            
            curl_close($ch);
            
            $this->log('Code HTTP: ' . $httpCode);
            
            if ($curlError) {
                throw new \Exception("Erreur cURL : " . $curlError);
            }
            
            if ($httpCode !== 200) {
                $this->log('Réponse erreur: ' . ($response ?: 'vide'));
                
                // Messages d'erreur plus explicites
                if ($httpCode === 403) {
                    throw new \Exception("HTTP 403 - Accès refusé. Vérifiez que l'API Generative Language est activée dans Google Cloud Console.");
                } elseif ($httpCode === 404) {
                    throw new \Exception("HTTP 404 - Modèle non trouvé. Essayez 'gemini-1.5-flash' ou 'gemini-1.0-pro'.");
                } elseif ($httpCode === 429) {
                    throw new \Exception("HTTP 429 - Quota dépassé. Attendez quelques minutes.");
                } else {
                    throw new \Exception("HTTP $httpCode - Vérifiez votre clé API et votre connexion.");
                }
            }
            
            if (!is_string($response)) {
                throw new \Exception("Réponse invalide de l'API");
            }
            
            $result = json_decode($response, true);
            
            if (!is_array($result)) {
                throw new \Exception("Réponse JSON invalide de l'API");
            }
            
            if (isset($result['error'])) {
                throw new \Exception("API Error: " . ($result['error']['message'] ?? 'Erreur inconnue'));
            }
            
            $text = $result['candidates'][0]['content']['parts'][0]['text'] ?? '';
            
            if (empty($text)) {
                throw new \Exception("Aucun texte généré");
            }
            
            // Nettoyage supplémentaire : supprimer les éventuelles introductions résiduelles
            $text = preg_replace('/^(Absolument|Bien sûr|Voici|En tant qu\'expert|D\'accord|Je vais vous|Certainement)[\s!.,:]+/i', '', $text);
            $text = trim($text);
            
            $this->log('Succès ! Longueur: ' . strlen($text));
            
            return $text;
            
        } catch (\Exception $e) {
            $this->log('ERREUR: ' . $e->getMessage());
            throw new \Exception("Erreur API Gemini : " . $e->getMessage());
        }
    }
    
    private function log(string $message): void
    {
        if ($this->logger) {
            $this->logger->info('[Gemini] ' . $message);
        }
    }
}