<?php

namespace App\Controller;

use App\Repository\CommandeRepository;
use App\Repository\ProductRepository;
use App\Repository\UserRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class HomeController extends AbstractController
{
    public function __construct(
        private ProductRepository $productRepository,
        private CommandeRepository $commandeRepository,
        private UserRepository $userRepository,
    ) {
    }

    #[Route('/', name: 'app_home')]
    public function index(): Response
    {
        $stats = [
            'products_total' => $this->productRepository->count([]),
            'products_available' => $this->productRepository->countAvailable(),
            'commandes_total' => $this->commandeRepository->countCommandes(),
            'commandes_amount' => $this->commandeRepository->totalAmountSum(),
            'users_total' => $this->userRepository->count([]),
        ];

        return $this->render('home/index.html.twig', [
            'titre' => 'Bienvenue sur EduPlay',
            'description' => 'La plateforme éducative pour les enfants, les parents et les enseignants.',
            'stats' => $stats,
        ]);
    }
}