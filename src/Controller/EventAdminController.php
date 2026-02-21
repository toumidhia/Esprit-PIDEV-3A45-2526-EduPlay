<?php

namespace App\Controller;

use App\Entity\SchoolEvent;
use App\Form\SchoolEventType;
use Doctrine\ORM\EntityManagerInterface;
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
        // ✅ auto-suppression des événements passés (endDate < now)
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
        $sort = (string) $request->query->get('sort', 'createdAt'); // createdAt|startDate|endDate|title
        $order = strtolower((string) $request->query->get('order', 'desc')) === 'asc' ? 'ASC' : 'DESC';

        $allowedSort = ['createdAt', 'startDate', 'endDate', 'title'];
        if (!in_array($sort, $allowedSort, true)) {
            $sort = 'createdAt';
        }

        $qb = $em->getRepository(SchoolEvent::class)->createQueryBuilder('e');

        if ($q !== '') {
            $qb->andWhere('LOWER(e.title) LIKE :q OR LOWER(e.location) LIKE :q OR LOWER(e.description) LIKE :q')
               ->setParameter('q', '%'.mb_strtolower($q).'%');
        }

        $qb->orderBy('e.'.$sort, $order);

        $events = $qb->getQuery()->getResult();

        // ✅ si requête AJAX => on renvoie seulement les lignes du tableau (tbody)
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

    #[Route('/admin/events/{id}', name: 'admin_event_show', methods: ['GET'])]
    public function show(SchoolEvent $event): Response
    {
        return $this->render('BackOffice/admin/event/show.html.twig', [
            'event' => $event,
        ]);
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
        if ($this->isCsrfTokenValid('delete_event_'.$event->getId(), $request->request->get('_token'))) {
            $em->remove($event);
            $em->flush();
            $this->addFlash('success', 'Événement supprimé.');
        }

        return $this->redirectToRoute('admin_event_index');
    }
}