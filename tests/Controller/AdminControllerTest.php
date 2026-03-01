<?php

namespace App\Tests\Controller;

use App\Entity\User;
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
        
        // Simuler connexion admin
        $userRepository = static::getContainer()->get('doctrine')->getRepository(User::class);
        $admin = $userRepository->findOneBy(['type' => 'admin']);
        
        if ($admin) {
            $client->loginUser($admin);
            $client->request('GET', '/admin/users');
            
            $this->assertResponseIsSuccessful();
            $this->assertSelectorTextContains('h1', 'Gestion des Utilisateurs');
        } else {
            $this->markTestSkipped('Aucun admin en base pour tester');
        }
    }

    public function testPaginationWorks(): void
    {
        $client = static::createClient();
        
        $userRepository = static::getContainer()->get('doctrine')->getRepository(User::class);
        $admin = $userRepository->findOneBy(['type' => 'admin']);
        
        if ($admin) {
            $client->loginUser($admin);
            $client->request('GET', '/admin/users?page=1&limit=10');
            
            $this->assertResponseIsSuccessful();
        }
    }
}