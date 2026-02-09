<?php

namespace App\Controller;

use App\Entity\User;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class DashboardController extends AbstractController
{
    #[Route('/dashboard', name: 'app_dashboard')]
    public function dashboard(): Response
    {
        /** @var User|null $user */
        $user = $this->getUser();
        
        if (!$user) {
            return $this->redirectToRoute('app_login');
        }

        // Redirection selon le type d'utilisateur
        if ($user->isAdmin()) {
            return $this->redirectToRoute('app_admin_dashboard');
        }

        if ($user->isParent()) {
            return $this->redirectToRoute('app_parent_dashboard');
        }

        if ($user->isEnseignant()) {
            return $this->redirectToRoute('app_enseignant_dashboard');
        }

        if ($user->isEnfant()) {
            return $this->redirectToRoute('app_enfant_dashboard');
        }

        // Par défaut
        return $this->render('dashboard/index.html.twig', [
            'user' => $user,
        ]);
    }
}