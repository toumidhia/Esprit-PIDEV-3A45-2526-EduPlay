<?php

namespace App\Controller;

use App\Entity\EventResource;
use App\Entity\SchoolEvent;
use App\Service\AI\ChecklistGenerator;
use App\Service\AI\PlanningGenerator;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/admin/events/ai')]
class AIEventController extends AbstractController
{
    public function __construct(
        private EntityManagerInterface $em
    ) {}

    #[Route('/{id}/generate-checklist', name: 'admin_event_ai_checklist', methods: ['POST'])]
    public function generateChecklist(
        SchoolEvent $event, 
        ChecklistGenerator $checklistGenerator,
        Request $request
    ): Response {
        // Vérification du token CSRF
        if (!$this->isCsrfTokenValid('generate_ai_' . $event->getId(), $request->request->get('_token'))) {
            $this->addFlash('error', 'Token CSRF invalide.');
            return $this->redirectToRoute('admin_event_show', ['id' => $event->getId()]);
        }

        try {
            // Génération de la checklist
            $result = $checklistGenerator->generateChecklist($event);

            // Création de la ressource
            $resource = new EventResource();
            $resource->setType('CHECKLIST');
            $resource->setTitle($result['title']);
            $resource->setContext($result['content']);
            $resource->setEvent($event);
            $resource->setCreatedAt(new \DateTimeImmutable());

            $this->em->persist($resource);
            $this->em->flush();

            $this->addFlash('success', '✅ Checklist générée avec succès !');
        } catch (\Exception $e) {
            $this->addFlash('error', '❌ Erreur lors de la génération : ' . $e->getMessage());
        }

        return $this->redirectToRoute('admin_event_show', ['id' => $event->getId()]);
    }

    #[Route('/{id}/generate-planning', name: 'admin_event_ai_planning', methods: ['POST'])]
    public function generatePlanning(
        SchoolEvent $event, 
        PlanningGenerator $planningGenerator,
        Request $request
    ): Response {
        // Vérification du token CSRF
        if (!$this->isCsrfTokenValid('generate_ai_' . $event->getId(), $request->request->get('_token'))) {
            $this->addFlash('error', 'Token CSRF invalide.');
            return $this->redirectToRoute('admin_event_show', ['id' => $event->getId()]);
        }

        try {
            // Génération du planning
            $result = $planningGenerator->generatePlanning($event);

            // Création de la ressource
            $resource = new EventResource();
            $resource->setType('PLANNING');
            $resource->setTitle($result['title']);
            $resource->setContext($result['content']);
            $resource->setEvent($event);
            $resource->setCreatedAt(new \DateTimeImmutable());

            $this->em->persist($resource);
            $this->em->flush();

            $this->addFlash('success', '✅ Planning généré avec succès !');
        } catch (\Exception $e) {
            $this->addFlash('error', '❌ Erreur lors de la génération : ' . $e->getMessage());
        }

        return $this->redirectToRoute('admin_event_show', ['id' => $event->getId()]);
    }
}