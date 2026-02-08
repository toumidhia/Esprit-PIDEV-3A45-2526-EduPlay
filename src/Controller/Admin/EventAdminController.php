<?php

namespace App\Controller\Admin;

use App\Repository\SchoolEventRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

use App\Entity\SchoolEvent;
use App\Form\SchoolEventType;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\File\Exception\FileException;
use Symfony\Component\String\Slugger\SluggerInterface;

class EventAdminController extends AbstractController
{
    #[Route('/admin/events', name: 'admin_event_index', methods: ['GET'])]
    public function index(SchoolEventRepository $schoolEventRepository): Response
    {
        //$this->denyAccessUnlessGranted('ROLE_ADMIN');

        $events = $schoolEventRepository->findBy([], ['createdAt' => 'DESC']);

        return $this->render('admin/event/index.html.twig', [
            'events' => $events,
        ]);
    }

    #[Route('/admin/events/stats', name: 'admin_event_stats', methods: ['GET'])]
    public function stats(): Response
    {
        //$this->denyAccessUnlessGranted('ROLE_ADMIN');

        return $this->render('admin/event/stats.html.twig');
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
            $newName = $safe.'-'.uniqid().'.'.$imageFile->guessExtension();

            try {
                $imageFile->move($this->getParameter('event_images_dir'), $newName);
                $event->setImagePath('uploads/events/'.$newName);
            } catch (FileException $e) {
                $this->addFlash('success', 'Erreur upload image.');
            }
        }

        $em->persist($event);
        $em->flush();

        $this->addFlash('success', 'Événement créé avec succès.');
        return $this->redirectToRoute('admin_event_index');
        }

        return $this->render('admin/event/new.html.twig', [
         'form' => $form->createView(),
        ]);
    }

    #[Route('/admin/events/{id}', name: 'admin_event_show', methods: ['GET'])]
    public function show(SchoolEvent $event): Response
    {
        return $this->render('admin/event/show.html.twig', [
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
                $newName = $safe.'-'.uniqid().'.'.$imageFile->guessExtension();

            try {
                $imageFile->move($this->getParameter('event_images_dir'), $newName);
                $event->setImagePath('uploads/events/'.$newName);
            } catch (FileException $e) {}
            }

        $em->flush();
        $this->addFlash('success', 'Événement modifié avec succès.');
        return $this->redirectToRoute('admin_event_index');
        }

        return $this->render('admin/event/edit.html.twig', [
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
