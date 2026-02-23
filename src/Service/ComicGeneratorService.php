<?php
// src/Service/ComicGeneratorService.php

namespace App\Service;

class ComicGeneratorService
{
    private string $apiKey;
    private string $comicsDir;

    public function __construct(string $geminiApiKey, string $projectDir)
    {
        $this->apiKey    = $geminiApiKey;
        $this->comicsDir = $projectDir . '/public/comics';

        if (!is_dir($this->comicsDir)) {
            mkdir($this->comicsDir, 0777, true);
        }
    }

    // ========================= MÉTHODE PRINCIPALE =========================
    public function generateComic(int $resourceId, string $pdfText, int $minAge, int $maxAge): array
    {
        // Vérifier si la BD existe déjà en cache
        if ($this->comicExists($resourceId)) {
            return $this->loadFromCache($resourceId);
        }

        // Étape 1 — Résumé
        $summary = $this->generateSummary($pdfText, $minAge, $maxAge);

        // Étape 2 — Scénario
        $scenario = $this->generateScenario($summary, $minAge, $maxAge);

        // Étape 3 — Images
        $scenes = $this->generateImages($resourceId, $scenario);

        $this->saveCache($resourceId, $scenes);
        return $scenes;
    }

    // ========================= ÉTAPE 1 — RÉSUMÉ =========================
    private function generateSummary(string $pdfText, int $minAge, int $maxAge): string
    {
        $truncatedText = mb_substr($pdfText, 0, 8000);

        $prompt = "Tu es un assistant pour enfants.
Lis ce texte de livre et génère un résumé court et simple de 200 mots maximum.
Le résumé doit être adapté aux enfants de {$minAge} à {$maxAge} ans.
Utilise des phrases simples et courtes.
Identifie les personnages principaux et les moments clés de l'histoire.
Réponds UNIQUEMENT avec le résumé, sans introduction ni commentaire.

Texte du livre :
{$truncatedText}";

        $response = $this->callOpenRouter($prompt);
        return $response ?? 'Résumé non disponible';
    }

    // ========================= ÉTAPE 2 — SCÉNARIO =========================
    private function generateScenario(string $summary, int $minAge, int $maxAge): array
    {
        $prompt = "Tu es un créateur de bandes dessinées pour enfants.
Depuis ce résumé : {$summary}

Crée exactement 5 scènes pour une bande dessinée adaptée aux enfants de {$minAge} à {$maxAge} ans.
Réponds UNIQUEMENT avec un JSON valide, sans texte avant ou après, sans balises markdown.

Format JSON attendu :
{
  \"titre\": \"titre court et accrocheur de la BD\",
  \"scenes\": [
    {
      \"numero\": 1,
      \"description_image\": \"description détaillée de l'illustration à dessiner, style cartoon coloré\",
      \"dialogue\": \"texte court et simple dans la bulle de dialogue (max 15 mots)\",
      \"narrateur\": \"texte narratif court en haut de la case (max 10 mots)\"
    }
  ]
}";

        $response = $this->callOpenRouter($prompt);

        if (!$response) {
            return $this->getDefaultScenario();
        }

        $cleaned = preg_replace('/```json\s*|\s*```/', '', $response);
        $cleaned = trim($cleaned);

        $data = json_decode($cleaned, true);

        if (!$data || !isset($data['scenes'])) {
            return $this->getDefaultScenario();
        }

        return $data;
    }

    // ========================= ÉTAPE 3 — IMAGES =========================
    private function generateImages(int $resourceId, array $scenario): array
    {
        $scenes = [];

        foreach ($scenario['scenes'] as $scene) {
            $description = urlencode(
                'comic book illustration for children, colorful cartoon style, ' .
                ($scene['description_image'] ?? 'happy scene') .
                ', thick black outlines, bright colors, no text'
            );

            // Pollinations.ai — gratuit, sans clé
            $imageUrl = "https://image.pollinations.ai/prompt/{$description}?width=400&height=300&nologo=true";

            $scenes[] = [
                'numero'    => $scene['numero'],
                'image_url' => $imageUrl,
                'dialogue'  => $scene['dialogue'] ?? '',
                'narrateur' => $scene['narrateur'] ?? '',
            ];
        }

        return [
            'titre'  => $scenario['titre'] ?? 'Ma BD',
            'scenes' => $scenes,
        ];
    }

    // ========================= APPEL OPENROUTER =========================
    private function callOpenRouter(string $prompt): ?string
    {
        $url  = 'https://openrouter.ai/api/v1/chat/completions';
        $body = json_encode([
            'model'    => 'mistralai/mistral-7b-instruct:free',
            'messages' => [
                ['role' => 'user', 'content' => $prompt]
            ],
            'max_tokens' => 1000,
        ]);

        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST           => true,
            CURLOPT_POSTFIELDS     => $body,
            CURLOPT_HTTPHEADER     => [
                'Content-Type: application/json',
                'Authorization: Bearer ' . $this->apiKey,
                'HTTP-Referer: http://127.0.0.1:8000',
                'X-Title: EduPlay',
            ],
            CURLOPT_TIMEOUT        => 60,
            CURLOPT_SSL_VERIFYPEER => false,
        ]);

        $response  = curl_exec($ch);
        $httpCode  = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $curlError = curl_error($ch);
        curl_close($ch);

        // Log debug
        $logDir = __DIR__ . '/../../var/log';
        if (!is_dir($logDir)) mkdir($logDir, 0777, true);
        file_put_contents($logDir . '/openrouter.txt',
            "=== " . date('Y-m-d H:i:s') . " ===\n" .
            "KEY: " . substr($this->apiKey, 0, 15) . "...\n" .
            "HTTP: $httpCode\n" .
            "CURL ERROR: $curlError\n" .
            "RESPONSE: $response\n\n",
            FILE_APPEND
        );

        if ($httpCode !== 200 || !$response) return null;

        $data = json_decode($response, true);
        return $data['choices'][0]['message']['content'] ?? null;
    }

    // ========================= CACHE =========================
    private function comicExists(int $resourceId): bool
    {
        return file_exists($this->comicsDir . "/comic_{$resourceId}_data.json");
    }

    private function loadFromCache(int $resourceId): array
    {
        $data = file_get_contents($this->comicsDir . "/comic_{$resourceId}_data.json");
        return json_decode($data, true);
    }

    private function saveCache(int $resourceId, array $scenes): void
    {
        file_put_contents(
            $this->comicsDir . "/comic_{$resourceId}_data.json",
            json_encode($scenes, JSON_UNESCAPED_UNICODE)
        );
    }

    // ========================= SCÉNARIO PAR DÉFAUT =========================
    private function getDefaultScenario(): array
    {
        return [
            'titre'  => 'Mon Histoire',
            'scenes' => [
                ['numero' => 1, 'description_image' => 'Un enfant heureux dans un jardin coloré', 'dialogue' => 'Quelle belle aventure !', 'narrateur' => 'Il était une fois...'],
                ['numero' => 2, 'description_image' => "L'enfant découvre quelque chose de mystérieux", 'dialogue' => "Qu'est-ce que c'est ?", 'narrateur' => 'Soudain...'],
                ['numero' => 3, 'description_image' => "L'enfant rencontre un ami", 'dialogue' => "Je vais t'aider !", 'narrateur' => 'Un nouvel ami apparaît'],
                ['numero' => 4, 'description_image' => 'Ils surmontent un obstacle ensemble', 'dialogue' => 'On y est presque !', 'narrateur' => 'Le défi commence'],
                ['numero' => 5, 'description_image' => 'Fin heureuse, tous célèbrent', 'dialogue' => 'Nous avons réussi !', 'narrateur' => 'Et ils vécurent heureux !'],
            ]
        ];
    }
}