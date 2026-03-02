<?php

namespace App\Tests\Service;

use App\Entity\Seance;
use App\Service\SeanceManager;
use PHPUnit\Framework\TestCase;

class SeanceManagerTest extends TestCase
{
    public function testValidSeance(): void
    {
        $seance = new Seance();
        $seance->setStartTime(new \DateTime('tomorrow 10:00:00'));
        $seance->setEndTime(new \DateTime('tomorrow 12:00:00'));
        $seance->setStatus('scheduled');

        $manager = new SeanceManager();
        $this->assertTrue($manager->validate($seance));
    }

    // La date et l'heure de fin doivent être postérieures à la date et l'heure de début.
    public function testSeanceWithEndDateBeforeStartDate(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage("La date de fin d'un événement doit être postérieure à la date de début.");

        $seance = new Seance();
        $seance->setStartTime(new \DateTime('tomorrow 12:00:00'));
        $seance->setEndTime(new \DateTime('tomorrow 10:00:00'));
        $seance->setStatus('scheduled');

        $manager = new SeanceManager();
        $manager->validate($seance);
    }

    // Le statut de la séance doit correspondre à une liste stricte (scheduled, ongoing, completed, cancelled).
    public function testSeanceWithInvalidStatus(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage("Statut invalide.");

        $seance = new Seance();
        $seance->setStartTime(new \DateTime('tomorrow 10:00:00'));
        $seance->setEndTime(new \DateTime('tomorrow 12:00:00'));
        $seance->setStatus('invalid_status');

        $manager = new SeanceManager();
        $manager->validate($seance);
    }
}
