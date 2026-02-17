<?php

namespace App\Controller;

use App\Entity\Commande;
use App\Form\FrontCommandeType;
use App\Repository\CommandeRepository;
use App\Repository\ProductRepository;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/shop')]
class CommandeFrontController extends AbstractController
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private ProductRepository $productRepository,
        private UserRepository $userRepository,
        private CommandeRepository $commandeRepository,
    ) {
    }

    #[Route('/{id}/commander', name: 'app_front_commande_new', requirements: ['id' => '\d+'], methods: ['GET', 'POST'])]
    public function new(Request $request, int $id): Response
    {
        $user = $this->getUser();
        $isParent = $user && $user instanceof \App\Entity\User && $user->getType() === 'parent';

        // Récupérer le produit
        $product = $this->productRepository->find($id);
        if (!$product || !$product->isAvailability()) {
            throw $this->createNotFoundException('Produit non trouvé ou indisponible.');
        }

        $commande = new Commande();
        $commande->setProduct($product);
        $commande->setDateCommande(new \DateTime());
        $commande->setQuantity(1);
        $commande->setTotalAmount((float) ($product->getPrice() * 1));

        if ($isParent) {
            $commande->setUser($user);
        }

        $parents = $this->userRepository->findParents();
        $form = $this->createForm(FrontCommandeType::class, $commande, [
            'parents' => $parents,
            'current_user_is_parent' => $isParent,
        ]);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            try {
                $quantity = $commande->getQuantity();
                $totalAmount = $product->getPrice() * $quantity;
                $commande->setTotalAmount((float) $totalAmount);
                if ($isParent) {
                    $commande->setUser($user);
                }
                $this->entityManager->persist($commande);
                $this->entityManager->flush();
                $this->addFlash('success', 'Votre commande a bien été enregistrée.');
                return $this->redirectToRoute('app_front_commande_confirm', ['id' => $commande->getId()], Response::HTTP_SEE_OTHER);
            } catch (\Exception $e) {
                $this->addFlash('error', 'Une erreur est survenue lors de l\'enregistrement de la commande.');
            }
        }

        return $this->render('FrontOffice/parent/commande/new.html.twig', [
            'product' => $product,
            'form' => $form->createView(),
            'initial_total' => $product->getPrice() * 1,
            'current_user_is_parent' => $isParent,
        ]);
    }

    #[Route('/commande/{id}/confirm', name: 'app_front_commande_confirm', requirements: ['id' => '\d+'], methods: ['GET'])]
    public function confirm(int $id): Response
    {
        $commande = $this->commandeRepository->find($id);
        if (!$commande) {
            throw $this->createNotFoundException('Commande non trouvée.');
        }

        return $this->render('FrontOffice/parent/commande/confirm.html.twig', [
            'commande' => $commande,
            'product' => $commande->getProduct(),
            'user' => $commande->getUser(),
        ]);
    }
}