<?php
namespace App\Service;

use Symfony\Contracts\HttpClient\HttpClientInterface;
use Psr\Log\LoggerInterface;

class AgeDetectionService
{
    private const MIN_AGE = 3;
    private const MAX_AGE = 12;

    public function __construct(
        private HttpClientInterface $httpClient,
        private string $cohereApiKey,
        private LoggerInterface $logger
    ) {}

    public function detectAgeRange(
        string $title,
        string $author = '',
        string $summary = '',
        string $pdfContent = ''
    ): array {
        $prompt = $this->buildPrompt($title, $author, $summary, $pdfContent);

        try {
            $response = $this->httpClient->request('POST',
                'https://api.cohere.com/v2/chat',
                [
                    'headers' => [
                        'Content-Type' => 'application/json',
                        'Authorization' => 'Bearer ' . $this->cohereApiKey,
                    ],
                    'verify_peer' => false,
                    'verify_host' => false,
                    'timeout' => 15,
                    'json' => [
                        'model' => 'command-r-plus-08-2024',
                        'messages' => [
                            ['role' => 'user', 'content' => $prompt]
                        ],
                    ]
                ]
            );

            $data = $response->toArray(false);
            $text = $data['message']['content'][0]['text'] ?? '';

            if (empty($text)) {
                $this->logger->error('Cohere réponse vide: ' . json_encode($data));
                return $this->defaultRange();
            }

            return $this->parseResponse($text);

        } catch (\Exception $e) {
            $this->logger->error('Cohere Exception: ' . $e->getMessage());
            return $this->defaultRange();
        }
    }

    private function buildPrompt(string $title, string $author, string $summary, string $pdfContent): string
    {
        $excerpt = !empty($pdfContent) ? mb_substr($pdfContent, 0, 1500) : 'Non disponible';
        $summaryText = !empty($summary) ? $summary : 'Non disponible';
        $authorText = !empty($author) ? $author : 'Non disponible';

        return <<<PROMPT
Tu es un expert en littérature jeunesse francophone. Analyse ce livre pour enfants.

RÈGLES ABSOLUES:
- L'âge minimum possible est 3 ans
- L'âge maximum possible est 12 ans
- Ne dépasse JAMAIS ces limites
- Réponds UNIQUEMENT avec du JSON brut, sans markdown, sans explication

LIVRE:
- Titre: {$title}
- Auteur: {$authorText}
- Résumé: {$summaryText}
- Extrait du contenu: {$excerpt}

FORMAT DE RÉPONSE (JSON uniquement):
{"age_min": <entier entre 3 et 12>, "age_max": <entier entre 3 et 12>, "reason": "<raison courte en français>"}
PROMPT;
    }

    private function parseResponse(string $text): array
    {
        $text = preg_replace('/```json\s*|\s*```/', '', trim($text));
        $text = trim($text);

        $data = json_decode($text, true);

        if (json_last_error() === JSON_ERROR_NONE && isset($data['age_min'], $data['age_max'])) {
            $ageMin = max(self::MIN_AGE, min(self::MAX_AGE, (int) $data['age_min']));
            $ageMax = max(self::MIN_AGE, min(self::MAX_AGE, (int) $data['age_max']));

            if ($ageMin > $ageMax) {
                [$ageMin, $ageMax] = [$ageMax, $ageMin];
            }

            return [
                'age_min' => $ageMin,
                'age_max' => $ageMax,
                'reason'  => $data['reason'] ?? '',
                'success' => true,
            ];
        }

        $this->logger->warning('Parse failed: ' . $text);
        return $this->defaultRange();
    }

    private function defaultRange(): array
    {
        return [
            'age_min' => 6,
            'age_max' => 9,
            'reason'  => 'Détection automatique indisponible',
            'success' => false,
        ];
    }
}