<?php

namespace App\Controller;

use App\Entity\EventRegistration;
use App\Entity\SchoolEvent;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class AdminEventRegistrationController extends AbstractController
{
    #[Route('/admin/event-registrations', name: 'admin_event_registrations_events', methods: ['GET'])]
    public function events(Request $request, EntityManagerInterface $em): Response
    {
        // $this->denyAccessUnlessGranted('ROLE_ADMIN');

        $q = trim((string) $request->query->get('q', ''));

        // sort: start | end | count
        $sort = (string) $request->query->get('sort', 'start');

        // order: asc | desc
        $order = strtolower((string) $request->query->get('order', 'desc')) === 'asc' ? 'ASC' : 'DESC';

        $qb = $em->createQueryBuilder()
            ->select('e AS event, COUNT(r.id) AS regCount')
            ->from(SchoolEvent::class, 'e')
            ->leftJoin(EventRegistration::class, 'r', 'WITH', 'r.event = e')
            ->groupBy('e.id');

        if ($q !== '') {
            $qb->andWhere('LOWER(e.title) LIKE :q OR LOWER(e.location) LIKE :q')
               ->setParameter('q', '%' . mb_strtolower($q) . '%');
        }

        // ✅ tri
        if ($sort === 'count') {
            $qb->orderBy('regCount', $order)
               ->addOrderBy('e.startDate', 'DESC');
        } elseif ($sort === 'end') {
            $qb->orderBy('e.endDate', $order)
               ->addOrderBy('e.startDate', 'DESC');
        } else { // start
            $qb->orderBy('e.startDate', $order)
               ->addOrderBy('e.endDate', 'DESC');
        }

        $rows = $qb->getQuery()->getResult();

        return $this->render('BackOffice/admin/event/registration.html.twig', [
            'rows'  => $rows,
            'q'     => $q,
            'sort'  => $sort,
            'order' => strtolower($order),
        ]);
    }

    #[Route('/admin/events/{id}/registrations', name: 'admin_event_registrations_show', methods: ['GET'])]
    public function show(SchoolEvent $event, Request $request, EntityManagerInterface $em): Response
    {
        $q = trim((string) $request->query->get('q', ''));

        $qb = $em->getRepository(EventRegistration::class)->createQueryBuilder('r')
            ->andWhere('r.event = :event')
            ->setParameter('event', $event)
            ->orderBy('r.registeredAt', 'DESC');

        if ($q !== '') {
            $qb->andWhere('LOWER(r.childFullName) LIKE :q OR LOWER(COALESCE(r.parentPhone, \'\')) LIKE :q OR LOWER(COALESCE(r.emergencyContactName, \'\')) LIKE :q')
               ->setParameter('q', '%' . mb_strtolower($q) . '%');
        }

        $registrations = $qb->getQuery()->getResult();

        return $this->render('BackOffice/admin/event/registration.html.twig', [
            'event'         => $event,
            'registrations' => $registrations,
            'q'             => $q,
        ]);
    }
}