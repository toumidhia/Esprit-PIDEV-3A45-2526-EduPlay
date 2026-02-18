<?php

namespace App\Controller;

use App\Entity\User;
use App\Entity\Commande;
use App\Form\EnfantType;
use App\Form\FrontCommandeType;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use App\Service\StripePaymentService;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

#[Route('/parent')]
#[IsGranted('ROLE_PARENT')]
class ParentController extends AbstractController
{
    #[Route('/dashboard', name: 'app_parent_dashboard')]
    public function mesEnfants(UserRepository $userRepository): Response
    {
        /** @var User $parent */
        $parent = $this->getUser();

        // Check if user is logged in and is a parent
        if (!$parent) {
            $this->addFlash('error', 'Vous devez être connecté pour accéder à cette page.');
            return $this->redirectToRoute('app_login');
        }

        return $this->render('FrontOffice/parent/dashboard.html.twig', [
            'parent' => $parent,
            'enfants' => $enfants = $userRepository->findBy(['parent' => $parent]),
        ]);
    }

    #[Route('/enfant/new', name: 'app_parent_enfant_new')]
    public function newEnfant(
        Request $request,
        UserPasswordHasherInterface $passwordHasher,
        EntityManagerInterface $entityManager
    ): Response {
        /** @var User $parent */
        $parent = $this->getUser();

        $enfant = new User();
        $form = $this->createForm(EnfantType::class, $enfant);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            // Définir le type enfant
            $enfant->setType('kid');

            // Hasher le mot de passe
            $hashedPassword = $passwordHasher->hashPassword(
                $enfant,
                $form->get('password')->getData()
            );
            $enfant->setPassword($hashedPassword);

            // Associer l'enfant au parent
            $enfant->setParent($parent);

            // Set default role
            $enfant->setRoles(['ROLE_KID']);

            // Sauvegarder
            $entityManager->persist($enfant);
            $entityManager->flush();

            $this->addFlash('success', 'Le compte de ' . $enfant->getFullName() . ' a été créé avec succès ! Identifiant: ' . $enfant->getUsername());

            return $this->redirectToRoute('app_parent_dashboard');
        }

        return $this->render('FrontOffice/parent/enfant_new.html.twig', [
            'form' => $form->createView(),
        ]);
    }

    #[Route('/enfant/{id}/delete', name: 'app_parent_enfant_delete', methods: ['POST'])]
    public function deleteEnfant(
        User $enfant,
        Request $request,
        EntityManagerInterface $entityManager
    ): Response {
        /** @var User $parent */
        $parent = $this->getUser();

        // Vérifier que l'enfant appartient bien au parent connecté
        if ($enfant->getParent() !== $parent) {
            throw $this->createAccessDeniedException('Vous ne pouvez pas supprimer cet enfant.');
        }

        if ($this->isCsrfTokenValid('delete'.$enfant->getId(), $request->request->get('_token'))) {
            $entityManager->remove($enfant);
            $entityManager->flush();

            $this->addFlash('success', 'Le compte a été supprimé avec succès.');
        }

        return $this->redirectToRoute('app_parent_dashboard');
    }

    #[Route('/commandes', name: 'app_parent_commandes', methods: ['GET'])]
    public function commandes(): Response
    {
        /** @var User $parent */
        $parent = $this->getUser();

        if (!$parent || $parent->getType() !== 'parent') {
            return $this->redirectToRoute('app_login');
        }

        $commandes = $parent->getCommandes()->toArray();
        usort($commandes, static fn ($a, $b) => ($b->getDateCommande() ?? new \DateTime()) <=> ($a->getDateCommande() ?? new \DateTime()));

        return $this->render('FrontOffice/parent/commandes.html.twig', [
            'parent' => $parent,
            'commandes' => $commandes,
        ]);
    }

    #[Route('/commande/{id}/edit', name: 'app_parent_commande_edit', methods: ['GET', 'POST'])]
    public function editCommande(
        Commande $commande,
        Request $request,
        EntityManagerInterface $entityManager
    ): Response {
        /** @var User $parent */
        $parent = $this->getUser();

        // Vérifier que la commande appartient bien au parent connecté
        if ($commande->getUser() !== $parent) {
            throw $this->createAccessDeniedException('Vous ne pouvez pas modifier cette commande.');
        }

        $form = $this->createForm(FrontCommandeType::class, $commande);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            // Recalculate total amount
            $product = $commande->getProduct();
            if ($product) {
                $commande->setTotalAmount($product->getPrice() * $commande->getQuantity());
            }

            $entityManager->persist($commande);
            $entityManager->flush();

            $this->addFlash('success', 'Commande modifiée avec succès.');
            return $this->redirectToRoute('app_parent_commandes');
        }

        return $this->render('FrontOffice/parent/commande_edit.html.twig', [
            'commande' => $commande,
            'form' => $form,
        ]);
    }

    #[Route('/commande/{id}/delete', name: 'app_parent_commande_delete', methods: ['POST'])]
    public function deleteCommande(
        Commande $commande,
        Request $request,
        EntityManagerInterface $entityManager
    ): Response {
        /** @var User $parent */
        $parent = $this->getUser();

        // Vérifier que la commande appartient bien au parent connecté
        if ($commande->getUser() !== $parent) {
            throw $this->createAccessDeniedException('Vous ne pouvez pas supprimer cette commande.');
        }

        if ($this->isCsrfTokenValid('delete'.$commande->getId(), $request->request->get('_token'))) {
            $entityManager->remove($commande);
            $entityManager->flush();

            $this->addFlash('success', 'Commande supprimée avec succès.');
        }

        return $this->redirectToRoute('app_parent_commandes');
    }

    #[Route('/commandes/{id}/paiement', name: 'app_parent_commande_paiement', methods: ['POST'])]
    public function paiement(Commande $commande, StripePaymentService $stripePaymentService): Response
    {
        // Ensure the user is the owner of the commande
        /** @var User $parent */
        $parent = $this->getUser();
        if ($commande->getUser() !== $parent) {
            throw $this->createAccessDeniedException('You do not have access to this commande.');
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

    #[Route('/commandes/{id}/paiement/form', name: 'app_parent_commande_paiement_form', methods: ['GET'])]
    public function paiementForm(Commande $commande): Response
    {
        /** @var User $parent */
        $parent = $this->getUser();
        if ($commande->getUser() !== $parent) {
            throw $this->createAccessDeniedException('Vous ne pouvez pas accéder à cette commande.');
        }

        return $this->render('FrontOffice/parent/paiement.html.twig', [
            'commande' => $commande,
            'stripe_public_key' => $_ENV['STRIPE_PUBLIC_KEY'],
        ]);
    }

    #[Route('/commandes/{id}/paiement/complete', name: 'app_parent_commande_paiement_complete', methods: ['POST'])]
    public function paiementComplete(Commande $commande, Request $request, EntityManagerInterface $em): Response
    {
        /** @var User $parent */
        $parent = $this->getUser();
        if ($commande->getUser() !== $parent) {
            throw $this->createAccessDeniedException('Vous do not have access to this commande.');
        }

        $data = json_decode($request->getContent(), true);
        $paymentIntentId = $data['paymentIntentId'] ?? null;

        if (!$paymentIntentId) {
            return $this->json(['error' => 'Missing paymentIntentId'], Response::HTTP_BAD_REQUEST);
        }

        $commande->setStripePaymentId($paymentIntentId);
        $commande->setIsPaid(true);
        $em->persist($commande);
        $em->flush();

        return $this->json(['status' => 'ok']);
    }

    #[Route('/commandes/{id}/checkout', name: 'app_parent_commande_checkout', methods: ['POST'])]
    public function checkout(Commande $commande, StripePaymentService $stripePaymentService, UrlGeneratorInterface $urlGenerator): Response
    {
        /** @var User $parent */
        $parent = $this->getUser();
        if ($commande->getUser() !== $parent) {
            throw $this->createAccessDeniedException('Vous do not have access to this commande.');
        }

        $successUrl = $urlGenerator->generate('app_parent_commande_checkout_success', ['id' => $commande->getId()], UrlGeneratorInterface::ABSOLUTE_URL) . '?session_id={CHECKOUT_SESSION_ID}';
        $cancelUrl = $urlGenerator->generate('app_parent_commande_paiement_form', ['id' => $commande->getId()], UrlGeneratorInterface::ABSOLUTE_URL);

        $session = $stripePaymentService->createCheckoutSession(
            $commande->getTotalAmount(),
            'usd',
            $successUrl,
            $cancelUrl,
            ['commande_id' => $commande->getId(), 'description' => 'Commande #' . $commande->getId()]
        );

        // Redirect user to Stripe Checkout
        return $this->redirect($session->url);
    }

    #[Route('/commandes/{id}/checkout/success', name: 'app_parent_commande_checkout_success', methods: ['GET'])]
    public function checkoutSuccess(Commande $commande, Request $request, StripePaymentService $stripePaymentService, EntityManagerInterface $em): Response
    {
        $sessionId = $request->query->get('session_id');
        if (!$sessionId) {
            $this->addFlash('error', 'Session manquante.');
            return $this->redirectToRoute('app_parent_commandes');
        }

        $session = $stripePaymentService->retrieveCheckoutSession($sessionId);

        // validate metadata
        if (!isset($session->metadata->commande_id) || (int) $session->metadata->commande_id !== $commande->getId()) {
            $this->addFlash('error', 'Session mismatch.');
            return $this->redirectToRoute('app_parent_commandes');
        }

        // mark commande as paid (we can also use webhook for production reliability)
        $commande->setStripePaymentId($session->payment_intent ?? $session->id);
        $commande->setIsPaid(true);
        $em->persist($commande);
        $em->flush();

        $this->addFlash('success', 'Paiement confirmé via Stripe Checkout.');
        return $this->redirectToRoute('app_parent_commandes');
    }
}