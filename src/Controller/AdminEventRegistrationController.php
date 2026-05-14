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
    private const PER_PAGE_EVENTS = 8;
    private const PER_PAGE_REGS   = 10;

    #[Route('/admin/event-registrations', name: 'admin_event_registrations_events', methods: ['GET'])]
    public function events(Request $request, EntityManagerInterface $em): Response
    {
        $q     = trim((string) $request->query->get('q', ''));
        $sort  = (string) $request->query->get('sort', 'startDate'); // startDate|endDate|regCount
        $order = strtolower((string) $request->query->get('order', 'desc')) === 'asc' ? 'ASC' : 'DESC';
        $page  = max(1, (int) $request->query->get('page', 1));

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
        if ($sort === 'endDate') {
            $qb->orderBy('e.endDate', $order);
        } elseif ($sort === 'regCount') {
            $qb->orderBy('regCount', $order);
        } else {
            $qb->orderBy('e.startDate', $order);
        }

        // ✅ total (pagination)
        $countQb = clone $qb;
        $total = count($countQb->getQuery()->getResult());

        $totalPages = (int) max(1, (int) ceil($total / self::PER_PAGE_EVENTS));
        if ($page > $totalPages) $page = $totalPages;

        $qb->setFirstResult(($page - 1) * self::PER_PAGE_EVENTS)
           ->setMaxResults(self::PER_PAGE_EVENTS);

        $rows = $qb->getQuery()->getResult();

        // ✅ AJAX => renvoyer فقط rows
        if ($request->isXmlHttpRequest()) {
            return $this->render('BackOffice/admin/event/_events_rows.html.twig', [
                'rows' => $rows,
            ]);
        }

        return $this->render('BackOffice/admin/event/registration.html.twig', [
            'rows'        => $rows,
            'q'           => $q,
            'sort'        => $sort,
            'order'       => strtolower($order),
            'currentPage' => $page,
            'totalPages'  => $totalPages,
            'total'       => $total,
        ]);
    }

    #[Route('/admin/events/{id}/registrations', name: 'admin_event_registrations_show', methods: ['GET'])]
    public function show(SchoolEvent $event, Request $request, EntityManagerInterface $em): Response
    {
        $q    = trim((string) $request->query->get('q', ''));
        $page = max(1, (int) $request->query->get('page', 1));

        $qb = $em->getRepository(EventRegistration::class)->createQueryBuilder('r')
            ->andWhere('r.event = :event')
            ->setParameter('event', $event)
            ->orderBy('r.registeredAt', 'DESC');

        if ($q !== '') {
            $qb->andWhere("
                LOWER(r.childFullName) LIKE :q
                OR LOWER(COALESCE(r.parentPhone, '')) LIKE :q
                OR LOWER(COALESCE(r.emergencyContactName, '')) LIKE :q
                OR LOWER(COALESCE(r.emergencyContactPhone, '')) LIKE :q
            ")->setParameter('q', '%' . mb_strtolower($q) . '%');
        }

        // total
        $countQb = clone $qb;
        $total = (int) $countQb->select('COUNT(r.id)')->getQuery()->getSingleScalarResult();

        $totalPages = (int) max(1, (int) ceil($total / self::PER_PAGE_REGS));
        if ($page > $totalPages) $page = $totalPages;

        $qb->select('r')
           ->setFirstResult(($page - 1) * self::PER_PAGE_REGS)
           ->setMaxResults(self::PER_PAGE_REGS);

        $registrations = $qb->getQuery()->getResult();

        // ✅ AJAX => renvoyer فقط rows
        if ($request->isXmlHttpRequest()) {
            return $this->render('BackOffice/admin/event/_registrations_rows.html.twig', [
                'registrations' => $registrations,
            ]);
        }

        return $this->render('BackOffice/admin/event/registration.html.twig', [
            'event'         => $event,
            'registrations' => $registrations,
            'q'             => $q,
            'currentPage'   => $page,
            'totalPages'    => $totalPages,
            'total'         => $total,
        ]);
    }
}