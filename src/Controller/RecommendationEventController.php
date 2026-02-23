<?php
// src/Controller/Parent/RecommendationController.php

namespace App\Controller;

use App\Service\RecommendationEventService;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class RecommendationEventController extends AbstractController
{
    #[Route('/parent/recommendations', name: 'parent_recommendations', methods: ['GET'])]
    public function index(RecommendationEventService $recommendationEventService, EntityManagerInterface $em): Response
    {
        $this->denyAccessUnlessGranted('IS_AUTHENTICATED_FULLY');
        
        $parent = $this->getUser();
        
        $recommendations = $recommendationEventService->getRecommendationsForParent($parent, 6);
        
        return $this->render('FrontOffice/Parent/event/recommendations/index.html.twig', [
            'recommendations' => $recommendations,
        ]);
    }
}