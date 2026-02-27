<?php

namespace App\Controller;

use App\Entity\User;
use App\Form\ParentRegistrationType;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Authentication\AuthenticationUtils;

class SecurityController extends AbstractController
{
    #[Route('/register', name: 'app_register')]
    public function register(
        Request $request,
        UserPasswordHasherInterface $passwordHasher,
        EntityManagerInterface $entityManager
    ): Response {
        // Rediriger si déjà connecté
        if ($this->getUser()) {
            return $this->redirectToRoute('app_dashboard_redirect');
        }

        $user = new User();
        $form = $this->createForm(ParentRegistrationType::class, $user);
        $form->handleRequest($request);

        // Debugging: surface submission and validation errors to flashes so we can see why nothing happens
        if ($form->isSubmitted()) {
            if (!$form->isValid()) {
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
        }


        if ($form->isSubmitted() && $form->isValid()) {
            $user->setType('parent');

            $rawPassword = $form->get('password')->getData();
            if (!\is_string($rawPassword) || $rawPassword === '') {
                $this->addFlash('error', 'Veuillez entrer un mot de passe.');
                return $this->render('FrontOffice/security/register.html.twig', [
                    'registrationForm' => $form->createView(),
                ]);
            }

            $hashedPassword = $passwordHasher->hashPassword($user, $rawPassword);
            $user->setPassword($hashedPassword);

            try {
                $entityManager->persist($user);
                $entityManager->flush();
            } catch (\Throwable $e) {
                $this->addFlash('error', 'Une erreur est survenue lors de l\'inscription. Réessayez ou contactez le support.');
                return $this->render('FrontOffice/security/register.html.twig', [
                    'registrationForm' => $form->createView(),
                ]);
            }

            $this->addFlash('success', 'Votre compte a été créé avec succès ! Vous pouvez maintenant vous connecter.');
            return $this->redirectToRoute('app_login');
        }

        return $this->render('FrontOffice/security/register.html.twig', [
            'registrationForm' => $form->createView(),
        ]);
    }

    #[Route('/login', name: 'app_login')]
    public function login(AuthenticationUtils $authenticationUtils): Response
    {
        // Rediriger si déjà connecté
        if ($this->getUser()) {
            return $this->redirectToRoute('app_dashboard_redirect');
        }

        // Récupérer l'erreur de connexion s'il y en a une
        $error = $authenticationUtils->getLastAuthenticationError();
        
        // Dernier nom d'utilisateur entré
        $lastUsername = $authenticationUtils->getLastUsername();

        return $this->render('FrontOffice/security/login.html.twig', [
            'last_username' => $lastUsername,
            'error' => $error,
        ]);
    }

    #[Route('/logout', name: 'app_logout')]
    public function logout(): void
    {
        // Cette méthode sera interceptée par le système de sécurité
        throw new \LogicException('This method can be blank - it will be intercepted by the logout key on your firewall.');
    }
}