<?php

namespace App\Controller\Admin;

use App\Entity\EventResource;
use App\Form\EventResourceMainType;
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
    // ✅ Limite (PDF/LINK) par événement
    private const MAX_MAIN_RESOURCES = 10;

    #[Route('/admin/events/{id}/resources', name: 'admin_event_resource_index', methods: ['GET'])]
    public function index(SchoolEvent $event, Request $request, EntityManagerInterface $em): Response
    {
        $type = $request->query->get('type'); // PDF|LINK|CHECKLIST|PLANNING|...
        $sort = $request->query->get('sort', 'date'); // date|type
        $order = strtolower((string) $request->query->get('order', 'desc')) === 'asc' ? 'ASC' : 'DESC';

        $criteria = ['event' => $event];
        if ($type) {
            $criteria['type'] = $type;
        }

        // tri
        $orderBy = ['createdAt' => 'DESC'];
        if ($sort === 'type') {
            $orderBy = ['type' => $order, 'createdAt' => 'DESC'];
        } elseif ($sort === 'date') {
            $orderBy = ['createdAt' => $order];
        }

        $resources = $em->getRepository(EventResource::class)->findBy($criteria, $orderBy);

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
            $type = (string) $resource->getType();
            $pdf  = $form->get('pdfFile')->getData();

            // ✅ Max 10 ressources (PDF/LINK)
            if (in_array($type, ['PDF', 'LINK'], true)) {
                $countMain = (int) $em->createQueryBuilder()
                    ->select('COUNT(r.id)')
                    ->from(EventResource::class, 'r')
                    ->where('r.event = :event')
                    ->andWhere('r.type IN (:types)')
                    ->setParameter('event', $event)
                    ->setParameter('types', ['PDF', 'LINK'])
                    ->getQuery()
                    ->getSingleScalarResult();

                if ($countMain >= self::MAX_MAIN_RESOURCES) {
                    $form->addError(new FormError('Limite atteinte : maximum ' . self::MAX_MAIN_RESOURCES . ' ressources (PDF/LINK) par événement.'));
                }
            }

            // ✅ validations dépendantes du type
            if ($type === 'PDF' && !$pdf) {
                $form->addError(new FormError("Pour une ressource de type PDF, le fichier est obligatoire."));
            }

            if ($type === 'LINK') {
                $url = trim((string) $resource->getUrl());
                if ($url === '') {
                    $form->addError(new FormError("Pour une ressource de type LINK, l'URL est obligatoire."));
                } else {
                    $normalized = $this->normalizeUrl($url);
                    // doublon URL
                    $exists = $em->getRepository(EventResource::class)->findOneBy([
                        'event' => $event,
                        'type' => 'LINK',
                        'url' => $normalized,
                    ]);
                    if ($exists) {
                        $form->addError(new FormError("Cette URL existe déjà pour cet événement."));
                    }
                    // on stocke normalisée pour rendre la règle stricte
                    $resource->setUrl($normalized);
                }
            }

            // ✅ Auto create checklist/planning if filled (optionnel)
            $checklistText = trim((string) $form->get('checklistText')->getData());
            $planningText  = trim((string) $form->get('planningText')->getData());

            // ⚠️ ici on ne persiste pas encore, on le fera quand le form est valid
            // mais on peut déjà préparer les règles "un seul checklist/planning"
            if ($checklistText !== '') {
                $existingChecklist = $em->getRepository(EventResource::class)->findOneBy([
                    'event' => $event,
                    'type' => 'CHECKLIST'
                ]);
                if ($existingChecklist) {
                    $form->addError(new FormError("Checklist déjà existante pour cet événement (1 seule autorisée)."));
                }
            }

            if ($planningText !== '') {
                $existingPlanning = $em->getRepository(EventResource::class)->findOneBy([
                    'event' => $event,
                    'type' => 'PLANNING'
                ]);
                if ($existingPlanning) {
                    $form->addError(new FormError("Planning déjà existant pour cet événement (1 seul autorisé)."));
                }
            }
        }

        if ($form->isSubmitted() && $form->isValid()) {
            $type = (string) $resource->getType();
            $pdf  = $form->get('pdfFile')->getData();

            // ✅ Nettoyage : garder cohérence selon type
            if ($type === 'PDF') {
                $resource->setUrl(null);
            }
            if ($type === 'LINK') {
                $resource->setFilePath(null);
            }

            // ✅ Upload PDF + anti-doublon strict par nom (par event)
            if ($pdf) {
                $originalName = pathinfo($pdf->getClientOriginalName(), PATHINFO_FILENAME);
                $safe = (string) $slugger->slug($originalName);
                $ext = $pdf->guessExtension() ?: 'pdf';

                // nom stable => interdit doublons
                $newName = 'event' . $event->getId() . '-' . $safe . '.' . $ext;
                $relativePath = 'uploads/event-resources/' . $newName;

                // doublon DB
                $exists = $em->getRepository(EventResource::class)->findOneBy([
                    'event' => $event,
                    'type' => 'PDF',
                    'filePath' => $relativePath,
                ]);

                if ($exists) {
                    $form->addError(new FormError("Un PDF avec le même nom existe déjà pour cet événement."));
                    return $this->render('admin/event_resource/new.html.twig', [
                        'event' => $event,
                        'form' => $form->createView(),
                    ]);
                }

                try {
                    $pdf->move($this->getParameter('event_resources_dir'), $newName);
                    $resource->setFilePath($relativePath);
                } catch (FileException $e) {
                    $form->addError(new FormError("Erreur lors de l'upload du fichier PDF."));
                    return $this->render('admin/event_resource/new.html.twig', [
                        'event' => $event,
                        'form' => $form->createView(),
                    ]);
                }
            }

            $em->persist($resource);

            // ✅ Auto create checklist/planning (1 seul chacun)
            $checklist = trim((string) $form->get('checklistText')->getData());
            $planning  = trim((string) $form->get('planningText')->getData());

            if ($checklist !== '') {
                $r = new EventResource();
                $r->setEvent($event);
                $r->setCreatedAt(new \DateTimeImmutable());
                $r->setType('CHECKLIST');
                $r->setTitle('Checklist - ' . ($event->getTitle()));
                $r->setContext($checklist);
                $em->persist($r);
            }

            if ($planning !== '') {
                $r = new EventResource();
                $r->setEvent($event);
                $r->setCreatedAt(new \DateTimeImmutable());
                $r->setType('PLANNING');
                $r->setTitle('Planning - ' . ($event->getTitle()));
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

        $form = $this->createForm(EventResourceType::class, $resource);
        $form->handleRequest($request);

        if ($form->isSubmitted()) {
            $type = (string) $resource->getType();
            $pdf  = $form->get('pdfFile')->getData();

            // ✅ Max 10 ressources (PDF/LINK) en edit (on exclut la ressource courante)
            if (in_array($type, ['PDF', 'LINK'], true)) {
                $countMain = (int) $em->createQueryBuilder()
                    ->select('COUNT(r.id)')
                    ->from(EventResource::class, 'r')
                    ->where('r.event = :event')
                    ->andWhere('r.type IN (:types)')
                    ->andWhere('r.id != :current')
                    ->setParameter('event', $event)
                    ->setParameter('types', ['PDF', 'LINK'])
                    ->setParameter('current', $resource->getId())
                    ->getQuery()
                    ->getSingleScalarResult();

                if ($countMain >= self::MAX_MAIN_RESOURCES) {
                    $form->addError(new FormError('Limite atteinte : maximum ' . self::MAX_MAIN_RESOURCES . ' ressources (PDF/LINK) par événement.'));
                }
            }

            // LINK => url obligatoire + pas de doublon (hors current)
            if ($type === 'LINK') {
                $url = trim((string) $resource->getUrl());
                if ($url === '') {
                    $form->addError(new FormError("Pour une ressource de type LINK, l'URL est obligatoire."));
                } else {
                    $normalized = $this->normalizeUrl($url);
                    $qb = $em->createQueryBuilder()
                        ->select('COUNT(r.id)')
                        ->from(EventResource::class, 'r')
                        ->where('r.event = :event')
                        ->andWhere('r.type = :type')
                        ->andWhere('r.url = :url')
                        ->andWhere('r.id != :current')
                        ->setParameter('event', $event)
                        ->setParameter('type', 'LINK')
                        ->setParameter('url', $normalized)
                        ->setParameter('current', $resource->getId());

                    if ((int) $qb->getQuery()->getSingleScalarResult() > 0) {
                        $form->addError(new FormError("Cette URL existe déjà pour cet événement."));
                    }
                    $resource->setUrl($normalized);
                }
            }

            // PDF => si pas de nouveau fichier, OK si filePath existe
            if ($type === 'PDF' && !$pdf && !$resource->getFilePath()) {
                $form->addError(new FormError("Pour une ressource de type PDF, le fichier est obligatoire."));
            }
        }

        if ($form->isSubmitted() && $form->isValid()) {
            $type = (string) $resource->getType();
            $pdf  = $form->get('pdfFile')->getData();

            // ✅ Nettoyage cohérent
            if ($type === 'PDF') {
                $resource->setUrl(null);
            }
            if ($type === 'LINK') {
                $resource->setFilePath(null);
            }

            // ✅ upload nouveau PDF + anti doublon strict
            if ($pdf) {
                $originalName = pathinfo($pdf->getClientOriginalName(), PATHINFO_FILENAME);
                $safe = (string) $slugger->slug($originalName);
                $ext = $pdf->guessExtension() ?: 'pdf';

                $newName = 'event' . $event->getId() . '-' . $safe . '.' . $ext;
                $relativePath = 'uploads/event-resources/' . $newName;

                $qb = $em->createQueryBuilder()
                    ->select('COUNT(r.id)')
                    ->from(EventResource::class, 'r')
                    ->where('r.event = :event')
                    ->andWhere('r.type = :type')
                    ->andWhere('r.filePath = :fp')
                    ->andWhere('r.id != :current')
                    ->setParameter('event', $event)
                    ->setParameter('type', 'PDF')
                    ->setParameter('fp', $relativePath)
                    ->setParameter('current', $resource->getId());

                if ((int) $qb->getQuery()->getSingleScalarResult() > 0) {
                    $form->addError(new FormError("Un PDF avec le même nom existe déjà pour cet événement."));
                    return $this->render('admin/event_resource/edit.html.twig', [
                        'event' => $event,
                        'resource' => $resource,
                        'form' => $form->createView(),
                    ]);
                }

                try {
                    $pdf->move($this->getParameter('event_resources_dir'), $newName);
                    $resource->setFilePath($relativePath);
                } catch (FileException $e) {
                    $form->addError(new FormError("Erreur lors de l'upload du fichier PDF."));
                    return $this->render('admin/event_resource/edit.html.twig', [
                        'event' => $event,
                        'resource' => $resource,
                        'form' => $form->createView(),
                    ]);
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

    // ✅ Edition checklist/planning séparées (comme tu as déjà fait)
    #[Route('/admin/events/{eventId}/resources/{resourceId}/checklist/edit', name: 'admin_event_checklist_edit', methods: ['GET','POST'])]
    public function editChecklist(int $eventId, int $resourceId, Request $request, EntityManagerInterface $em): Response
    {
        $event = $em->getRepository(SchoolEvent::class)->find($eventId);
        if (!$event) throw $this->createNotFoundException('Event introuvable.');

        $resource = $em->getRepository(EventResource::class)->find($resourceId);
        if (!$resource || $resource->getEvent()?->getId() !== $event->getId() || $resource->getType() !== 'CHECKLIST') {
            throw $this->createNotFoundException('Checklist introuvable pour cet événement.');
        }

        $form = $this->createFormBuilder($resource)
            ->add('title', TextType::class, ['label' => 'Titre', 'required' => true])
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
    public function editPlanning(int $eventId, int $resourceId, Request $request, EntityManagerInterface $em): Response
    {
        $event = $em->getRepository(SchoolEvent::class)->find($eventId);
        if (!$event) throw $this->createNotFoundException('Event introuvable.');

        $resource = $em->getRepository(EventResource::class)->find($resourceId);
        if (!$resource || $resource->getEvent()?->getId() !== $event->getId() || $resource->getType() !== 'PLANNING') {
            throw $this->createNotFoundException('Planning introuvable pour cet événement.');
        }

        $form = $this->createFormBuilder($resource)
            ->add('title', TextType::class, ['label' => 'Titre', 'required' => true])
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

    private function normalizeUrl(string $url): string
    {
        $url = trim($url);
        // petit nettoyage : enlever espaces + uniformiser
        // (on peut ajouter https:// si absent, mais je le laisse simple)
        return $url;
    }
}
