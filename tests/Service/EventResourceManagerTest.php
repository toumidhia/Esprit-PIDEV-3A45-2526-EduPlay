<?php
// tests/Service/EventResourceManagerTest.php

namespace App\Tests\Service;

use App\Entity\EventResource;
use App\Entity\SchoolEvent;
use App\Service\EventResourceManager;
use PHPUnit\Framework\TestCase;

class EventResourceManagerTest extends TestCase
{
    private EventResourceManager $manager;
    private SchoolEvent $event;

    protected function setUp(): void
    {
        $this->manager = new EventResourceManager();
        
        // Créer un événement valide pour les tests
        $this->event = (new SchoolEvent())
            ->setTitle('Événement Test')
            ->setDescription('Description test')
            ->setLocation('Tunis')
            ->setStartDate(new \DateTime('+1 day'))
            ->setEndDate(new \DateTime('+1 day +2 hours'));
    }

    private function createValidResource(): EventResource
    {
        return (new EventResource())
            ->setEvent($this->event)
            ->setCreatedAt(new \DateTimeImmutable())
            ->setTitle('Titre par défaut')  // ✅ AJOUTÉ
            ->setType('PDF');                // ✅ AJOUTÉ
    }

    public function testValidPdfResource()
    {
        $resource = $this->createValidResource();
        $resource->setFilePath('/uploads/documents/test.pdf');

        $this->assertTrue($this->manager->validate($resource));
    }

    public function testValidLinkResource()
    {
        $resource = $this->createValidResource();
        $resource->setType('LINK');
        $resource->setUrl('https://example.com');

        $this->assertTrue($this->manager->validate($resource));
    }

    public function testValidChecklistResource()
    {
        $resource = $this->createValidResource();
        $resource->setType('CHECKLIST');
        $resource->setContext('- Matériel 1\n- Matériel 2');

        $this->assertTrue($this->manager->validate($resource));
    }

    public function testValidPlanningResource()
    {
        $resource = $this->createValidResource();
        $resource->setType('PLANNING');
        $resource->setContext('09:00 Accueil\n10:00 Activité');

        $this->assertTrue($this->manager->validate($resource));
    }

    public function testResourceWithoutTitle()
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Le titre de la ressource est obligatoire.');

        $resource = $this->createValidResource();
        $resource->setTitle('');  // ✅ Titre vide (pas null)
        $resource->setType('PDF');
        $resource->setFilePath('/uploads/test.pdf');

        $this->manager->validate($resource);
    }

    public function testResourceWithoutType()
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Le type de ressource est obligatoire.');

        $resource = $this->createValidResource();
        $resource->setTitle('Sans type');
        $resource->setType('');  // ✅ Type vide (pas null)

        $this->manager->validate($resource);
    }

    public function testResourceWithInvalidType()
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Type de ressource invalide.');

        $resource = $this->createValidResource();
        $resource->setTitle('Type invalide');
        $resource->setType('INVALID_TYPE');

        $this->manager->validate($resource);
    }

    public function testLinkResourceWithoutUrl()
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Pour une ressource de type LINK, l\'URL est obligatoire.');

        $resource = $this->createValidResource();
        $resource->setTitle('Lien sans URL');
        $resource->setType('LINK');
        // Pas d'URL

        $this->manager->validate($resource);
    }

    public function testPdfResourceWithoutFile()
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Pour une ressource de type PDF, le fichier est obligatoire.');

        $resource = $this->createValidResource();
        $resource->setTitle('PDF sans fichier');
        $resource->setType('PDF');
        // Pas de filePath

        $this->manager->validate($resource);
    }

    public function testChecklistResourceWithoutContext()
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Pour une ressource de type CHECKLIST/PLANNING, le contenu est obligatoire.');

        $resource = $this->createValidResource();
        $resource->setTitle('Checklist vide');
        $resource->setType('CHECKLIST');
        // Pas de context

        $this->manager->validate($resource);
    }
}