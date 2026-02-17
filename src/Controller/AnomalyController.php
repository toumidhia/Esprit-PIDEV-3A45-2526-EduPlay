<?php

namespace App\Controller;

use App\Service\AnomalyDetectionService;
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
        $anomalies = $this->anomalyDetectionService->detectAllAnomalies();

        return $this->render('BackOffice/admin/anomalies/index.html.twig', [
            'anomalies' => $anomalies,
        ]);
    }
}
