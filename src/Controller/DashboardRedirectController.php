<?php

namespace App\Controller;

use App\Entity\User;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class DashboardRedirectController extends AbstractController
{
    #[Route('/dashboard', name: 'app_dashboard_redirect')]
    public function index(): Response
    {
        $user = $this->getUser();
        
        if (!$user instanceof User) {
            return $this->redirectToRoute('app_login');
        }

        if ($user->isAdmin()) {
            return $this->redirectToRoute('app_admin_dashboard');
        }
        
        if ($user->isEnseignant()) {
            return $this->redirectToRoute('teacher_game_index');
        }
        
        if ($user->isParent()) {
            return $this->redirectToRoute('app_parent_dashboard');
        }
        
        if ($user->isEnfant()) {
            return $this->redirectToRoute('front_games');
        }

        return $this->redirectToRoute('app_home');
    }
}