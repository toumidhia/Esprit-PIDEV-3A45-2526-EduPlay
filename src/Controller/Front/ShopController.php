<?php

namespace App\Controller\Front;

use App\Repository\ProductRepository;
use App\Service\RecommendationService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/shop')]
class ShopController extends AbstractController
{
    public function __construct(
        private RecommendationService $recommendationService,
    ) {
    }

    #[Route('', name: 'app_front_shop', methods: ['GET'])]
    public function index(Request $request, ProductRepository $productRepository): Response
    {
        $search = $request->query->get('search');
        $priceMin = $request->query->get('price_min') ? (float) $request->query->get('price_min') : null;
        $priceMax = $request->query->get('price_max') ? (float) $request->query->get('price_max') : null;
        $sortBy = $request->query->get('sort', 'id');
        $sortOrder = strtoupper($request->query->get('order', 'DESC')) === 'ASC' ? 'ASC' : 'DESC';

        $products = $productRepository->searchFilterSortFront($search, $priceMin, $priceMax, $sortBy, $sortOrder);
        $stats = $productRepository->getStatsFront();
        
        // Recommandations intelligentes
        $recommendations = $this->recommendationService->getRecommendationsForUser(null, 4);

        return $this->render('front/shop/index.html.twig', [
            'products' => $products,
            'stats' => $stats,
            'recommendations' => $recommendations,
            'search' => $search,
            'price_min' => $request->query->get('price_min'),
            'price_max' => $request->query->get('price_max'),
            'sortBy' => $sortBy,
            'sortOrder' => $sortOrder,
        ]);
    }

    #[Route('/{id}', name: 'app_front_shop_show', requirements: ['id' => '\d+'], methods: ['GET'])]
    public function show(int $id, ProductRepository $productRepository): Response
    {
        $product = $productRepository->find($id);
        if (!$product || !$product->isAvailability()) {
            throw $this->createNotFoundException('Produit non trouvé.');
        }

        // Recommandations : produits similaires et fréquemment achetés ensemble
        $similarProducts = $this->recommendationService->getContentBasedRecommendations($product, 3);
        $frequentlyBoughtTogether = $this->recommendationService->getFrequentlyBoughtTogether($product, 3);

        return $this->render('front/shop/show.html.twig', [
            'product' => $product,
            'similarProducts' => $similarProducts,
            'frequentlyBoughtTogether' => $frequentlyBoughtTogether,
        ]);
    }
}
