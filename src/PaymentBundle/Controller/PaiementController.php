<?php

namespace App\PaymentBundle\Controller;

use App\Entity\Commande;
use App\PaymentBundle\Service\StripePaymentService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class PaiementController extends AbstractController
{
    #[Route('/paiement/{id}', name: 'app_paiement', methods: ['POST'])]
    public function processPaiement(Commande $commande, StripePaymentService $stripePaymentService): Response
    {
        // Ensure the user is the owner of the commande
        /** @var User $user */
        $user = $this->getUser();
        if ($commande->getUser() !== $user) {
            throw $this->createAccessDeniedException('Vous ne pouvez pas accéder à cette commande.');
        }

        // Ensure the request is properly handled
        $requestContent = json_decode($this->get('request_stack')->getCurrentRequest()->getContent(), true);
        if (!$requestContent) {
            return $this->json(['error' => 'Invalid request data'], Response::HTTP_BAD_REQUEST);
        }

        // Create a payment intent
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
            'stripe_public_key' => $_ENV['STRIPE_PUBLIC_KEY'],
        ]);
    }
}