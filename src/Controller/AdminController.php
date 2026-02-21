<?php

namespace App\Controller;

use App\Entity\User;
use App\Form\AdminType;
use App\Form\EnseignantType;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Knp\Component\Pager\PaginatorInterface;
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
    public function users(
        Request $request,
        UserRepository $userRepository,
        PaginatorInterface $paginator
    ): Response {
        // Récupérer les paramètres de filtre
        $typeFilter = $request->query->get('type', 'all');
        $statusFilter = $request->query->get('status', 'all');
        $searchTerm = $request->query->get('search', '');

        // Créer la requête de base
        $queryBuilder = $userRepository->createQueryBuilder('u');

        // Filtre par type
        if ($typeFilter !== 'all') {
            $queryBuilder->andWhere('u.type = :type')
                ->setParameter('type', $typeFilter);
        }

        // Filtre par statut
        if ($statusFilter !== 'all') {
            $queryBuilder->andWhere('u.active = :status')
                ->setParameter('status', $statusFilter === 'active' ? 1 : 0);
        }

        // Recherche par nom ou email
        if (!empty($searchTerm)) {
            $queryBuilder->andWhere('u.firstName LIKE :search OR u.lastName LIKE :search OR u.email LIKE :search')
                ->setParameter('search', '%' . $searchTerm . '%');
        }

        // Tri par défaut
        if (!$request->query->get('sort')) {
    $queryBuilder->orderBy('u.createdAt', 'DESC');
}

        // Pagination
        $pagination = $paginator->paginate(
            $queryBuilder,
            $request->query->getInt('page', 1), // Numéro de page
            $request->query->getInt('limit', 10) // Limite par page (10, 20, 50)
        );
        

        // Statistiques (sans filtres)
        $stats = [
            'admin' => $userRepository->count(['type' => 'admin']),
            'enseignant' => $userRepository->count(['type' => 'enseignant']),
            'parent' => $userRepository->count(['type' => 'parent']),
            'enfant' => $userRepository->count(['type' => 'enfant']),
            'total' => $userRepository->count([])
        ];

        return $this->render('BackOffice/admin/gestion_user/users.html.twig', [
            'pagination' => $pagination,
            'stats' => $stats,
            'currentType' => $typeFilter,
            'currentStatus' => $statusFilter,
            'currentSearch' => $searchTerm,
            'currentLimit' => $request->query->getInt('limit', 10),
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
            
            $plainPassword = $form->get('password')->getData();
            
            if (!$plainPassword) {
                $this->addFlash('error', 'Le mot de passe est obligatoire.');
                return $this->render('BackOffice/admin/gestion_user/admin_new.html.twig', [
                    'form' => $form->createView(),
                ]);
            }

            $admin->setType('admin');
            $hashedPassword = $passwordHasher->hashPassword($admin, $plainPassword);
            $admin->setPassword($hashedPassword);

            $entityManager->persist($admin);
            $entityManager->flush();

            $this->addFlash('success', 'L\'administrateur a été créé avec succès.');
            return $this->redirectToRoute('app_admin_users');
        }

        return $this->render('BackOffice/admin/gestion_user/admin_new.html.twig', [
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
        $form = $this->createForm(EnseignantType::class, $enseignant);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            
            $plainPassword = $form->get('password')->getData();
            
            if (!$plainPassword) {
                $this->addFlash('error', 'Le mot de passe est obligatoire.');
                return $this->render('BackOffice/admin/gestion_user/enseignant_new.html.twig', [
                    'form' => $form->createView(),
                ]);
            }

            $enseignant->setType('enseignant');
            $hashedPassword = $passwordHasher->hashPassword($enseignant, $plainPassword);
            $enseignant->setPassword($hashedPassword);

            $entityManager->persist($enseignant);
            $entityManager->flush();

            $this->addFlash('success', 'L\'enseignant ' . $enseignant->getFullName() . ' a été créé avec succès.');
            return $this->redirectToRoute('app_admin_users');
        }

        return $this->render('BackOffice/admin/gestion_user/enseignant_new.html.twig', [
            'form' => $form->createView(),
        ]);
    }

    #[Route('/admin/{id}/delete', name: 'app_admin_admin_delete', methods: ['POST'])]
    public function deleteAdmin(
        User $admin,
        Request $request,
        EntityManagerInterface $entityManager
    ): Response {
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