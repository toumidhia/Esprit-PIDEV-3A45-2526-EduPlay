<?php

namespace App\Security;

use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bridge\Twig\Mime\TemplatedEmail;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Address;
use SymfonyCasts\Bundle\VerifyEmail\VerifyEmailHelperInterface;
use SymfonyCasts\Bundle\VerifyEmail\Exception\VerifyEmailExceptionInterface;

class EmailVerifier
{
    public function __construct(
        private VerifyEmailHelperInterface $verifyEmailHelper,
        private MailerInterface $mailer,
        private EntityManagerInterface $entityManager
    ) {}

    public function sendEmailConfirmation(string $routeName, User $user): void
    {
        $email = $user->getEmail();

        if ($email === null) {
            throw new \LogicException('User email cannot be null for email verification.');
        }

        $userId = $user->getId();
        if ($userId === null) {
            throw new \LogicException('User ID cannot be null for email verification.');
        }

        $signatureComponents = $this->verifyEmailHelper->generateSignature(
            $routeName,
            (string) $userId,
            $email,
            ['id' => $userId]
        );

        $emailMessage = (new TemplatedEmail())
            ->from(new Address('noreply@eduplay.com', 'EduPlay'))
            ->to($email)
            ->subject('✅ Confirmez votre email - EduPlay')
            ->htmlTemplate('FrontOffice/security/confirmation_email.html.twig')
            ->context([
                'signedUrl' => $signatureComponents->getSignedUrl(),
                'user' => $user,
            ]);

        $this->mailer->send($emailMessage);
    }

    /**
     * Valide le lien de confirmation et active l'utilisateur
     */
    public function handleEmailConfirmation(Request $request): User
    {
        $id = $request->query->get('id');

        if ($id === null || $id === '') {
            throw new \Exception('ID utilisateur manquant dans l\'URL');
        }

        $user = $this->entityManager
            ->getRepository(User::class)
            ->find($id);

        if (!$user instanceof User) {
            throw new \Exception('Utilisateur non trouvé');
        }

        $email = $user->getEmail();
        if ($email === null) {
            throw new \LogicException('User email cannot be null during email verification.');
        }

        $userId = $user->getId();
        if ($userId === null) {
            throw new \LogicException('User ID cannot be null during email verification.');
        }

        try {
            $this->verifyEmailHelper->validateEmailConfirmationFromRequest(
                $request,
                (string) $userId,
                $email
            );
        } catch (VerifyEmailExceptionInterface $e) {
            throw $e;
        }

        $user->setActive(true);
        $this->entityManager->flush();

        return $user;
    }
}