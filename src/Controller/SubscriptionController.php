<?php
// src/Controller/SubscriptionController.php

namespace App\Controller;

use App\Entity\Subscription;
use App\Entity\Course;
use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/subscription')]
class SubscriptionController extends AbstractController
{
    #[Route('/subscribe/{courseId}/{kidId}', name: 'app_subscription_subscribe', methods: ['POST'])]
    #[IsGranted('ROLE_PARENT')]
    public function subscribe(int $courseId, int $kidId, EntityManagerInterface $entityManager, Request $request): Response
    {
        // Verify CSRF token
        $csrfToken = $request->request->get('_token');
        if (!$this->isCsrfTokenValid('subscribe' . $courseId . $kidId, $csrfToken)) {
            $this->addFlash('error', 'Token CSRF invalide.');
            return $this->redirectToRoute('app_course_index');
        }

        // Get the course
        $course = $entityManager->getRepository(Course::class)->find($courseId);
        if (!$course) {
            $this->addFlash('error', 'Cours non trouvé.');
            return $this->redirectToRoute('app_course_index');
        }

        // Get the kid (user with type 'kid')
        $kid = $entityManager->getRepository(User::class)->find($kidId);
        if (!$kid || $kid->getType() !== 'kid') {
            $this->addFlash('error', 'Enfant non trouvé.');
            return $this->redirectToRoute('app_course_index');
        }

        // Check if parent owns this kid
        $currentUser = $this->getUser();
        if (!$kid->getParent() || $kid->getParent()->getId() !== $currentUser->getId()) {
            $this->addFlash('error', 'Cet enfant ne vous appartient pas.');
            return $this->redirectToRoute('app_course_index');
        }

        // Check if already subscribed
        $existingSubscription = $entityManager->getRepository(Subscription::class)->findOneBy([
            'course' => $course,
            'kid' => $kid,
            'active' => true
        ]);

        if ($existingSubscription) {
            $this->addFlash('warning', 'Cet enfant est déjà inscrit à ce cours.');
            return $this->redirectToRoute('app_course_index');
        }

        // Create new subscription
        $subscription = new Subscription();
        $subscription->setCourse($course);
        $subscription->setKid($kid);
        $subscription->setParent($currentUser);
        $subscription->setActive(true);

        $entityManager->persist($subscription);
        $entityManager->flush();

        $this->addFlash('success', 'Inscription réussie!');
        return $this->redirectToRoute('app_course_index');
    }

    #[Route('/unsubscribe/{courseId}/{kidId}', name: 'app_subscription_unsubscribe', methods: ['POST'])]
    #[IsGranted('ROLE_PARENT')]
    public function unsubscribe(int $courseId, int $kidId, EntityManagerInterface $entityManager, Request $request): Response
    {
        // Verify CSRF token
        $csrfToken = $request->request->get('_token');
        if (!$this->isCsrfTokenValid('unsubscribe' . $courseId . $kidId, $csrfToken)) {
            $this->addFlash('error', 'Token CSRF invalide.');
            return $this->redirectToRoute('app_course_index');
        }

        // Get the course
        $course = $entityManager->getRepository(Course::class)->find($courseId);
        if (!$course) {
            $this->addFlash('error', 'Cours non trouvé.');
            return $this->redirectToRoute('app_course_index');
        }

        // Get the kid
        $kid = $entityManager->getRepository(User::class)->find($kidId);
        if (!$kid || $kid->getType() !== 'kid') {
            $this->addFlash('error', 'Enfant non trouvé.');
            return $this->redirectToRoute('app_course_index');
        }

        // Check if parent owns this kid
        $currentUser = $this->getUser();
        if (!$kid->getParent() || $kid->getParent()->getId() !== $currentUser->getId()) {
            $this->addFlash('error', 'Cet enfant ne vous appartient pas.');
            return $this->redirectToRoute('app_course_index');
        }

        // Find the subscription
        $subscription = $entityManager->getRepository(Subscription::class)->findOneBy([
            'course' => $course,
            'kid' => $kid,
            'active' => true
        ]);

        if (!$subscription) {
            $this->addFlash('error', 'Inscription non trouvée.');
            return $this->redirectToRoute('app_course_index');
        }

        // Deactivate subscription
        $subscription->setActive(false);
        $entityManager->flush();

        $this->addFlash('success', 'Désinscription réussie!');
        return $this->redirectToRoute('app_course_index');
    }

    #[Route('/list', name: 'app_subscription_list', methods: ['GET'])]
    public function list(EntityManagerInterface $entityManager): Response
    {
        $user = $this->getUser();

        if (!$user) {
            return $this->redirectToRoute('app_login');
        }

        // Si l'utilisateur est ADMIN - voir TOUTES les inscriptions
        if ($this->isGranted('ROLE_ADMIN')) {
            $subscriptions = $entityManager->getRepository(Subscription::class)->findBy(
                ['active' => true],
                ['subscribedAt' => 'DESC']
            );

            // Calculer les statistiques pour l'admin
            $uniqueCourses = [];
            $uniqueKids = [];
            $uniqueParents = [];

            foreach ($subscriptions as $sub) {
                $uniqueCourses[$sub->getCourse()->getId()] = true;
                $uniqueKids[$sub->getKid()->getId()] = true;
                $uniqueParents[$sub->getParent()->getId()] = true;
            }

            return $this->render('BackOffice/subscription/list.html.twig', [
                'subscriptions' => $subscriptions,
                'uniqueCoursesCount' => count($uniqueCourses),
                'uniqueKidsCount' => count($uniqueKids),
                'uniqueParentsCount' => count($uniqueParents),
            ]);
        }

        // Si l'utilisateur est PARENT - voir SES inscriptions
        if ($this->isGranted('ROLE_PARENT')) {
            $subscriptions = $entityManager->getRepository(Subscription::class)->findBy(
                ['parent' => $user, 'active' => true],
                ['subscribedAt' => 'DESC']
            );

            return $this->render('FrontOffice/subscription/list.html.twig', [
                'subscriptions' => $subscriptions,
            ]);
        }

        // Sinon, accès refusé
        $this->addFlash('error', 'Vous n\'avez pas accès à cette page.');
        return $this->redirectToRoute('app_course_index');
    }
}