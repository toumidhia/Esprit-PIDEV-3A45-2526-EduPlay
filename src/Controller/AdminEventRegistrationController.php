<?php
// src/Controller/AdminEventRegistrationController.php

namespace App\Controller;

use App\Entity\EventRegistration;
use App\Entity\SchoolEvent;
use Doctrine\ORM\EntityManagerInterface;
use Knp\Component\Pager\PaginatorInterface;
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
        $sort = (string) $request->query->get('sort', 'startDate');
        $order = strtolower((string) $request->query->get('order', 'desc')) === 'asc' ? 'ASC' : 'DESC';
        $page = $request->query->getInt('page', 1);
        $limit = 10;

        // ✅ Compter le total d'abord
        $countQb = $em->createQueryBuilder()
            ->select('COUNT(DISTINCT e.id)')
            ->from(SchoolEvent::class, 'e');

        if ($q !== '') {
            $countQb->andWhere('LOWER(e.title) LIKE :q OR LOWER(e.location) LIKE :q')
                    ->setParameter('q', '%' . mb_strtolower($q) . '%');
        }

        $total = (int) $countQb->getQuery()->getSingleScalarResult();

        // ✅ Récupérer les événements avec pagination manuelle
        $qb = $em->createQueryBuilder()
            ->select('e AS event, COUNT(r.id) AS regCount')
            ->from(SchoolEvent::class, 'e')
            ->leftJoin(EventRegistration::class, 'r', 'WITH', 'r.event = e')
            ->groupBy('e.id');

        if ($q !== '') {
            $qb->andWhere('LOWER(e.title) LIKE :q OR LOWER(e.location) LIKE :q')
               ->setParameter('q', '%' . mb_strtolower($q) . '%');
        }

        // ✅ Tri avec les vrais noms de champs
        if ($sort === 'regCount') {
            $qb->orderBy('regCount', $order);
        } elseif ($sort === 'endDate') {
            $qb->orderBy('e.endDate', $order);
        } else { // startDate par défaut
            $qb->orderBy('e.startDate', $order);
        }

        // ✅ Pagination manuelle
        $qb->setFirstResult(($page - 1) * $limit)
           ->setMaxResults($limit);

        $rows = $qb->getQuery()->getResult();
        $totalPages = (int) ceil($total / $limit);

        // ✅ Si requête AJAX
        $isAjax = $request->headers->get('X-Requested-With') === 'XMLHttpRequest';
        if ($isAjax) {
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
            'limit'       => $limit,
        ]);
    }

    #[Route('/admin/events/{id}/registrations', name: 'admin_event_registrations_show', methods: ['GET'])]
    public function show(SchoolEvent $event, Request $request, EntityManagerInterface $em): Response
    {
        $q = trim((string) $request->query->get('q', ''));
        $page = $request->query->getInt('page', 1);
        $limit = 10;

        // ✅ Compter le total
        $countQb = $em->getRepository(EventRegistration::class)->createQueryBuilder('r')
            ->select('COUNT(r.id)')
            ->andWhere('r.event = :event')
            ->setParameter('event', $event);

        if ($q !== '') {
            $countQb->andWhere('LOWER(r.childFullName) LIKE :q OR LOWER(COALESCE(r.parentPhone, \'\')) LIKE :q OR LOWER(COALESCE(r.emergencyContactName, \'\')) LIKE :q')
                    ->setParameter('q', '%' . mb_strtolower($q) . '%');
        }

        $total = (int) $countQb->getQuery()->getSingleScalarResult();

        // ✅ Récupérer les inscriptions avec pagination
        $qb = $em->getRepository(EventRegistration::class)->createQueryBuilder('r')
            ->andWhere('r.event = :event')
            ->setParameter('event', $event)
            ->orderBy('r.registeredAt', 'DESC');

        if ($q !== '') {
            $qb->andWhere('LOWER(r.childFullName) LIKE :q OR LOWER(COALESCE(r.parentPhone, \'\')) LIKE :q OR LOWER(COALESCE(r.emergencyContactName, \'\')) LIKE :q')
               ->setParameter('q', '%' . mb_strtolower($q) . '%');
        }

        $qb->setFirstResult(($page - 1) * $limit)
           ->setMaxResults($limit);

        $registrations = $qb->getQuery()->getResult();
        $totalPages = (int) ceil($total / $limit);

        // ✅ Si requête AJAX
        $isAjax = $request->headers->get('X-Requested-With') === 'XMLHttpRequest';
        if ($isAjax) {
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
            'limit'         => $limit,
        ]);
    }

    // ✅ NOUVELLES ROUTES POUR GÉRER LES STATUTS

    #[Route('/admin/registration/{id}/approve', name: 'admin_registration_approve', methods: ['POST'])]
    public function approve(EventRegistration $registration, Request $request, EntityManagerInterface $em): Response
    {
        $this->denyAccessUnlessGranted('ROLE_ADMIN');
        
        $token = $request->request->get('_token');
        if (is_string($token) && $this->isCsrfTokenValid('approve' . $registration->getId(), $token)) {
            $registration->setStatus('APPROVED');
            $em->flush();
            $this->addFlash('success', '✅ Inscription approuvée avec succès');
        }
        
        return $this->redirectToRoute('admin_event_registrations_show', ['id' => $registration->getEvent()->getId()]);
    }

    #[Route('/admin/registration/{id}/reject', name: 'admin_registration_reject', methods: ['POST'])]
    public function reject(EventRegistration $registration, Request $request, EntityManagerInterface $em): Response
    {
        $this->denyAccessUnlessGranted('ROLE_ADMIN');
        
        $token = $request->request->get('_token');
        if (is_string($token) && $this->isCsrfTokenValid('reject' . $registration->getId(), $token)) {
            $registration->setStatus('REJECTED');
            $em->flush();
            $this->addFlash('success', '❌ Inscription refusée');
        }
        
        return $this->redirectToRoute('admin_event_registrations_show', ['id' => $registration->getEvent()->getId()]);
    }

    #[Route('/admin/registration/{id}/reset', name: 'admin_registration_reset', methods: ['POST'])]
    public function resetStatus(EventRegistration $registration, Request $request, EntityManagerInterface $em): Response
    {
        $this->denyAccessUnlessGranted('ROLE_ADMIN');
        
        $token = $request->request->get('_token');
        if (is_string($token) && $this->isCsrfTokenValid('reset' . $registration->getId(), $token)) {
            $registration->setStatus('PENDING');
            $em->flush();
            $this->addFlash('success', '↩️ Statut réinitialisé (en attente)');
        }
        
        return $this->redirectToRoute('admin_event_registrations_show', ['id' => $registration->getEvent()->getId()]);
    }

    #[Route('/admin/registration/{id}/ticket', name: 'admin_registration_ticket', methods: ['GET'])]
    public function viewTicket(EventRegistration $registration): Response
    {
        $this->denyAccessUnlessGranted('ROLE_ADMIN');
        
        return $this->render('BackOffice/admin/event/ticket.html.twig', [
            'registration' => $registration,
        ]);
    }
}