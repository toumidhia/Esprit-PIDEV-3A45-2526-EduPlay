<?php

namespace App\Controller\Front;

use App\Entity\SchoolEvent;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class EventController extends AbstractController
{
    #[Route('/events', name: 'front_event_index', methods: ['GET'])]
    public function index(Request $request, EntityManagerInterface $em): Response
    {
        $q = trim((string) $request->query->get('q', ''));
        $sort = (string) $request->query->get('sort', 'start'); // start|created
        $order = strtolower((string) $request->query->get('order', 'asc')) === 'desc' ? 'DESC' : 'ASC';

        $qb = $em->getRepository(SchoolEvent::class)->createQueryBuilder('e');

        if ($q !== '') {
            $qb->andWhere('LOWER(e.title) LIKE :q OR LOWER(e.description) LIKE :q')
               ->setParameter('q', '%' . mb_strtolower($q) . '%');
        }

        if ($sort === 'created') {
            $qb->orderBy('e.createdAt', $order);
        } else {
            $qb->orderBy('e.startDate', $order);
        }

        $events = $qb->getQuery()->getResult();

        return $this->render('front/event/index.html.twig', [
            'events' => $events,
            'q' => $q,
            'sort' => $sort,
            'order' => strtolower($order), // 'asc' ou 'desc' pour le twig
        ]);
    }

    #[Route('/events/{id}', name: 'front_event_show', methods: ['GET'])]
    public function show(SchoolEvent $event): Response
    {
        // ✅ Ressources associées triées (createdAt DESC)
        $resources = $event->getResources() ? $event->getResources()->toArray() : [];

        usort($resources, function ($a, $b) {
            $tb = $b->getCreatedAt()?->getTimestamp() ?? 0;
            $ta = $a->getCreatedAt()?->getTimestamp() ?? 0;
            return $tb <=> $ta;
        });

        return $this->render('front/event/show.html.twig', [
            'event' => $event,
            'resources' => $resources,
        ]);
    }
}
