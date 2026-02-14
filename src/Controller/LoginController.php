<?php
// src/Controller/LoginController.php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Authentication\AuthenticationUtils;

class LoginController extends AbstractController
{
    #[Route('/login', name: 'app_login')]
    public function index(AuthenticationUtils $authenticationUtils): Response
    {
        // If user is already logged in, redirect them
        if ($this->getUser()) {
            $user = $this->getUser();
            $roles = $user->getRoles();

            // Check for parent role first
            if (in_array('ROLE_PARENT', $roles)) {
                return $this->redirectToRoute('app_course_parent_browse');
            }

            // Check for teacher role
            if (in_array('ROLE_TEACHER', $roles)) {
                return $this->redirectToRoute('app_enseignant_dashboard');
            }

            // Check for admin
            if (in_array('ROLE_ADMIN', $roles)) {
                return $this->redirectToRoute('app_dashboard');
            }

            // Check for kid - REDIRIGER VERS COURSE INDEX
            if (in_array('ROLE_KID', $roles)) {
                return $this->redirectToRoute('app_course_index');
            }

            // Default redirect
            return $this->redirectToRoute('app_course_index');
        }

        // Get the login error if there is one
        $error = $authenticationUtils->getLastAuthenticationError();

        // Last username entered by the user
        $lastUsername = $authenticationUtils->getLastUsername();

        return $this->render('login/index.html.twig', [
            'last_username' => $lastUsername,
            'error' => $error,
        ]);
    }

    #[Route('/logout', name: 'app_logout', methods: ['GET'])]
    public function logout(): void
    {
        throw new \Exception('Don\'t forget to activate logout in security.yaml');
    }
}