<?php

namespace App\Tests\Controller;

use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class AdminControllerTest extends WebTestCase
{
    public function testAdminUsersPageRequiresAuth(): void
    {
        $client = static::createClient();
        $client->request('GET', '/admin/users');

        $this->assertResponseRedirects('/login');
    }

    public function testAdminCanAccessUsersPage(): void
    {
        $client = static::createClient();

        /** @var EntityManagerInterface $em */
        $em = static::getContainer()->get(EntityManagerInterface::class);
        $userRepository = $em->getRepository(User::class);

        $admin = $userRepository->findOneBy(['type' => 'admin']);

        if (!$admin instanceof User) {
            $this->markTestSkipped('Aucun admin en base pour tester');
        }

        $client->loginUser($admin);
        $client->request('GET', '/admin/users');

        $this->assertResponseIsSuccessful();
        $this->assertSelectorTextContains('h1', 'Gestion des Utilisateurs');
    }

    public function testPaginationWorks(): void
    {
        $client = static::createClient();

        /** @var EntityManagerInterface $em */
        $em = static::getContainer()->get(EntityManagerInterface::class);
        $userRepository = $em->getRepository(User::class);

        $admin = $userRepository->findOneBy(['type' => 'admin']);

        if (!$admin instanceof User) {
            $this->markTestSkipped('Aucun admin en base pour tester');
        }

        $client->loginUser($admin);
        $client->request('GET', '/admin/users?page=1&limit=10');

        $this->assertResponseIsSuccessful();
    }
}