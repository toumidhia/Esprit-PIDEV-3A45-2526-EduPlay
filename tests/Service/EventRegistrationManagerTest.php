<?php
// tests/Service/EventRegistrationManagerTest.php

namespace App\Tests\Service;

use App\Entity\EventRegistration;
use App\Entity\SchoolEvent;
use App\Entity\User;
use App\Service\EventRegistrationManager;
use PHPUnit\Framework\TestCase;

class EventRegistrationManagerTest extends TestCase
{
    private EventRegistrationManager $manager;

    protected function setUp(): void
    {
        $this->manager = new EventRegistrationManager();
    }

    private function createValidEvent(): SchoolEvent
    {
        return (new SchoolEvent())
            ->setTitle('Atelier Test')
            ->setDescription('Description test')
            ->setLocation('Tunis')
            ->setStartDate(new \DateTime('+1 day'))
            ->setEndDate(new \DateTime('+1 day +2 hours'));
    }

    private function createValidParent(): User
    {
        $parent = new User();
        $parent->setEmail('parent@test.com');
        return $parent;
    }

    private function createValidRegistration(): EventRegistration
    {
        $registration = new EventRegistration();
        $registration->setChildFullName('Enfant Test');
        $registration->setEvent($this->createValidEvent());
        $registration->setParent($this->createValidParent());
        $registration->setStatus('PENDING');
        $registration->setRegisteredAt(new \DateTimeImmutable('-1 hour'));
        
        return $registration;
    }

    public function testValidRegistration()
    {
        $registration = $this->createValidRegistration();
        $this->assertTrue($this->manager->validate($registration));
    }

    public function testRegistrationWithoutChildName()
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Le nom de l\'enfant est obligatoire.');

        $registration = $this->createValidRegistration();
        $registration->setChildFullName('');

        $this->manager->validate($registration);
    }

    public function testRegistrationWithoutStatus()
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Le statut est obligatoire.');

        $registration = $this->createValidRegistration();
        $registration->setStatus('');

        $this->manager->validate($registration);
    }

    public function testRegistrationWithInvalidStatus()
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Statut invalide.');

        $registration = $this->createValidRegistration();
        $registration->setStatus('INVALID_STATUS');

        $this->manager->validate($registration);
    }

    public function testRegistrationWithFutureDate()
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('La date d\'inscription ne peut pas être dans le futur.');

        $registration = $this->createValidRegistration();
        $registration->setRegisteredAt(new \DateTimeImmutable('+1 day'));

        $this->manager->validate($registration);
    }

    public function testApprovedStatusIsValid()
    {
        $registration = $this->createValidRegistration();
        $registration->setStatus('APPROVED');
        
        $this->assertTrue($this->manager->validate($registration));
    }

    public function testRejectedStatusIsValid()
    {
        $registration = $this->createValidRegistration();
        $registration->setStatus('REJECTED');
        
        $this->assertTrue($this->manager->validate($registration));
    }

    public function testCancelledStatusIsValid()
    {
        $registration = $this->createValidRegistration();
        $registration->setStatus('CANCELLED');
        
        $this->assertTrue($this->manager->validate($registration));
    }
}