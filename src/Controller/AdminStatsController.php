<?php

namespace App\Controller;

use App\Entity\EventRegistration;
use App\Entity\EventResource;
use App\Entity\SchoolEvent;
use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class AdminStatsController extends AbstractController
{
    #[Route('/admin/stats', name: 'admin_stats', methods: ['GET'])]
    public function index(EntityManagerInterface $em): Response
    {
        /** @var User|null $user */
        $user = $this->getUser();

        // Protection : seuls les admins peuvent accéder
        if (!$user || $user->getType() !== 'admin') {
            return $this->redirectToRoute('app_login');
        }

        $now = new \DateTimeImmutable();

        $totalEvents = (int) $em->createQueryBuilder()
            ->select('COUNT(e.id)')
            ->from(SchoolEvent::class, 'e')
            ->getQuery()->getSingleScalarResult();

        $upcomingEvents = (int) $em->createQueryBuilder()
            ->select('COUNT(e.id)')
            ->from(SchoolEvent::class, 'e')
            ->where('e.startDate >= :now')
            ->setParameter('now', $now)
            ->getQuery()->getSingleScalarResult();

        $totalRegistrations = (int) $em->createQueryBuilder()
            ->select('COUNT(r.id)')
            ->from(EventRegistration::class, 'r')
            ->getQuery()->getSingleScalarResult();

        // Dernières inscriptions
        $latestRegistrations = $em->getRepository(EventRegistration::class)
            ->findBy([], ['registeredAt' => 'DESC'], 8);

        // Inscriptions par événement
        $allRegistrations = $em->getRepository(EventRegistration::class)
            ->findBy([], ['registeredAt' => 'DESC']);

        $registrationsByEvent = [];
        foreach ($allRegistrations as $reg) {
            $title = $reg->getEvent()?->getTitle() ?? 'Événement supprimé';
            $registrationsByEvent[$title] = ($registrationsByEvent[$title] ?? 0) + 1;
        }
        arsort($registrationsByEvent);

        // Inscriptions par mois (12 derniers mois)
        $months = [];
        $cursor = (new \DateTimeImmutable('first day of this month'))->modify('-11 months');
        for ($i = 0; $i < 12; $i++) {
            $key = $cursor->format('Y-m');
            $months[$key] = 0;
            $cursor = $cursor->modify('+1 month');
        }

        foreach ($allRegistrations as $reg) {
            $dt = $reg->getRegisteredAt();
            if (!$dt) continue;
            $key = $dt->format('Y-m');
            if (array_key_exists($key, $months)) {
                $months[$key]++;
            }
        }

        // Ressources par type
        $allResources = $em->getRepository(EventResource::class)->findAll();
        $resourcesByType = [];
        foreach ($allResources as $res) {
            $type = (string) $res->getType();
            $resourcesByType[$type] = ($resourcesByType[$type] ?? 0) + 1;
        }
        ksort($resourcesByType);

        return $this->render('BackOffice/admin/stats/index.html.twig', [
            'totalEvents' => $totalEvents,
            'upcomingEvents' => $upcomingEvents,
            'totalRegistrations' => $totalRegistrations,
            'latestRegistrations' => $latestRegistrations,
            'registrationsByEvent' => $registrationsByEvent,
            'months' => $months,
            'resourcesByType' => $resourcesByType,
        ]);
    }
}