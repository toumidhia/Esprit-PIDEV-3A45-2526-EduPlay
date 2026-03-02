<?php
// tests/Service/ResourceManagerTest.php

namespace App\Tests\Service;

use App\Entity\Resource;
use App\Service\ResourceManager;
use PHPUnit\Framework\TestCase;

class ResourceManagerTest extends TestCase
{
    // ========================= TEST 1 — Ressource valide =========================
    public function testValidResource(): void
    {
        $resource = new Resource();
        $resource->setTitle('Harry Potter');
        $resource->setAuthor('J.K. Rowling');
        $resource->setMinAge(8);
        $resource->setMaxAge(12);
        $resource->setType('book');
        $resource->setLanguage('fr');

        $manager = new ResourceManager();

        $this->assertTrue($manager->validate($resource));
    }

    // ========================= TEST 2 — Titre vide =========================
    public function testResourceWithoutTitle(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Le titre est obligatoire');

        $resource = new Resource();
        $resource->setTitle('');
        $resource->setAuthor('J.K. Rowling');
        $resource->setMinAge(8);
        $resource->setMaxAge(12);
        $resource->setType('book');
        $resource->setLanguage('fr');

        $manager = new ResourceManager();
        $manager->validate($resource);
    }

    // ========================= TEST 3 — Auteur vide =========================
    public function testResourceWithoutAuthor(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('L\'auteur est obligatoire');

        $resource = new Resource();
        $resource->setTitle('Harry Potter');
        $resource->setAuthor('');
        $resource->setMinAge(8);
        $resource->setMaxAge(12);
        $resource->setType('book');
        $resource->setLanguage('fr');

        $manager = new ResourceManager();
        $manager->validate($resource);
    }

    // ========================= TEST 4 — Âge minimum négatif =========================
    public function testResourceWithNegativeMinAge(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('L\'âge minimum ne peut pas être négatif');

        $resource = new Resource();
        $resource->setTitle('Harry Potter');
        $resource->setAuthor('J.K. Rowling');
        $resource->setMinAge(-1);
        $resource->setMaxAge(12);
        $resource->setType('book');
        $resource->setLanguage('fr');

        $manager = new ResourceManager();
        $manager->validate($resource);
    }

    // ========================= TEST 5 — Âge max inférieur à âge min =========================
    public function testResourceWithMaxAgeLessThanMinAge(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('L\'âge maximum doit être supérieur à l\'âge minimum');

        $resource = new Resource();
        $resource->setTitle('Harry Potter');
        $resource->setAuthor('J.K. Rowling');
        $resource->setMinAge(12);
        $resource->setMaxAge(8);   // ❌ maxAge < minAge
        $resource->setType('book');
        $resource->setLanguage('fr');

        $manager = new ResourceManager();
        $manager->validate($resource);
    }

    // ========================= TEST 6 — Type invalide =========================
    public function testResourceWithInvalidType(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Le type doit être : book, magazine, journal ou manual');

        $resource = new Resource();
        $resource->setTitle('Harry Potter');
        $resource->setAuthor('J.K. Rowling');
        $resource->setMinAge(8);
        $resource->setMaxAge(12);
        $resource->setType('film');   // ❌ type invalide
        $resource->setLanguage('fr');

        $manager = new ResourceManager();
        $manager->validate($resource);
    }

    // ========================= TEST 7 — Langue vide =========================
    public function testResourceWithoutLanguage(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('La langue est obligatoire');

        $resource = new Resource();
        $resource->setTitle('Harry Potter');
        $resource->setAuthor('J.K. Rowling');
        $resource->setMinAge(8);
        $resource->setMaxAge(12);
        $resource->setType('book');
        $resource->setLanguage('');   // ❌ langue vide

        $manager = new ResourceManager();
        $manager->validate($resource);
    }
}