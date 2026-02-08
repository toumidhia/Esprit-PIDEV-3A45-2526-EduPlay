<?php

namespace App\Controller;

use App\Entity\User;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/enfant')]
class EnfantController extends AbstractController
{
    #[Route('/dashboard', name: 'app_enfant_dashboard')]
    public function dashboard(): Response
    {
        /** @var User $enfant */
        $enfant = $this->getUser();

        if (!$enfant || $enfant->getType() !== 'enfant') {
            return $this->redirectToRoute('app_login');
        }

        return $this->render('enfant/Partials/base_enfant.html.twig', [
            'enfant' => [
            'id' => $enfant->getId(),
            'firstName' => $enfant->getFirstName(),
            'lastName' => $enfant->getLastName(),
            'fullName' => $enfant->getFullName(),
            'username' => $enfant->getUsername(),
            'age' => $enfant->getAge(),
            'niveau' => $enfant->getNiveau(),
                ],
            'parent' => $enfant->getParent(),
        ]);
    }
}
