<?php
// tests/Service/EventResourceManagerTest.php

namespace App\Tests\Service;

use App\Entity\EventResource;
use App\Service\EventResourceManager;
use PHPUnit\Framework\TestCase;

class EventResourceManagerTest extends TestCase
{
    private EventResourceManager $manager;

    protected function setUp(): void
    {
        $this->manager = new EventResourceManager();
    }

    public function testValidPdfResource()
    {
        $resource = new EventResource();
        $resource->setTitle('Document PDF');
        $resource->setType('PDF');
        $resource->setFilePath('/uploads/documents/test.pdf');

        $this->assertTrue($this->manager->validate($resource));
    }

    public function testValidLinkResource()
    {
        $resource = new EventResource();
        $resource->setTitle('Lien utile');
        $resource->setType('LINK');
        $resource->setUrl('https://example.com');

        $this->assertTrue($this->manager->validate($resource));
    }

    public function testValidChecklistResource()
    {
        $resource = new EventResource();
        $resource->setTitle('Checklist préparation');
        $resource->setType('CHECKLIST');
        $resource->setContext('- Matériel 1\n- Matériel 2');

        $this->assertTrue($this->manager->validate($resource));
    }

    public function testValidPlanningResource()
    {
        $resource = new EventResource();
        $resource->setTitle('Planning journée');
        $resource->setType('PLANNING');
        $resource->setContext('09:00 Accueil\n10:00 Activité');

        $this->assertTrue($this->manager->validate($resource));
    }

    public function testResourceWithoutTitle()
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Le titre de la ressource est obligatoire.');

        $resource = new EventResource();
        $resource->setType('PDF');
        $resource->setFilePath('/uploads/test.pdf');

        $this->manager->validate($resource);
    }

    public function testResourceWithoutType()
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Le type de ressource est obligatoire.');

        $resource = new EventResource();
        $resource->setTitle('Sans type');

        $this->manager->validate($resource);
    }

    public function testResourceWithInvalidType()
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Type de ressource invalide.');

        $resource = new EventResource();
        $resource->setTitle('Type invalide');
        $resource->setType('INVALID_TYPE');

        $this->manager->validate($resource);
    }

    public function testLinkResourceWithoutUrl()
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Pour une ressource de type LINK, l\'URL est obligatoire.');

        $resource = new EventResource();
        $resource->setTitle('Lien sans URL');
        $resource->setType('LINK');

        $this->manager->validate($resource);
    }

    public function testPdfResourceWithoutFile()
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Pour une ressource de type PDF, le fichier est obligatoire.');

        $resource = new EventResource();
        $resource->setTitle('PDF sans fichier');
        $resource->setType('PDF');

        $this->manager->validate($resource);
    }

    public function testChecklistResourceWithoutContext()
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Pour une ressource de type CHECKLIST/PLANNING, le contenu est obligatoire.');

        $resource = new EventResource();
        $resource->setTitle('Checklist vide');
        $resource->setType('CHECKLIST');

        $this->manager->validate($resource);
    }
}