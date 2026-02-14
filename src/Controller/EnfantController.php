<?php

namespace App\Controller;

use App\Entity\User;
use App\Form\EnfantType;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/enfant')]
class EnfantController extends AbstractController
{
    #[Route('/new', name: 'app_enfant_new', methods: ['GET', 'POST'])]
    #[IsGranted('ROLE_PARENT')]
    public function new(Request $request, EntityManagerInterface $entityManager, UserPasswordHasherInterface $passwordHasher): Response
    {
        $enfant = new User();
        $enfant->setType('kid');
        $enfant->setParent($this->getUser());
        $enfant->setActive(true);
        $enfant->setRoles(['ROLE_KID']); // Ajouter le rôle

        $form = $this->createForm(EnfantType::class, $enfant);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            // Hasher le mot de passe
            $hashedPassword = $passwordHasher->hashPassword(
                $enfant,
                $enfant->getPassword()
            );
            $enfant->setPassword($hashedPassword);

            $entityManager->persist($enfant);
            $entityManager->flush();

            $this->addFlash('success', 'Enfant ajouté avec succès!');
            return $this->redirectToRoute('app_course_index');
        }

        return $this->render('FrontOffice/enfant/new.html.twig', [
            'enfant' => $enfant,
            'form' => $form,
        ]);
    }
}