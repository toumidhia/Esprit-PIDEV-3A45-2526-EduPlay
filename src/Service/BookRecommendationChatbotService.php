<?php

namespace App\Service;

class BookRecommendationChatbotService
{
    private string $groqApiKey;

    public function __construct(string $groqApiKey)
    {
        $this->groqApiKey = $groqApiKey;
    }

    /**
     * Appelle l'API Groq pour des recommandations de livres
     *
     * @param string $userMessage Message de l'enfant (préférences, envies)
     * @param array  $books       Liste des livres disponibles [{title, author, minAge, maxAge, type, summary}]
     * @param int|null $childAge  Âge de l'enfant (si connecté)
     */
    public function getRecommendation(string $userMessage, array $books, ?int $childAge = null): ?string
    {
        $booksContext = $this->formatBooksForPrompt($books);
        $ageContext   = $childAge !== null
            ? "L'enfant a {$childAge} ans. La liste ci-dessous contient UNIQUEMENT les livres adaptés à cet âge."
            : "Propose des recommandations variées pour différentes tranches d'âge.";

        $rule = $childAge !== null
            ? "- Cite TOUS les livres de la liste ci-dessus avec leur titre et auteur. C'est la liste complète pour cet âge."
            : "- Cite 1 à 3 livres avec leur titre et auteur, selon les préférences.";

        $prompt = <<<PROMPT
Tu es un assistant bienveillant qui recommande des livres pour enfants sur EduPlay.
Réponds en français, de manière simple et engageante (style enfant).

CONTEXTE :
- {$ageContext}
- Livres disponibles :

{$booksContext}

MESSAGE : "{$userMessage}"

RÈGLES :
- Recommande UNIQUEMENT des livres de la liste ci-dessus.
{$rule}
- Sois encourageant et amical.
- Si aucun livre ne correspond, dis-le et encourage à explorer d'autres thèmes.
PROMPT;

        return $this->callGroq($prompt);
    }

    private function formatBooksForPrompt(array $books): string
    {
        if (empty($books)) {
            return '(Aucun livre disponible dans la bibliothèque pour le moment.)';
        }

        $lines = [];
        foreach ($books as $i => $book) {
            $title   = $book['title'] ?? 'Sans titre';
            $author  = $book['author'] ?? 'Auteur inconnu';
            $minAge  = $book['minAge'] ?? 0;
            $maxAge  = $book['maxAge'] ?? 99;
            $type    = $book['type'] ?? 'Livre';
            $summary = mb_substr($book['summary'] ?? '', 0, 150);
            $lines[] = "- {$title} par {$author} ({$minAge}-{$maxAge} ans, {$type}) : {$summary}";
        }

        return implode("\n", $lines);
    }

    private function callGroq(string $prompt): ?string
    {
        $ch = curl_init('https://api.groq.com/openai/v1/chat/completions');
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST           => true,
            CURLOPT_POSTFIELDS     => json_encode([
                'model'      => 'llama-3.1-8b-instant',
                'messages'   => [['role' => 'user', 'content' => $prompt]],
                'max_tokens' => 1500,
            ]),
            CURLOPT_HTTPHEADER     => [
                'Content-Type: application/json',
                'Authorization: Bearer ' . $this->groqApiKey,
            ],
            CURLOPT_TIMEOUT        => 45,
            CURLOPT_SSL_VERIFYPEER => true,
        ]);

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($httpCode !== 200 || !$response) {
            return null;
        }

        $data = json_decode($response, true);
        return $data['choices'][0]['message']['content'] ?? null;
    }
}
