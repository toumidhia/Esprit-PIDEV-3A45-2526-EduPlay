<?php

namespace App\Controller;

use App\Entity\User;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/enseignant')]
class EnseignantController extends AbstractController
{
    #[Route('/dashboard', name: 'app_enseignant_dashboard')]
    public function dashboard(): Response
    {
        /** @var User $enseignant */
        $enseignant = $this->getUser();

        if (!$enseignant || $enseignant->getType() !== 'enseignant') {
            return $this->redirectToRoute('app_login');
        }

        return $this->render('FrontOffice/enseignant/Partials/base_enseignant.html.twig', [
            'enseignant' => $enseignant,
        ]);
    }
}