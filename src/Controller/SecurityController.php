<?php

namespace App\Controller;

use App\Entity\User;
use App\Form\ParentRegistrationType;
use App\Security\EmailVerifier;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Authentication\AuthenticationUtils;
use SymfonyCasts\Bundle\VerifyEmail\Exception\VerifyEmailExceptionInterface;

class SecurityController extends AbstractController
{
    // ----------------------------
    // Inscription Parent
    // ----------------------------
    #[Route('/register', name: 'app_register')]
    public function register(
        Request $request,
        UserPasswordHasherInterface $passwordHasher,
        EntityManagerInterface $entityManager,
        EmailVerifier $emailVerifier
    ): Response {
        if ($this->getUser()) {
            return $this->redirectToRoute('app_dashboard_redirect');
        }

        $user = new User();
        $user->setActive(false); // Compte inactif par défaut
        $form = $this->createForm(ParentRegistrationType::class, $user);
        $form->handleRequest($request);

        if ($form->isSubmitted() && !$form->isValid()) {
            $errors = $form->getErrors(true, true);
            $msgParts = [];
            foreach ($errors as $error) {
                $origin = $error->getOrigin();
                $field = $origin ? $origin->getName() : 'form';
                $msgParts[] = sprintf('%s: %s', $field, $error->getMessage());
            }
            if (count($msgParts) > 0) {
                $this->addFlash('error', implode(' | ', $msgParts));
            }
        }

        if ($form->isSubmitted() && $form->isValid()) {
            $user->setType('parent');

            $rawPassword = $form->get('password')->getData();
            $hashedPassword = $passwordHasher->hashPassword($user, $rawPassword);
            $user->setPassword($hashedPassword);

            try {
                $entityManager->persist($user);
                $entityManager->flush();

                // Envoi de l’email de vérification
                $emailVerifier->sendEmailConfirmation('app_verify_email', $user);

            } catch (\Throwable $e) {
                $this->addFlash('error', 'Une erreur est survenue. Réessayez ou contactez le support.');
                return $this->render('FrontOffice/security/register.html.twig', [
                    'registrationForm' => $form->createView(),
                ]);
            }

            $this->addFlash(
                'success',
                'Votre compte a été créé avec succès ! Un email de confirmation vous a été envoyé. Vous devez vérifier votre email pour activer votre compte.'
            );
            return $this->redirectToRoute('app_login');
        }

        return $this->render('FrontOffice/security/register.html.twig', [
            'registrationForm' => $form->createView(),
        ]);
    }

    // ----------------------------
    // Vérification email
    // ----------------------------
    #[Route('/verify/email', name: 'app_verify_email')]
public function verifyUserEmail(
    Request $request,
    UserRepository $userRepository,
    EmailVerifier $emailVerifier
): Response {
    $id = $request->query->get('id');

    if (!$id) {
        $this->addFlash('error', 'Lien invalide - ID manquant.');
        return $this->redirectToRoute('app_login');
    }

    $user = $userRepository->find($id);
    if (!$user) {
        $this->addFlash('error', 'Utilisateur non trouvé.');
        return $this->redirectToRoute('app_login');
    }

    try {
        $emailVerifier->handleEmailConfirmation($request);
        
        $this->addFlash('success', '✅ Félicitations ! Votre email a été vérifié. Vous pouvez maintenant vous connecter.');
        
    } catch (VerifyEmailExceptionInterface $e) {
        // Exception spécifique du bundle (lien expiré, invalide, déjà utilisé)
        $this->addFlash('error', ' Le lien de vérification est invalide ou a expiré. ' . $e->getReason());
        
    } catch (\Exception $e) {
        // Pour le débogage uniquement - À SUPPRIMER en production
        dump($e->getMessage());
        dd($e);
        
        // En production, remplacez par :
        // $this->addFlash('error', 'Une erreur technique est survenue. Veuillez réessayer ou contacter le support.');
    }

    return $this->redirectToRoute('app_login');
}

    // ----------------------------
    // Login
    // ----------------------------
    #[Route('/login', name: 'app_login')]
    public function login(AuthenticationUtils $authenticationUtils): Response
    {
        if ($this->getUser()) {
            return $this->redirectToRoute('app_dashboard_redirect');
        }

        $error = $authenticationUtils->getLastAuthenticationError();
        $lastUsername = $authenticationUtils->getLastUsername();

        return $this->render('FrontOffice/security/login.html.twig', [
            'last_username' => $lastUsername,
            'error' => $error,
        ]);
    }

    // ----------------------------
    // Logout
    // ----------------------------
    #[Route('/logout', name: 'app_logout')]
    public function logout(): void
    {
        throw new \LogicException('This method will be intercepted by the firewall.');
    }
}