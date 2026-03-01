<?php

namespace App\Controller;

use App\Repository\ProductRepository;
use App\Service\RecommendationProductService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/shop')]
#[IsGranted('ROLE_USER')]
class ShopController extends AbstractController
{
    public function __construct(
        private RecommendationProductService $RecommendationProductService,
    ) {
    }

    #[Route('', name: 'app_front_shop', methods: ['GET'])]
    public function index(Request $request, ProductRepository $productRepository): Response
    {
        if ($this->isGranted('ROLE_ENFANT')) {
            throw $this->createAccessDeniedException('Les enfants ne peuvent pas accéder à la boutique.');
        }
        $search = $request->query->get('search');
        $priceMin = $request->query->get('price_min') ? (float) $request->query->get('price_min') : null;
        $priceMax = $request->query->get('price_max') ? (float) $request->query->get('price_max') : null;
        $sortBy = $request->query->get('sort', 'id');
        $sortOrder = strtoupper($request->query->get('order', 'DESC')) === 'ASC' ? 'ASC' : 'DESC';

        $products = $productRepository->searchFilterSortFront($search, $priceMin, $priceMax, $sortBy, $sortOrder);
        $stats = $productRepository->getStatsFront();
        
        // Recommandations intelligentes
        $recommendations = $this->RecommendationProductService->getRecommendationsForUser(null, 4);

        return $this->render('FrontOffice/parent/shop/index.html.twig', [
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
        if ($this->isGranted('ROLE_ENFANT')) {
            throw $this->createAccessDeniedException('Les enfants ne peuvent pas accéder à la boutique.');
        }
        $product = $productRepository->find($id);
        if (!$product || !$product->isAvailability()) {
            throw $this->createNotFoundException('Produit non trouvé.');
        }

        // Recommandations : produits similaires et fréquemment achetés ensemble
        $similarProducts = $this->RecommendationProductService->getContentBasedRecommendations($product, 3);
        $frequentlyBoughtTogether = $this->RecommendationProductService->getFrequentlyBoughtTogether($product, 3);

        return $this->render('FrontOffice/parent/shop/show.html.twig', [
            'product' => $product,
            'similarProducts' => $similarProducts,
            'frequentlyBoughtTogether' => $frequentlyBoughtTogether,
        ]);
    }
}
