<?php

namespace App\Tests\Entity;

use App\Entity\User;
use PHPUnit\Framework\TestCase;

class UserTest extends TestCase
{
    public function testGetFullName(): void
    {
        $user = new User();
        $user->setFirstName('John');
        $user->setLastName('Doe');
        
        $this->assertEquals('John Doe', $user->getFullName());
    }

    public function testUserTypesSetter(): void
    {
        $user = new User();
        
        $user->setType('admin');
        $this->assertTrue($user->isAdmin());
        
        $user->setType('enseignant');
        $this->assertTrue($user->isEnseignant());
        
        $user->setType('parent');
        $this->assertTrue($user->isParent());
        
        $user->setType('enfant');
        $this->assertTrue($user->isEnfant());
    }

    public function testUserRolesAutomaticAssignment(): void
    {
        $user = new User();
        $user->setType('admin');
        
        $this->assertContains('ROLE_ADMIN', $user->getRoles());
        $this->assertContains('ROLE_USER', $user->getRoles());
    }

    public function testGetAge(): void
    {
        $user = new User();
        $birthDate = new \DateTime('2010-01-01');
        $user->setBirthDate($birthDate);
        
        $age = $user->getAge();
        $this->assertGreaterThanOrEqual(14, $age);
        $this->assertLessThanOrEqual(16, $age);
    }
}