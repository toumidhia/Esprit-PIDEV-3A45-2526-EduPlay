<?php

namespace App\AnomalyBundle\Controller;

use App\AnomalyBundle\Service\AnomalyDetectionService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/admin/anomalies')]
class AnomalyController extends AbstractController
{
    public function __construct(
        private AnomalyDetectionService $anomalyDetectionService,
    ) {
    }

    #[Route('', name: 'app_back_anomalies', methods: ['GET'])]
    public function index(): Response
    {
        $suspiciousOrders = $this->anomalyDetectionService->detectSuspiciousOrders();
        
        // Build summary stats
        $summary = [
            'total_suspicious' => count($suspiciousOrders),
            'high_severity' => count(array_filter($suspiciousOrders, fn($o) => $o['max_severity'] === 'high')),
            'medium_severity' => count(array_filter($suspiciousOrders, fn($o) => $o['max_severity'] === 'medium')),
            'low_severity' => count(array_filter($suspiciousOrders, fn($o) => $o['max_severity'] === 'low')),
            'by_type' => []
        ];
        
        // Count by flag type
        $flagCounts = [];
        foreach ($suspiciousOrders as $order) {
            foreach ($order['flags'] as $flag) {
                $type = $flag['type'];
                $flagCounts[$type] = ($flagCounts[$type] ?? 0) + 1;
            }
        }
        $summary['by_type'] = $flagCounts;

        return $this->render('BackOffice/admin/anomalies/index.html.twig', [
            'anomalies' => [
                'suspicious_orders' => $suspiciousOrders,
                'summary' => $summary,
            ],
        ]);
    }
}
