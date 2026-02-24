<?php
namespace App\Service;

use Symfony\Contracts\HttpClient\HttpClientInterface;

class GeolocationService
{
    private $httpClient;
    private $apiKey;

    public function __construct(HttpClientInterface $httpClient, string $abstractApiKey)
    {
        $this->httpClient = $httpClient;
        $this->apiKey = $abstractApiKey;
    }

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
}