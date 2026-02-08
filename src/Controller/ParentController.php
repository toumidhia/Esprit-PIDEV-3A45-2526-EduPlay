<?php

namespace App\Controller;

use App\Entity\User;
use App\Form\EnfantType;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/parent')]
class ParentController extends AbstractController
{
    #[Route('/dashboard', name: 'app_parent_dashboard')]
    public function dashboard(): Response
    {
        /** @var User $parent */
        $parent = $this->getUser();

        if (!$parent || $parent->getType() !== 'parent') {
            return $this->redirectToRoute('app_login');
        }

        return $this->render('parent/dashboard.html.twig', [
            'parent' => $parent,
            'enfants' => $parent->getEnfants(),
        ]);
    }

    #[Route('/enfant/new', name: 'app_parent_enfant_new')]
    public function newEnfant(
        Request $request,
        UserPasswordHasherInterface $passwordHasher,
        EntityManagerInterface $entityManager
    ): Response {
        /** @var User $parent */
        $parent = $this->getUser();

        if (!$parent || $parent->getType() !== 'parent') {
            return $this->redirectToRoute('app_login');
        }

        $enfant = new User();
        $form = $this->createForm(EnfantType::class, $enfant);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            // Définir le type enfant
            $enfant->setType('enfant');
            
            // Hasher le mot de passe
            $hashedPassword = $passwordHasher->hashPassword(
                $enfant,
                $form->get('password')->getData()
            );
            $enfant->setPassword($hashedPassword);

            // Associer l'enfant au parent
            $enfant->setParent($parent);

            // Sauvegarder
            $entityManager->persist($enfant);
            $entityManager->flush();

            $this->addFlash('success', 'Le compte de ' . $enfant->getFullName() . ' a été créé avec succès ! Identifiant: ' . $enfant->getUsername());

            return $this->redirectToRoute('app_parent_dashboard');
        }

        return $this->render('parent/enfant_new.html.twig', [
            'form' => $form->createView(),
        ]);
    }

    #[Route('/enfant/{id}/delete', name: 'app_parent_enfant_delete', methods: ['POST'])]
    public function deleteEnfant(
        User $enfant,
        Request $request,
        EntityManagerInterface $entityManager
    ): Response {
        /** @var User $parent */
        $parent = $this->getUser();

        // Vérifier que l'enfant appartient bien au parent connecté
        if ($enfant->getParent() !== $parent) {
            throw $this->createAccessDeniedException('Vous ne pouvez pas supprimer cet enfant.');
        }

        if ($this->isCsrfTokenValid('delete'.$enfant->getId(), $request->request->get('_token'))) {
            $entityManager->remove($enfant);
            $entityManager->flush();

            $this->addFlash('success', 'Le compte a été supprimé avec succès.');
        }

        return $this->redirectToRoute('app_parent_dashboard');
    }
}