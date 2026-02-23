<?php
// src/Security/LoginFormAuthenticator.php

namespace App\Security;

use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Exception\CustomUserMessageAuthenticationException;
use Symfony\Component\Security\Http\Authenticator\AbstractLoginFormAuthenticator;
use Symfony\Component\Security\Http\Authenticator\Passport\Badge\CsrfTokenBadge;
use Symfony\Component\Security\Http\Authenticator\Passport\Badge\RememberMeBadge;
use Symfony\Component\Security\Http\Authenticator\Passport\Badge\UserBadge;
use Symfony\Component\Security\Http\Authenticator\Passport\Credentials\PasswordCredentials;
use Symfony\Component\Security\Http\Authenticator\Passport\Passport;
use Symfony\Component\Security\Http\Util\TargetPathTrait;
use Symfony\Component\Security\Http\SecurityRequestAttributes;
use App\Service\GeolocationService;

class LoginFormAuthenticator extends AbstractLoginFormAuthenticator
{
    use TargetPathTrait;

    public const LOGIN_ROUTE = 'app_login';

    public function __construct(
        private UrlGeneratorInterface $urlGenerator,
        private EntityManagerInterface $entityManager,
        private GeolocationService $geolocationService

    ) {}

    public function authenticate(Request $request): Passport
    {
        $login = $request->request->get('login', '');
        $password = $request->request->get('password', '');

        $request->getSession()->set(SecurityRequestAttributes::LAST_USERNAME, $login);

        return new Passport(
            new UserBadge($login, function ($userIdentifier) {
                // Cherche par email
                $user = $this->entityManager->getRepository(User::class)->findOneBy(['email' => $userIdentifier]);

                // Si pas trouvé, cherche par username (pour les enfants)
                if (!$user) {
                    $user = $this->entityManager->getRepository(User::class)->findOneBy(['username' => $userIdentifier]);
                }

                if (!$user) {
                    throw new CustomUserMessageAuthenticationException('Identifiants incorrects.');
                }

                if (!$user->isActive()) {
                    throw new CustomUserMessageAuthenticationException(
                        'Votre compte n\'est pas actif. Veuillez vérifier votre email pour activer votre compte.'
                    );
                }

                return $user;
            }),
            new PasswordCredentials($password),
            [
                new CsrfTokenBadge('authenticate', $request->request->get('_csrf_token')),
                new RememberMeBadge(),
            ]
        );
    }

    public function onAuthenticationSuccess(Request $request, TokenInterface $token, string $firewallName): ?Response
    {
        $user = $token->getUser();

         /** @var SessionInterface $session */
        $session = $request->getSession();
        $session->getFlashBag()->clear();

        $ip = $request->getClientIp();
        $location = $this->geolocationService->getLocationFromIp($ip);
        
        if ($location) {
            $user->setLastLoginIp($ip);
            $user->setLastLoginCountry($location['country_code'] ?? null);
            $user->setLastLoginCity($location['city'] ?? null);
            $user->setLastLoginAt(new \DateTime());
            
            $this->entityManager->flush();
        }

        if ($targetPath = $this->getTargetPath($request->getSession(), $firewallName)) {
            return new RedirectResponse($targetPath);
        }

        $roles = $user->getRoles();

        if (in_array('ROLE_ADMIN', $roles)) {
            return new RedirectResponse($this->urlGenerator->generate('app_admin_dashboard'));
        }

        if (in_array('ROLE_PARENT', $roles)) {
            return new RedirectResponse($this->urlGenerator->generate('app_parent_dashboard'));
        }

        if (in_array('ROLE_ENSEIGNANT', $roles)) {
            // Note: User manually set this to teacher_game_index in DashboardRedirectController
            return new RedirectResponse($this->urlGenerator->generate('teacher_game_index'));
        }

        if (in_array('ROLE_ENFANT', $roles)) {
            return new RedirectResponse($this->urlGenerator->generate('front_games'));
        }

        // Default redirect
        return new RedirectResponse($this->urlGenerator->generate('app_course_index'));
    }

    protected function getLoginUrl(Request $request): string
    {
        return $this->urlGenerator->generate(self::LOGIN_ROUTE);
    }
}