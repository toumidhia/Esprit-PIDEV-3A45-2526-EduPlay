<?php
// tests/Service/EventManagerTest.php

namespace App\Tests\Service;

use App\Entity\SchoolEvent;
use App\Service\EventManager;
use PHPUnit\Framework\TestCase;

class EventManagerTest extends TestCase
{
    public function testValidEvent()
    {
        $event = new SchoolEvent();
        $event->setTitle('Atelier Mosaïstes');
        $event->setStartDate(new \DateTime('2026-03-10 10:00:00'));
        $event->setEndDate(new \DateTime('2026-03-10 12:00:00'));

        $manager = new EventManager();

        $this->assertTrue($manager->validate($event));
    }

    public function testEventWithoutTitle()
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Le titre de l\'événement est obligatoire.');

        $event = new SchoolEvent();
        $event->setStartDate(new \DateTime('2026-03-10 10:00:00'));
        $event->setEndDate(new \DateTime('2026-03-10 12:00:00'));

        $manager = new EventManager();
        $manager->validate($event);
    }

    public function testEventWithEndDateBeforeStartDate()
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('La date de fin doit être postérieure à la date de début.');

        $event = new SchoolEvent();
        $event->setTitle('Atelier Mosaïstes');
        $event->setStartDate(new \DateTime('2026-03-10 12:00:00'));
        $event->setEndDate(new \DateTime('2026-03-10 10:00:00'));

        $manager = new EventManager();
        $manager->validate($event);
    }
}