<?php
// src/Controller/AdminController.php

namespace App\Controller;

use App\Entity\User;
use App\Form\AdminType;
use App\Form\EnseignantType;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/admin')]
#[IsGranted('ROLE_ADMIN')]
class AdminController extends AbstractController
{
    #[Route('/users', name: 'app_admin_users')]
    public function users(UserRepository $userRepository): Response
    {
        // Récupérer tous les utilisateurs triés par date de création
        $users = $userRepository->findBy([], ['createdAt' => 'DESC']);

        // Compter par type
        $stats = [
            'admin' => $userRepository->count(['type' => 'admin']),
            'enseignant' => $userRepository->count(['type' => 'teacher']),
            'parent' => $userRepository->count(['type' => 'parent']),
            'enfant' => $userRepository->count(['type' => 'kid']),
            'total' => count($users)
        ];

        return $this->render('BackOffice/admin/users.html.twig', [
            'users' => $users,
            'stats' => $stats
        ]);
    }

    #[Route('/admin/new', name: 'app_admin_admin_new')]
    public function newAdmin(
        Request $request,
        UserPasswordHasherInterface $passwordHasher,
        EntityManagerInterface $entityManager
    ): Response {
        $admin = new User();
        $form = $this->createForm(AdminType::class, $admin);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $admin->setType('admin');
            $admin->setRoles(['ROLE_ADMIN', 'ROLE_USER']);
            $admin->setActive(true);
            $admin->setCreatedAt(new \DateTime());

            $hashedPassword = $passwordHasher->hashPassword(
                $admin,
                $form->get('password')->getData()
            );
            $admin->setPassword($hashedPassword);

            $entityManager->persist($admin);
            $entityManager->flush();

            $this->addFlash('success', 'L\'administrateur a été créé avec succès.');
            return $this->redirectToRoute('app_admin_users');
        }

        return $this->render('BackOffice/admin/admin_new.html.twig', [
            'form' => $form->createView(),
        ]);
    }

    #[Route('/enseignant/new', name: 'app_admin_enseignant_new')]
public function newEnseignant(
    Request $request,
    UserPasswordHasherInterface $passwordHasher,
    EntityManagerInterface $entityManager
): Response {
    $enseignant = new User();
    $form = $this->createForm(EnseignantType::class, $enseignant, [
        'validation_groups' => false,  // ✅ DÉSACTIVER LA VALIDATION
    ]);
    
    $form->handleRequest($request);

    if ($form->isSubmitted()) {
        
        // Forcer les valeurs
        $enseignant->setType('enseignant');
        $enseignant->setActive(true);
        $enseignant->setBirthDate(null);  // ✅ Explicitement NULL
        $enseignant->setUsername(null);   // ✅ Explicitement NULL
        
        $plainPassword = $form->get('password')->getData();
        
        if ($plainPassword) {
            $hashedPassword = $passwordHasher->hashPassword($enseignant, $plainPassword);
            $enseignant->setPassword($hashedPassword);
        }

        try {
            $entityManager->persist($enseignant);
            $entityManager->flush();
            
            $this->addFlash('success', 'Enseignant créé !');
            return $this->redirectToRoute('app_admin_users');
            
        } catch (\Exception $e) {
            $this->addFlash('error', 'Erreur : ' . $e->getMessage());
        }
    }

    return $this->render('BackOffice/admin/enseignant_new.html.twig', [
        'form' => $form->createView(),
    ]);
}

    #[Route('/admin/{id}/delete', name: 'app_admin_admin_delete', methods: ['POST'])]
    public function deleteAdmin(
        User $admin,
        Request $request,
        EntityManagerInterface $entityManager
    ): Response {
        // Empêcher la suppression de soi-même
        if ($admin === $this->getUser()) {
            $this->addFlash('error', 'Vous ne pouvez pas supprimer votre propre compte.');
            return $this->redirectToRoute('app_admin_users');
        }

        if ($this->isCsrfTokenValid('delete'.$admin->getId(), $request->request->get('_token'))) {
            $entityManager->remove($admin);
            $entityManager->flush();

            $this->addFlash('success', 'L\'administrateur a été supprimé avec succès.');
        }

        return $this->redirectToRoute('app_admin_users');
    }

    #[Route('/enseignant/{id}/delete', name: 'app_admin_enseignant_delete', methods: ['POST'])]
    public function deleteEnseignant(
        User $enseignant,
        Request $request,
        EntityManagerInterface $entityManager
    ): Response {
        if ($this->isCsrfTokenValid('delete'.$enseignant->getId(), $request->request->get('_token'))) {
            $entityManager->remove($enseignant);
            $entityManager->flush();

            $this->addFlash('success', 'L\'enseignant a été supprimé avec succès.');
        }

        return $this->redirectToRoute('app_admin_users');
    }

    #[Route('/user/{id}/toggle-status', name: 'app_admin_user_toggle', methods: ['POST'])]
    public function toggleUserStatus(
        User $user,
        Request $request,
        EntityManagerInterface $entityManager
    ): Response {
        if ($this->isCsrfTokenValid('toggle' . $user->getId(), $request->request->get('_token'))) {
            // Empêcher la désactivation de soi-même
            if ($user === $this->getUser()) {
                $this->addFlash('error', 'Vous ne pouvez pas désactiver votre propre compte.');
                return $this->redirectToRoute('app_admin_users');
            }

            $user->setActive(!$user->isActive());
            $entityManager->flush();

            $status = $user->isActive() ? 'activé' : 'désactivé';
            $this->addFlash('success', "L'utilisateur {$user->getFullName()} a été {$status}.");
        }

        return $this->redirectToRoute('app_admin_users');
    }

    #[Route('/user/{id}/delete', name: 'app_admin_user_delete', methods: ['POST'])]
    public function deleteUser(
        User $user,
        Request $request,
        EntityManagerInterface $entityManager
    ): Response {
        if ($this->isCsrfTokenValid('delete' . $user->getId(), $request->request->get('_token'))) {
            // Empêcher la suppression de soi-même
            if ($user === $this->getUser()) {
                $this->addFlash('error', 'Vous ne pouvez pas supprimer votre propre compte.');
                return $this->redirectToRoute('app_admin_users');
            }

            $fullName = $user->getFullName();
            $entityManager->remove($user);
            $entityManager->flush();

            $this->addFlash('success', "L'utilisateur {$fullName} a été supprimé.");
        }

        return $this->redirectToRoute('app_admin_users');
    }
}