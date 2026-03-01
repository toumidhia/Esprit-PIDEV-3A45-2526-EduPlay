<?php
// src/Controller/EventController.php

namespace App\Controller;

use App\Entity\SchoolEvent;
use App\Service\RecommendationEventService; // 👈 AJOUTE CET IMPORT
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

use Symfony\Component\Security\Http\Attribute\IsGranted;

#[IsGranted('ROLE_USER')]
class EventController extends AbstractController
{
    #[Route('/events', name: 'front_event_index', methods: ['GET'])]
    public function index(Request $request, EntityManagerInterface $em, RecommendationEventService $recommendationEventService): Response // 👈 AJOUTE LE SERVICE
    {
        $q = trim((string) $request->query->get('q', ''));
        $sort = (string) $request->query->get('sort', 'start');
        $order = strtolower((string) $request->query->get('order', 'asc')) === 'desc' ? 'DESC' : 'ASC';
        $page = $request->query->getInt('page', 1);
        $limit = 6;

        // Compter le total
        $countQb = $em->getRepository(SchoolEvent::class)->createQueryBuilder('e')
            ->select('COUNT(e.id)');

        if ($q !== '') {
            $countQb->andWhere('LOWER(e.title) LIKE :q OR LOWER(e.description) LIKE :q')
                    ->setParameter('q', '%' . mb_strtolower($q) . '%');
        }

        $total = $countQb->getQuery()->getSingleScalarResult();

        // Récupérer les événements
        $qb = $em->getRepository(SchoolEvent::class)->createQueryBuilder('e');

        if ($q !== '') {
            $qb->andWhere('LOWER(e.title) LIKE :q OR LOWER(e.description) LIKE :q')
               ->setParameter('q', '%' . mb_strtolower($q) . '%');
        }

        if ($sort === 'created') {
            $qb->orderBy('e.createdAt', $order);
        } elseif ($sort === 'title') {
            $qb->orderBy('e.title', $order);
        } else {
            $qb->orderBy('e.startDate', $order);
        }

        $qb->setFirstResult(($page - 1) * $limit)
           ->setMaxResults($limit);

        $events = $qb->getQuery()->getResult();
        $totalPages = ceil($total / $limit);

        // ✅ RÉCUPÉRER LES RECOMMANDATIONS
        $recommendations = [];
        if ($this->getUser()) {
            // 3 recommandations maximum en haut de page
            /** @var \App\Entity\User $user */
            $user = $this->getUser();
            $recommendations = $recommendationEventService->getRecommendationsForParent($user, 3);
        }

        // Si requête AJAX
        if ($request->headers->get('X-Requested-With') === 'XMLHttpRequest') {
            return $this->render('FrontOffice/Parent/event/_event_cards.html.twig', [
                'events' => $events,
            ]);
        }

        return $this->render('FrontOffice/Parent/event/index.html.twig', [
            'events' => $events,
            'recommendations' => $recommendations, // 👈 PASSE LES RECOS À LA VUE
            'q' => $q,
            'sort' => $sort,
            'order' => strtolower($order),
            'currentPage' => $page,
            'totalPages' => $totalPages,
            'total' => $total,
            'limit' => $limit,
        ]);
    }

    #[Route('/events/{id}', name: 'front_event_show', methods: ['GET'])]
    public function show(SchoolEvent $event): Response
    {
        $resources = $event->getResources()->toArray();

        usort($resources, function ($a, $b) {
            $tb = $b->getCreatedAt()?->getTimestamp() ?? 0;
            $ta = $a->getCreatedAt()?->getTimestamp() ?? 0;
            return $tb <=> $ta;
        });

        return $this->render('FrontOffice/Parent/event/show.html.twig', [
            'event' => $event,
            'resources' => $resources,
        ]);
    }
}