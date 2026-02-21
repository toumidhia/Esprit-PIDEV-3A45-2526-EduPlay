<?php

namespace App\Controller;

use App\Entity\User;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/enfant')]
#[IsGranted('ROLE_ENFANT')]
class EnfantController extends AbstractController
{
    #[Route('/dashboard', name: 'app_enfant_dashboard')]
    public function dashboard(): Response
    {
        /** @var User $enfant */
        $enfant = $this->getUser();
        
        $parent = $enfant->getParent();
        $age = $enfant->getAge();

        return $this->render('FrontOffice/enfant/dashboard.html.twig', [
            'enfant' => $enfant,
            'parent' => $parent,
            'age' => $age
        ]);
    }
}