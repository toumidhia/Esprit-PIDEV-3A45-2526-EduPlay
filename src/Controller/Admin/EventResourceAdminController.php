<?php

namespace App\Controller\Admin;

use App\Entity\EventResource;
use App\Entity\SchoolEvent;
use App\Form\EventResourceType;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Form\FormError;
use Symfony\Component\HttpFoundation\File\Exception\FileException;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\String\Slugger\SluggerInterface;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;

class EventResourceAdminController extends AbstractController
{
    #[Route('/admin/events/{id}/resources', name: 'admin_event_resource_index', methods: ['GET'])]
    public function index(SchoolEvent $event, EntityManagerInterface $em): Response
    {
        $resources = $em->getRepository(EventResource::class)->findBy(
            ['event' => $event],
            ['createdAt' => 'DESC']
        );

        return $this->render('admin/event_resource/index.html.twig', [
            'event' => $event,
            'resources' => $resources,
        ]);
    }

    #[Route('/admin/events/{id}/resources/new', name: 'admin_event_resource_new', methods: ['GET','POST'])]
    public function new(
        SchoolEvent $event,
        Request $request,
        EntityManagerInterface $em,
        SluggerInterface $slugger
    ): Response {
        $resource = new EventResource();
        $resource->setEvent($event);
        $resource->setCreatedAt(new \DateTimeImmutable());

        $form = $this->createForm(EventResourceType::class, $resource);
        $form->handleRequest($request);

        if ($form->isSubmitted()) {
            $type = $resource->getType();
            $pdf  = $form->get('pdfFile')->getData();

            if ($type === 'PDF' && !$pdf) {
                $form->addError(new FormError("Pour une ressource de type PDF, le fichier est obligatoire."));
            }

            if ($type === 'LINK' && !$resource->getUrl()) {
                $form->addError(new FormError("Pour une ressource de type LINK, l'URL est obligatoire."));
            }
        }

        if ($form->isSubmitted() && $form->isValid()) {
            $pdf = $form->get('pdfFile')->getData();

            if ($pdf) {
                $original = pathinfo($pdf->getClientOriginalName(), PATHINFO_FILENAME);
                $safe = $slugger->slug($original);
                $newName = $safe . '-' . uniqid('', true) . '.' . $pdf->guessExtension();

                try {
                    $pdf->move($this->getParameter('event_resources_dir'), $newName);
                    $resource->setFilePath('uploads/event-resources/' . $newName);
                } catch (FileException $e) {
                    $form->addError(new FormError("Erreur lors de l'upload du fichier PDF."));
                    return $this->render('admin/event_resource/new.html.twig', [
                        'event' => $event,
                        'form' => $form->createView(),
                    ]);
                }
            }

            $em->persist($resource);

            // ✅ Auto create checklist/planning if filled (always optional)
            $checklist = trim((string) $form->get('checklistText')->getData());
            $planning  = trim((string) $form->get('planningText')->getData());

            if ($checklist !== '') {
                $r = new EventResource();
                $r->setEvent($event);
                $r->setCreatedAt(new \DateTimeImmutable());
                $r->setType('CHECKLIST');
                $r->setTitle('Checklist - ' . ($resource->getTitle() ?: $event->getTitle()));
                $r->setContext($checklist);
                $em->persist($r);
            }

            if ($planning !== '') {
                $r = new EventResource();
                $r->setEvent($event);
                $r->setCreatedAt(new \DateTimeImmutable());
                $r->setType('PLANNING');
                $r->setTitle('Planning - ' . ($resource->getTitle() ?: $event->getTitle()));
                $r->setContext($planning);
                $em->persist($r);
            }

            $em->flush();

            $this->addFlash('success', 'Ressource ajoutée.');
            return $this->redirectToRoute('admin_event_resource_index', ['id' => $event->getId()]);
        }

        return $this->render('admin/event_resource/new.html.twig', [
            'event' => $event,
            'form' => $form->createView(),
        ]);
    }

    #[Route('/admin/events/{eventId}/resources/{resourceId}/edit', name: 'admin_event_resource_edit', methods: ['GET','POST'])]
    public function edit(
        int $eventId,
        int $resourceId,
        Request $request,
        EntityManagerInterface $em,
        SluggerInterface $slugger
    ): Response {
        $event = $em->getRepository(SchoolEvent::class)->find($eventId);
        if (!$event) {
            throw $this->createNotFoundException('Event introuvable.');
        }

        $resource = $em->getRepository(EventResource::class)->find($resourceId);
        if (!$resource || $resource->getEvent()?->getId() !== $event->getId()) {
            throw $this->createNotFoundException('Ressource introuvable pour cet événement.');
        }

        // ✅ On récupère la checklist/planning existants de cet EVENT (les plus récents)
        $checklistExisting = $em->getRepository(EventResource::class)->findOneBy(
            ['event' => $event, 'type' => 'CHECKLIST'],
            ['createdAt' => 'DESC']
        );
        $planningExisting = $em->getRepository(EventResource::class)->findOneBy(
            ['event' => $event, 'type' => 'PLANNING'],
            ['createdAt' => 'DESC']
        );

        $form = $this->createForm(EventResourceType::class, $resource, [
            'checklist_data' => $checklistExisting?->getContext() ?? '',
            'planning_data' => $planningExisting?->getContext() ?? '',
        ]);

        $form->handleRequest($request);

        if ($form->isSubmitted()) {
            $type = $resource->getType();
            $pdf  = $form->get('pdfFile')->getData();

            // En edit: si type PDF et aucun fichier uploadé, OK si filePath existe déjà
            if ($type === 'PDF' && !$pdf && !$resource->getFilePath()) {
                $form->addError(new FormError("Pour une ressource de type PDF, le fichier est obligatoire."));
            }

            if ($type === 'LINK' && !$resource->getUrl()) {
                $form->addError(new FormError("Pour une ressource de type LINK, l'URL est obligatoire."));
            }
        }

        if ($form->isSubmitted() && $form->isValid()) {
            // ✅ Upload si un nouveau PDF est donné
            $pdf = $form->get('pdfFile')->getData();

            if ($pdf) {
                $original = pathinfo($pdf->getClientOriginalName(), PATHINFO_FILENAME);
                $safe = $slugger->slug($original);
                $newName = $safe . '-' . uniqid('', true) . '.' . $pdf->guessExtension();

                try {
                    $pdf->move($this->getParameter('event_resources_dir'), $newName);
                    $resource->setFilePath('uploads/event-resources/' . $newName);
                } catch (FileException $e) {
                    $form->addError(new FormError("Erreur lors de l'upload du fichier PDF."));
                    return $this->render('admin/event_resource/edit.html.twig', [
                        'event' => $event,
                        'resource' => $resource,
                        'form' => $form->createView(),
                    ]);
                }
            }

            // ✅ Checklist/Planning (update/create/delete)
            $checklistText = trim((string) $form->get('checklistText')->getData());
            $planningText  = trim((string) $form->get('planningText')->getData());

            // CHECKLIST
            if ($checklistText !== '') {
                if (!$checklistExisting) {
                    $checklistExisting = new EventResource();
                    $checklistExisting->setEvent($event);
                    $checklistExisting->setCreatedAt(new \DateTimeImmutable());
                    $checklistExisting->setType('CHECKLIST');
                    $em->persist($checklistExisting);
                }
                $checklistExisting->setTitle('Checklist - ' . ($event->getTitle()));
                $checklistExisting->setContext($checklistText);
            } else {
                // si champ vide => supprimer l'existant (logique)
                if ($checklistExisting) {
                    $em->remove($checklistExisting);
                }
            }

            // PLANNING
            if ($planningText !== '') {
                if (!$planningExisting) {
                    $planningExisting = new EventResource();
                    $planningExisting->setEvent($event);
                    $planningExisting->setCreatedAt(new \DateTimeImmutable());
                    $planningExisting->setType('PLANNING');
                    $em->persist($planningExisting);
                }
                $planningExisting->setTitle('Planning - ' . ($event->getTitle()));
                $planningExisting->setContext($planningText);
            } else {
                if ($planningExisting) {
                    $em->remove($planningExisting);
                }
            }

            $em->flush();

            $this->addFlash('success', 'Ressource modifiée.');
            return $this->redirectToRoute('admin_event_resource_index', ['id' => $event->getId()]);
        }

        return $this->render('admin/event_resource/edit.html.twig', [
            'event' => $event,
            'resource' => $resource,
            'form' => $form->createView(),
        ]);
        }


    #[Route('/admin/events/{eventId}/resources/{resourceId}/delete', name: 'admin_event_resource_delete', methods: ['POST'])]
    public function delete(
        int $eventId,
        int $resourceId,
        Request $request,
        EntityManagerInterface $em
    ): Response {
        $event = $em->getRepository(SchoolEvent::class)->find($eventId);
        if (!$event) {
            throw $this->createNotFoundException('Event introuvable.');
        }

        $resource = $em->getRepository(EventResource::class)->find($resourceId);
        if (!$resource || $resource->getEvent()?->getId() !== $event->getId()) {
            throw $this->createNotFoundException('Ressource introuvable pour cet événement.');
        }

        if ($this->isCsrfTokenValid('delete_resource_' . $resource->getId(), $request->request->get('_token'))) {
            $em->remove($resource);
            $em->flush();
            $this->addFlash('success', 'Ressource supprimée.');
        }

        return $this->redirectToRoute('admin_event_resource_index', ['id' => $event->getId()]);
    }



    #[Route('/admin/events/{eventId}/resources/{resourceId}/checklist/edit', name: 'admin_event_checklist_edit', methods: ['GET','POST'])]
    public function editChecklist(
        int $eventId,
        int $resourceId,
        Request $request,
        EntityManagerInterface $em
    ): Response {
        $event = $em->getRepository(SchoolEvent::class)->find($eventId);
        if (!$event) {
            throw $this->createNotFoundException('Event introuvable.');
        }

        $resource = $em->getRepository(EventResource::class)->find($resourceId);
        if (!$resource || $resource->getEvent()?->getId() !== $event->getId() || $resource->getType() !== 'CHECKLIST') {
            throw $this->createNotFoundException('Checklist introuvable pour cet événement.');
        }

        $form = $this->createFormBuilder($resource)
            ->add('title', TextType::class, [
                'label' => 'Titre',
                'required' => true,
            ])
            ->add('context', TextareaType::class, [
                'label' => 'Checklist',
                'required' => true,
                'attr' => ['rows' => 10, 'placeholder' => "- Autorisation signée\n- Tenue de sport\n- Gourde"],
            ])
            ->getForm();

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $em->flush();
            $this->addFlash('success', 'Checklist modifiée.');
            return $this->redirectToRoute('admin_event_resource_index', ['id' => $event->getId()]);
        }

        return $this->render('admin/event_resource/edit_checklist.html.twig', [
            'event' => $event,
            'resource' => $resource,
            'form' => $form->createView(),
        ]);
    }

    #[Route('/admin/events/{eventId}/resources/{resourceId}/planning/edit', name: 'admin_event_planning_edit', methods: ['GET','POST'])]
    public function editPlanning(
        int $eventId,
        int $resourceId,
        Request $request,
        EntityManagerInterface $em
    ): Response {
        $event = $em->getRepository(SchoolEvent::class)->find($eventId);
        if (!$event) {
            throw $this->createNotFoundException('Event introuvable.');
        }

        $resource = $em->getRepository(EventResource::class)->find($resourceId);
        if (!$resource || $resource->getEvent()?->getId() !== $event->getId() || $resource->getType() !== 'PLANNING') {
            throw $this->createNotFoundException('Planning introuvable pour cet événement.');
        }

        $form = $this->createFormBuilder($resource)
            ->add('title', TextType::class, [
                'label' => 'Titre',
                'required' => true,
            ])
            ->add('context', TextareaType::class, [
                'label' => 'Planning',
                'required' => true,
                'attr' => ['rows' => 10, 'placeholder' => "08:30 - Accueil\n09:00 - Atelier 1\n10:30 - Pause\n11:00 - Atelier 2\n12:30 - Fin"],
            ])
            ->getForm();

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $em->flush();
            $this->addFlash('success', 'Planning modifié.');
            return $this->redirectToRoute('admin_event_resource_index', ['id' => $event->getId()]);
        }

        return $this->render('admin/event_resource/edit_planning.html.twig', [
            'event' => $event,
            'resource' => $resource,
            'form' => $form->createView(),
        ]);
    }


}