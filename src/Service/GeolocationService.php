<?php

namespace App\Service;

use Symfony\Contracts\HttpClient\HttpClientInterface;

class GeolocationService
{
    private HttpClientInterface $httpClient;
    private string $apiKey;

    public function __construct(HttpClientInterface $httpClient, string $abstractApiKey = '')
    {
        $this->httpClient = $httpClient;
        $this->apiKey = $abstractApiKey;
    }

    /**
     * @return array{city: string, country: string, region: string}
     */
    public function getLocation(string $ip): array
    {
        try {
            $response = $this->httpClient->request('GET', 'https://ipgeolocation.abstractapi.com/v1/', [
                'query' => [
                    'api_key' => $this->apiKey,
                    'ip_address' => $ip,
                ]
            ]);

            $data = $response->toArray();

            return [
                'city' => $data['city'] ?? 'Unknown',
                'country' => $data['country'] ?? 'Unknown',
                'region' => $data['region'] ?? 'Unknown',
            ];
        } catch (\Exception $e) {
            return [
                'city' => 'Unknown',
                'country' => 'Unknown',
                'region' => 'Unknown',
            ];
        }
    }
    
    /**
     * @param array<string> $historyCountries
     */
    public function isSuspiciousConnection(string $currentCountry, array $historyCountries): bool
    {
        $highRisk = ['RU'];

        if (in_array($currentCountry, $highRisk, true)) {
            return true;
        }

        if (count(array_unique($historyCountries)) >= 3) {
            return true;
        }

        return false;
    }
}