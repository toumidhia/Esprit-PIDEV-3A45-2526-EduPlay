<?php
// tests/Service/EventManagerTest.php

namespace App\Tests\Service;

use App\Entity\SchoolEvent;
use App\Service\EventManager;
use PHPUnit\Framework\TestCase;

class EventManagerTest extends TestCase
{
    private function createValidEvent(): SchoolEvent
    {
        return (new SchoolEvent())
            ->setTitle('Atelier Mosaïstes')
            ->setDescription('Description de test')
            ->setLocation('Tunis')
            ->setStartDate(new \DateTime('2026-03-10 10:00:00'))
            ->setEndDate(new \DateTime('2026-03-10 12:00:00'));
    }

    public function testValidEvent()
    {
        $event = $this->createValidEvent();
        $manager = new EventManager();

        $this->assertTrue($manager->validate($event));
    }

    public function testEventWithoutTitle()
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Le titre de l\'événement est obligatoire.');

        $event = $this->createValidEvent()->setTitle('');

        $manager = new EventManager();
        $manager->validate($event);
    }

    public function testEventWithEndDateBeforeStartDate()
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('La date de fin doit être postérieure à la date de début.');

        $event = $this->createValidEvent()
            ->setStartDate(new \DateTime('2026-03-10 12:00:00'))
            ->setEndDate(new \DateTime('2026-03-10 10:00:00'));

        $manager = new EventManager();
        $manager->validate($event);
    }

    public function testEventWithoutDescription()
    {
        $event = $this->createValidEvent()->setDescription('');
        $manager = new EventManager();

        // La validation ne vérifie pas la description, donc ça doit passer
        $this->assertTrue($manager->validate($event));
    }

    public function testEventWithoutLocation()
    {
        $event = $this->createValidEvent()->setLocation('');
        $manager = new EventManager();

        // La validation ne vérifie pas le lieu, donc ça doit passer
        $this->assertTrue($manager->validate($event));
    }
}