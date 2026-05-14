<?php
// src/Controller/EventAdminController.php

namespace App\Controller;

use App\Entity\SchoolEvent;
use App\Form\SchoolEventType;
use Doctrine\ORM\EntityManagerInterface;
use Knp\Component\Pager\PaginatorInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\File\Exception\FileException;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\String\Slugger\SluggerInterface;

class EventAdminController extends AbstractController
{
    #[Route('/admin/events', name: 'admin_event_index', methods: ['GET'])]
    public function index(Request $request, EntityManagerInterface $em): Response
    {
        // ✅ auto-suppression des événements passés
        $now = new \DateTimeImmutable();
        $pastEvents = $em->createQueryBuilder()
            ->select('e')
            ->from(SchoolEvent::class, 'e')
            ->where('e.endDate < :now')
            ->setParameter('now', $now)
            ->getQuery()
            ->getResult();

        foreach ($pastEvents as $ev) {
            $em->remove($ev);
        }
        if (!empty($pastEvents)) {
            $em->flush();
        }

        // ✅ Recherche + tri
        $q = trim((string) $request->query->get('q', ''));
        $sort = (string) $request->query->get('sort', 'createdAt');
        $order = strtolower((string) $request->query->get('order', 'desc')) === 'asc' ? 'ASC' : 'DESC';
        $page = $request->query->getInt('page', 1);
        $limit = 5;

        $allowedSort = ['createdAt', 'startDate', 'endDate', 'title'];
        if (!in_array($sort, $allowedSort, true)) {
            $sort = 'createdAt';
        }

        // ✅ Compter le total d'abord
        $countQb = $em->getRepository(SchoolEvent::class)->createQueryBuilder('e')
            ->select('COUNT(e.id)');

        if ($q !== '') {
            $countQb->andWhere('LOWER(e.title) LIKE :q OR LOWER(e.location) LIKE :q OR LOWER(e.description) LIKE :q')
                    ->setParameter('q', '%'.mb_strtolower($q).'%');
        }

        $total = (int) $countQb->getQuery()->getSingleScalarResult();

        // ✅ Récupérer les événements avec pagination manuelle
        $qb = $em->getRepository(SchoolEvent::class)->createQueryBuilder('e');

        if ($q !== '') {
            $qb->andWhere('LOWER(e.title) LIKE :q OR LOWER(e.location) LIKE :q OR LOWER(e.description) LIKE :q')
            ->setParameter('q', '%'.mb_strtolower($q).'%');
        }

        $qb->orderBy('e.'.$sort, $order)
        ->setFirstResult(($page - 1) * $limit)
        ->setMaxResults($limit);

        $events = $qb->getQuery()->getResult();

        // ✅ Calculer les infos de pagination
        $totalPages = (int) ceil($total / $limit);

        // ✅ si requête AJAX
        $isAjax = $request->headers->get('X-Requested-With') === 'XMLHttpRequest';
        if ($isAjax) {
            return $this->render('BackOffice/admin/event/_rows.html.twig', [
                'events' => $events,
            ]);
        }

        return $this->render('BackOffice/admin/event/index.html.twig', [
            'events' => $events,
            'q' => $q,
            'sort' => $sort,
            'order' => strtolower($order),
            'currentPage' => $page,
            'totalPages' => $totalPages,
            'total' => $total,
            'limit' => $limit,
        ]);
    }

    #[Route('/admin/events/stats', name: 'admin_event_stats', methods: ['GET'])]
    public function stats(): Response
    {
        return $this->render('BackOffice/admin/event/stats.html.twig');
    }

    #[Route('/admin/events/new', name: 'admin_event_new', methods: ['GET', 'POST'])]
    public function new(Request $request, EntityManagerInterface $em, SluggerInterface $slugger): Response
    {
        $event = new SchoolEvent();
        $event->setCreatedAt(new \DateTimeImmutable());

        $form = $this->createForm(SchoolEventType::class, $event);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {

            $imageFile = $form->get('imageFile')->getData();

            if ($imageFile) {
                $original = pathinfo($imageFile->getClientOriginalName(), PATHINFO_FILENAME);
                $safe = $slugger->slug($original);
                $newName = $safe.'-'.uniqid('', true).'.'.($imageFile->guessExtension() ?: 'jpg');

                try {
                    $imageFile->move($this->getParameter('event_images_dir'), $newName);
                    $event->setImagePath('uploads/events/'.$newName);
                } catch (FileException $e) {
                    $this->addFlash('error', "Erreur upload image.");
                }
            }

            $em->persist($event);
            $em->flush();

            $this->addFlash('success', 'Événement créé avec succès.');
            return $this->redirectToRoute('admin_event_index');
        }

        return $this->render('BackOffice/admin/event/new.html.twig', [
            'form' => $form->createView(),
        ]);
    }

    #[Route('/admin/events/calendar', name: 'admin_event_calendar', methods: ['GET'])]
    public function calendar(): Response
    {
        return $this->render('BackOffice/admin/event/calendar.html.twig');
    }

    #[Route('/admin/events/{id}', name: 'admin_event_show', methods: ['GET'])]
    public function show(SchoolEvent $event): Response
    {
        return $this->render('BackOffice/admin/event/show.html.twig', [
            'event' => $event,
        ]);
    }

    #[Route('/admin/events/calendar/load', name: 'admin_event_calendar_load', methods: ['GET'])]
    public function calendarLoad(Request $request, EntityManagerInterface $em): Response
    {
        // Récupérer les paramètres de début et fin envoyés par FullCalendar
        $startParam = $request->query->get('start');
        $endParam = $request->query->get('end');
        
        $start = new \DateTime(is_string($startParam) ? $startParam : 'now');
        $end = new \DateTime(is_string($endParam) ? $endParam : 'now');

        $events = $em->getRepository(SchoolEvent::class)->createQueryBuilder('e')
            ->where('e.startDate BETWEEN :start AND :end')
            ->orWhere('e.endDate BETWEEN :start AND :end')
            ->orWhere('e.startDate <= :start AND e.endDate >= :end')
            ->setParameter('start', $start)
            ->setParameter('end', $end)
            ->orderBy('e.startDate', 'ASC')
            ->getQuery()
            ->getResult();

        $calendarEvents = [];
        $now = new \DateTime();

        foreach ($events as $event) {
            $calendarEvents[] = [
                'id' => $event->getId(),
                'title' => $event->getTitle(),
                'start' => $event->getStartDate()->format('Y-m-d\TH:i:s'),
                'end' => $event->getEndDate()->format('Y-m-d\TH:i:s'),
                'url' => $this->generateUrl('admin_event_show', ['id' => $event->getId()]),
                'backgroundColor' => $event->getStartDate() > $now ? '#4f46e5' : '#6b7280',
                'borderColor' => $event->getStartDate() > $now ? '#4f46e5' : '#6b7280',
                'textColor' => '#ffffff',
                'description' => $event->getDescription(),
                'location' => $event->getLocation()
            ];
        }

        return $this->json($calendarEvents);
    }

    #[Route('/admin/events/{id}/edit', name: 'admin_event_edit', methods: ['GET', 'POST'])]
    public function edit(SchoolEvent $event, Request $request, EntityManagerInterface $em, SluggerInterface $slugger): Response
    {
        $form = $this->createForm(SchoolEventType::class, $event);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {

            $imageFile = $form->get('imageFile')->getData();
            if ($imageFile) {
                $original = pathinfo($imageFile->getClientOriginalName(), PATHINFO_FILENAME);
                $safe = $slugger->slug($original);
                $newName = $safe.'-'.uniqid('', true).'.'.($imageFile->guessExtension() ?: 'jpg');

                try {
                    $imageFile->move($this->getParameter('event_images_dir'), $newName);
                    $event->setImagePath('uploads/events/'.$newName);
                } catch (FileException $e) {
                    $this->addFlash('error', "Erreur upload image.");
                }
            }

            $em->flush();
            $this->addFlash('success', 'Événement modifié avec succès.');
            return $this->redirectToRoute('admin_event_index');
        }

        return $this->render('BackOffice/admin/event/edit.html.twig', [
            'event' => $event,
            'form' => $form->createView(),
        ]);
    }

    #[Route('/admin/events/{id}', name: 'admin_event_delete', methods: ['POST'])]
    public function delete(SchoolEvent $event, Request $request, EntityManagerInterface $em): Response
    {
        $token = $request->request->get('_token');
        if (is_string($token) && $this->isCsrfTokenValid('delete_event_'.$event->getId(), $token)) {
            $em->remove($event);
            $em->flush();
            $this->addFlash('success', 'Événement supprimé.');
        }

        return $this->redirectToRoute('admin_event_index');
    }
}