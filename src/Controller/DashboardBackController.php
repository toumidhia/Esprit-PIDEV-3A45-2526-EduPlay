<?php

namespace App\Controller;

use App\Repository\CommandeRepository;
use App\Repository\ProductRepository;
use App\AnomalyBundle\Service\AnomalyDetectionService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/admin')]
class DashboardBackController extends AbstractController
{
    public function __construct(
        private AnomalyDetectionService $anomalyDetectionService,
    ) {
    }

    #[Route('', name: 'app_back_dashboard', methods: ['GET'])]
    public function index(ProductRepository $productRepository, CommandeRepository $commandeRepository): Response
    {
        $anomalies = $this->anomalyDetectionService->detectAllAnomalies();
        $anomaliesCount = count($anomalies['commandes']) + count($anomalies['produits']) + count($anomalies['utilisateurs']);

        $totalRevenue = $commandeRepository->totalAmountSum();
        $revenue30 = $commandeRepository->totalAmountSumLastDays(30);
        $commandesCount = $commandeRepository->countCommandes();
        $commandes30 = $commandeRepository->countCommandesLastDays(30);

        return $this->render('back/dashboard.html.twig', [
            'products_count' => $productRepository->count([]),
            'commandes_count' => $commandesCount,
            'anomalies_count' => $anomaliesCount,
            'revenue_total' => $totalRevenue,
            'revenue_30days' => $revenue30,
            'commandes_30days' => $commandes30,
        ]);
    }
}
