<?php

namespace App\Service;

use Symfony\Contracts\HttpClient\HttpClientInterface;

class AiGameSummaryService
{
    public function __construct(
        private HttpClientInterface $http,
) {}

    public function summarizeForAge(string $description, int $age): string
{
    $prompt = "Résume ce jeu pour un enfant de {$age} ans en 2 phrases simples : {$description}";

    $res = $this->http->request('POST', 'http://localhost:11434/api/generate', [
        'json' => [
            'model' => 'phi3',
            'prompt' => $prompt,
            'stream' => false
        ],
        'timeout' => 200,
    ]);

    $data = $res->toArray(false);

    return trim($data['response'] ?? $description);
}
}