<?php

namespace App\Controller\Front;

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
class CommandeController extends AbstractController
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
        // Récupérer le produit
        $product = $this->productRepository->find($id);
        if (!$product || !$product->isAvailability()) {
            throw $this->createNotFoundException('Produit non trouvé ou indisponible.');
        }

        // Créer une nouvelle commande
        $commande = new Commande();
        $commande->setIdProduct($product); // id_product_id
        $commande->setDateCommande(new \DateTime()); // date_commande
        $commande->setQuantity(1); // quantity
        
        // Calcul du montant total
        $totalAmount = $product->getPrice() * $commande->getQuantity();
        $commande->setTotalAmount((float) $totalAmount); // total_amount

        // Récupérer les utilisateurs (parents)
        $parents = $this->userRepository->findParents();

        $form = $this->createForm(FrontCommandeType::class, $commande, [
            'parents' => $parents,
        ]);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            try {
                // Calculer le montant total final
                $quantity = $commande->getQuantity();
                $totalAmount = $product->getPrice() * $quantity;
                $commande->setTotalAmount((float) $totalAmount);
                
                // id_user_id est défini dans le formulaire via le champ "user"
                // date_commande est déjà défini
                
                $this->entityManager->persist($commande);
                $this->entityManager->flush();
                
                $this->addFlash('success', 'Votre commande a bien été enregistrée.');
                
                return $this->redirectToRoute('app_front_commande_confirm', [
                    'id' => $commande->getId()
                ], Response::HTTP_SEE_OTHER);
                
            } catch (\Exception $e) {
                $this->addFlash('error', 'Une erreur est survenue lors de l\'enregistrement de la commande.');
                // Log l'erreur si nécessaire
                // $this->logger->error('Erreur création commande: ' . $e->getMessage());
            }
        }

        return $this->render('front/commande/new.html.twig', [
            'product' => $product,
            'form' => $form->createView(),
            'initial_total' => $product->getPrice() * 1,
        ]);
    }

    #[Route('/commande/{id}/confirm', name: 'app_front_commande_confirm', requirements: ['id' => '\d+'], methods: ['GET'])]
    public function confirm(int $id): Response
    {
        $commande = $this->commandeRepository->find($id);
        if (!$commande) {
            throw $this->createNotFoundException('Commande non trouvée.');
        }

        return $this->render('front/commande/confirm.html.twig', [
            'commande' => $commande,
            'product' => $commande->getIdProduct(),
            'user' => $commande->getIdUser(),
        ]);
    }
}