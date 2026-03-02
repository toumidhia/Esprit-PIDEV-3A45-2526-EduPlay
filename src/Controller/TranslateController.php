<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Contracts\HttpClient\HttpClientInterface;

class TranslateController extends AbstractController
{
  #[Route('/translate', name: 'app_translate', methods: ['POST'])]
public function translate(Request $request, HttpClientInterface $http): JsonResponse
{
    // Lire le JSON envoyé par fetch
    $payload = json_decode($request->getContent(), true) ?? [];

    $text   = trim((string)($payload['text'] ?? ''));
    $target = (string)($payload['target'] ?? 'en');
    $source = (string)($payload['source'] ?? 'fr');

    // Vérification texte vide
    if ($text === '') {
        return new JsonResponse([
            'translatedText' => ''
        ]);
    }

    // Lire la clé API depuis .env
    $apiKey = $_ENV['GOOGLE_TRANSLATE_API_KEY'] ?? '';

    if ($apiKey === '') {
        return new JsonResponse([
            'error' => 'GOOGLE_TRANSLATE_API_KEY is missing in .env'
        ], 500);
    }

    try {
        // Endpoint Google Translate v2 (compatible API key)
        $url = 'https://translation.googleapis.com/language/translate/v2';

        $response = $http->request('POST', $url, [
            'query' => [
                'key' => $apiKey
            ],
            'json' => [
                'q'      => $text,
                'source' => $source,
                'target' => $target,
                'format' => 'text'
            ]
        ]);

        $status = $response->getStatusCode();
        $data   = $response->toArray(false);

        // Gestion erreur Google
        if ($status >= 400 || isset($data['error'])) {
            return new JsonResponse([
                'error' => 'Google API error',
                'google_status' => $status,
                'google_response' => $data
            ], 500);
        }

        // Extraire traduction
        $translated = $data['data']['translations'][0]['translatedText'] ?? null;

        if (!$translated) {
            return new JsonResponse([
                'error' => 'Translation failed',
                'google_response' => $data
            ], 500);
        }

        return new JsonResponse([
            'translatedText' => $translated
        ]);

    } catch (\Exception $e) {
        return new JsonResponse([
            'error' => 'Server error',
            'message' => $e->getMessage()
        ], 500);
    }
}
}