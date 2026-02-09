<?php

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

#[Route('/admin')]
class AdminController extends AbstractController
{
    #[Route('/dashboard', name: 'app_admin_dashboard')]
    public function dashboard(UserRepository $userRepository): Response
    {
        /** @var User $admin */
        $admin = $this->getUser();

        if (!$admin || $admin->getType() !== 'admin') {
            return $this->redirectToRoute('app_login');
        }

        $admins = $userRepository->findByType('admin');
        $enseignants = $userRepository->findByType('enseignant');

        return $this->render('BackOffice/admin/Partials/base_admin.html.twig', [
            'admins' => $admins,
            'enseignants' => $enseignants,
        ]);
    }

    #[Route('/users', name: 'app_admin_users')]
        public function users(UserRepository $userRepository): Response
        {
            return $this->render('BackOffice/admin/users.html.twig', [
                'admins' => $userRepository->findByType('admin'),
                'enseignants' => $userRepository->findByType('enseignant'),
            ]);
        }


    // === GESTION DES ADMINISTRATEURS ===

    #[Route('/admin/new', name: 'app_admin_admin_new')]
    public function newAdmin(
        Request $request,
        UserPasswordHasherInterface $passwordHasher,
        EntityManagerInterface $entityManager
    ): Response {
        /** @var User $currentAdmin */
        $currentAdmin = $this->getUser();

        if (!$currentAdmin || $currentAdmin->getType() !== 'admin') {
            return $this->redirectToRoute('app_login');
        }

        $admin = new User();
        $form = $this->createForm(AdminType::class, $admin);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $admin->setType('admin');
            
            $hashedPassword = $passwordHasher->hashPassword(
                $admin,
                $form->get('password')->getData()
            );
            $admin->setPassword($hashedPassword);

            $entityManager->persist($admin);
            $entityManager->flush();

            $this->addFlash('success', 'L\'administrateur a été créé avec succès.');

            return $this->redirectToRoute('app_admin_dashboard');
        }

        return $this->render('BackOffice/admin/admin_new.html.twig', [
            'form' => $form->createView(),
        ]);
    }

    #[Route('/admin/{id}/delete', name: 'app_admin_admin_delete', methods: ['POST'])]
    public function deleteAdmin(
        User $admin,
        Request $request,
        EntityManagerInterface $entityManager
    ): Response {
        /** @var User $currentAdmin */
        $currentAdmin = $this->getUser();

        // Empêcher la suppression de soi-même
        if ($admin === $currentAdmin) {
            $this->addFlash('error', 'Vous ne pouvez pas supprimer votre propre compte.');
            return $this->redirectToRoute('app_admin_dashboard');
        }

        if ($this->isCsrfTokenValid('delete'.$admin->getId(), $request->request->get('_token'))) {
            $entityManager->remove($admin);
            $entityManager->flush();

            $this->addFlash('success', 'L\'administrateur a été supprimé avec succès.');
        }

        return $this->redirectToRoute('app_admin_dashboard');
    }

    // === GESTION DES ENSEIGNANTS ===

    #[Route('/enseignant/new', name: 'app_admin_enseignant_new')]
    public function newEnseignant(
        Request $request,
        UserPasswordHasherInterface $passwordHasher,
        EntityManagerInterface $entityManager
    ): Response {
        /** @var User $admin */
        $admin = $this->getUser();

        if (!$admin || $admin->getType() !== 'admin') {
            return $this->redirectToRoute('app_login');
        }

        $enseignant = new User();
        $form = $this->createForm(EnseignantType::class, $enseignant);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $enseignant->setType('enseignant');
            
            $hashedPassword = $passwordHasher->hashPassword(
                $enseignant,
                $form->get('password')->getData()
            );
            $enseignant->setPassword($hashedPassword);

            $entityManager->persist($enseignant);
            $entityManager->flush();

            $this->addFlash('success', 'L\'enseignant a été créé avec succès.');

            return $this->redirectToRoute('app_admin_dashboard');
        }

        return $this->render('BackOffice/admin/enseignant_new.html.twig', [
            'form' => $form->createView(),
        ]);
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

        return $this->redirectToRoute('app_admin_dashboard');
    }
}