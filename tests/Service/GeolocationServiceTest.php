<?php

namespace App\Tests\Service;

use App\Service\GeolocationService;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

class GeolocationServiceTest extends KernelTestCase
{
    private GeolocationService $service;

    protected function setUp(): void
    {
        self::bootKernel();
        $container = static::getContainer();
        $this->service = $container->get(GeolocationService::class);
    }

    public function testGetLocationReturnsStructuredData(): void
    {
        $location = $this->service->getLocation('8.8.8.8');

        // Vérifie la structure
        $this->assertArrayHasKey('country', $location);
        $this->assertArrayHasKey('city', $location);
        $this->assertArrayHasKey('region', $location);

        // Vérifie que les valeurs ne sont pas vides
        $this->assertNotEmpty($location['country']);
    }

    public function testLocalIpReturnsStructuredData(): void
    {
        $location = $this->service->getLocation('127.0.0.1');

        $this->assertArrayHasKey('country', $location);
        $this->assertArrayHasKey('city', $location);
        $this->assertArrayHasKey('region', $location);
    }

    public function testIsSuspiciousConnectionWithMultipleCountries(): void
    {
        $result = $this->service->isSuspiciousConnection('US', ['FR', 'TN', 'US']);
        $this->assertTrue($result);

        $result = $this->service->isSuspiciousConnection('FR', ['FR', 'FR']);
        $this->assertFalse($result);
    }

    public function testHighRiskCountry(): void
    {
        $result = $this->service->isSuspiciousConnection('RU', ['RU']);
        $this->assertTrue($result);
    }
}