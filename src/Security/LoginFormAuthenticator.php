<?php

namespace App\Security;

use App\Entity\User;
use App\Service\GeolocationService;
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
use Symfony\Component\Security\Http\SecurityRequestAttributes;
use Symfony\Component\Security\Http\Util\TargetPathTrait;

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
        $login = (string) $request->request->get('login', '');
        $password = (string) $request->request->get('password', '');
        $csrfToken = $request->request->get('_csrf_token');
        $csrfToken = $csrfToken !== null ? (string) $csrfToken : null;

        $request->getSession()->set(SecurityRequestAttributes::LAST_USERNAME, $login);

        return new Passport(
            new UserBadge($login, function (string $userIdentifier) {
                $user = $this->entityManager->getRepository(User::class)->findOneBy(['email' => $userIdentifier]);

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
                new CsrfTokenBadge('authenticate', $csrfToken),
                new RememberMeBadge(),
            ]
        );
    }

    public function onAuthenticationSuccess(Request $request, TokenInterface $token, string $firewallName): ?Response
    {
        /** @var User $user */
        $user = $token->getUser();

        $ip = $request->getClientIp();
        $location = $this->geolocationService->getLocation($ip ?? '127.0.0.1');
        $user->setLastLoginIp($ip);
        $user->setLastLoginCity($location['city']);
        $user->setLastLoginCountry($location['country']);
        $user->setLastLoginAt(new \DateTime());
        
        $this->entityManager->flush();

        if ($targetPath = $this->getTargetPath($request->getSession(), $firewallName)) {
            return new RedirectResponse($targetPath);
        }

        $roles = $user->getRoles();

        if (in_array('ROLE_ADMIN', $roles, true)) {
            return new RedirectResponse($this->urlGenerator->generate('app_admin_dashboard'));
        }

        if (in_array('ROLE_PARENT', $roles, true)) {
            return new RedirectResponse($this->urlGenerator->generate('app_parent_dashboard'));
        }

        if (in_array('ROLE_ENSEIGNANT', $roles, true)) {
            return new RedirectResponse($this->urlGenerator->generate('teacher_game_index'));
        }

        if (in_array('ROLE_ENFANT', $roles, true)) {
            return new RedirectResponse($this->urlGenerator->generate('front_games'));
        }

        return new RedirectResponse($this->urlGenerator->generate('app_home'));
    }

    protected function getLoginUrl(Request $request): string
    {
        return $this->urlGenerator->generate(self::LOGIN_ROUTE);
    }
}