<?php

namespace App\Service;

use Symfony\Contracts\HttpClient\HttpClientInterface;

class GeolocationService
{
    private const API_URL = 'https://ipapi.co/';

    public function __construct(
        private HttpClientInterface $httpClient
    ) {}

    /**
     * Récupère les infos de géolocalisation depuis une IP
     */
    public function getLocationFromIp(string $ip): ?array
    {
        // Ignorer les IPs locales
        if ($this->isLocalIp($ip)) {
            return [
                'ip' => $ip,
                'city' => 'Localhost',
                'region' => 'Dev',
                'country_name' => 'Development',
                'country_code' => 'DEV',
                'latitude' => 0,
                'longitude' => 0,
                'timezone' => 'UTC',
            ];
        }

        try {
            $response = $this->httpClient->request('GET', self::API_URL . $ip . '/json/');
            
            if ($response->getStatusCode() === 200) {
                return $response->toArray();
            }
            
        } catch (\Exception $e) {
            // En cas d'erreur, retourner null
            return null;
        }

        return null;
    }

    /**
     * Vérifie si c'est une IP locale
     */
    private function isLocalIp(string $ip): bool
    {
        return in_array($ip, ['127.0.0.1', '::1', 'localhost']) 
            || str_starts_with($ip, '192.168.')
            || str_starts_with($ip, '10.');
    }

    /**
     * Détecte si une connexion est suspecte
     */
    public function isSuspiciousConnection(string $currentCountry, array $recentCountries): bool
    {
        // Si plus de 2 pays différents en 24h → suspect
        $uniqueCountries = array_unique($recentCountries);
        
        if (count($uniqueCountries) >= 3) {
            return true;
        }

        // Liste de pays à haut risque (exemple)
        $highRiskCountries = ['RU', 'CN', 'KP', 'IR'];
        
        if (in_array($currentCountry, $highRiskCountries)) {
            return true;
        }

        return false;
    }
}