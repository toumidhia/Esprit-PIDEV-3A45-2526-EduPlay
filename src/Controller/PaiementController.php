<?php

namespace App\Controller;

use App\Entity\Commande;
use App\Entity\User; // Import crucial pour corriger l'erreur de PHPDoc
use App\Service\StripePaymentService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\RequestStack; // Pour remplacer le $this->get()
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[IsGranted('ROLE_USER')]
class PaiementController extends AbstractController
{
    #[Route('/paiement', name: 'app_paiement_index')]
    public function index(): Response
    {
        if ($this->isGranted('ROLE_ENFANT')) {
            throw $this->createAccessDeniedException('Les enfants ne peuvent pas effectuer de paiement.');
        }
        return $this->json(['message' => 'Access granted to payment section.']);
    }

    #[Route('/paiement/{id}', name: 'app_paiement', methods: ['POST'])]
    public function processPaiement(
        Commande $commande, 
        StripePaymentService $stripePaymentService,
        RequestStack $requestStack // Injection directe recommandée
    ): Response {
        if ($this->isGranted('ROLE_ENFANT')) {
            throw $this->createAccessDeniedException('Les enfants ne peuvent pas effectuer de paiement.');
        }

        /** @var User $user */
        $user = $this->getUser();
        if ($commande->getUser() !== $user) {
            throw $this->createAccessDeniedException('Vous ne pouvez pas accéder à cette commande.');
        }

        // Correction du $this->get() par l'usage du RequestStack injecté
        $currentRequest = $requestStack->getCurrentRequest();
        if (!$currentRequest) {
            return $this->json(['error' => 'No request found'], Response::HTTP_BAD_REQUEST);
        }

        $requestContent = json_decode($currentRequest->getContent(), true);
        if (!$requestContent) {
            return $this->json(['error' => 'Invalid request data'], Response::HTTP_BAD_REQUEST);
        }

        $paymentIntent = $stripePaymentService->createPaymentIntent(
            $commande->getTotalAmount(),
            'usd',
            ['commande_id' => $commande->getId()]
        );

        return $this->json([
            'clientSecret' => $paymentIntent->client_secret,
        ]);
    }

    #[Route('/paiement/form/{id}', name: 'app_paiement_form', methods: ['GET'])]
    public function paiementForm(Commande $commande): Response
    {
        /** @var User $user */
        $user = $this->getUser();
        if ($commande->getUser() !== $user) {
            throw $this->createAccessDeniedException('Vous ne pouvez pas accéder à cette commande.');
        }

        return $this->render('FrontOffice/parent/paiement.html.twig', [
            'commande' => $commande,
            'stripe_public_key' => $_ENV['STRIPE_PUBLIC_KEY'] ?? '',
        ]);
    }
}