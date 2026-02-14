<?php

namespace App\Controller;

use App\Entity\User;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/enseignant')]
#[IsGranted('ROLE_TEACHER')]
class EnseignantController extends AbstractController
{
    #[Route('/dashboard', name: 'app_enseignant_dashboard')]
    public function dashboard(): Response
    {
        /** @var User $enseignant */
        $enseignant = $this->getUser();

        return $this->render('FrontOffice/enseignant/dashboard.html.twig', [
            'enseignant' => $enseignant,
        ]);
    }
}