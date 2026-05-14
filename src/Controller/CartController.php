<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use Symfony\Component\Routing\Annotation\Route;
use App\Repository\ProductRepository;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/cart')]
#[IsGranted('ROLE_USER')]
class CartController extends AbstractController
{
    #[Route('/add/{id}', name: 'app_front_cart_add', methods: ['POST'])]
    public function add(int $id, Request $request, SessionInterface $session, ProductRepository $productRepository): Response
    {
        // CSRF check
        $token = $request->request->get('_token');
        if (!$this->isCsrfTokenValid('add-to-cart', $token)) {
            $this->addFlash('error', 'Jeton CSRF invalide.');
            return $this->redirectToRoute('app_front_shop_show', ['id' => $id]);
        }

        $quantity = max(1, (int) $request->request->get('quantity', 1));

        // verify product exists
        $product = $productRepository->find($id);
        if (!$product) {
            $this->addFlash('error', 'Produit introuvable.');
            return $this->redirectToRoute('app_front_shop');
        }

        $cart = $session->get('cart', []);
        if (!isset($cart[$id])) {
            $cart[$id] = 0;
        }
        $cart[$id] += $quantity;
        $session->set('cart', $cart);

        $this->addFlash('success', sprintf('« %s » ajouté au panier (%d).', $product->getName(), $quantity));

        return $this->redirectToRoute('app_front_shop_show', ['id' => $id]);
    }

    #[Route('', name: 'app_front_cart_index', methods: ['GET'])]
    public function index(SessionInterface $session, ProductRepository $productRepository): Response
    {
        $cart = $session->get('cart', []);
        $items = [];
        $total = 0.0;
        foreach ($cart as $productId => $qty) {
            $product = $productRepository->find($productId);
            if (!$product) {
                continue;
            }
            $lineTotal = $product->getPrice() * $qty;
            $items[] = ['product' => $product, 'quantity' => $qty, 'lineTotal' => $lineTotal];
            $total += $lineTotal;
        }

        return $this->render('FrontOffice/parent/cart/index.html.twig', [
            'items' => $items,
            'total' => $total,
        ]);
    }
}
