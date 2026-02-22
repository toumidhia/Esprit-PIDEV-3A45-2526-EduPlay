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
        $signatureComponents = $this->verifyEmailHelper->generateSignature(
            $routeName,
            (string) $user->getId(),
            $user->getEmail(),
            ['id' => $user->getId()] // Ajoutez ce paramètre pour avoir l'id dans l'URL
        );

        $email = (new TemplatedEmail())
            ->from(new Address('noreply@eduplay.com', 'EduPlay'))
            ->to($user->getEmail())
            ->subject('✅ Confirmez votre email - EduPlay')
            ->htmlTemplate('FrontOffice/security/confirmation_email.html.twig')
            ->context([
                'signedUrl' => $signatureComponents->getSignedUrl(),
                'user' => $user,
            ]);

        $this->mailer->send($email);
    }

    /**
     * Valide le lien de confirmation et active l'utilisateur
     * Retourne l'utilisateur validé
     */
    public function handleEmailConfirmation(Request $request): User
    {
        // Le bundle va valider le token et nous donner l'utilisateur
        // Mais pour ça, on a besoin de récupérer l'utilisateur d'abord
        $id = $request->query->get('id');
        
        if (!$id) {
            throw new \Exception('ID utilisateur manquant dans l\'URL');
        }
        
        $user = $this->entityManager->getRepository(User::class)->find($id);
        
        if (!$user) {
            throw new \Exception('Utilisateur non trouvé');
        }
        
        // Valide que le token correspond bien à cet utilisateur
        $this->verifyEmailHelper->validateEmailConfirmationFromRequest(
            $request,
            (string) $user->getId(),
            $user->getEmail()
        );
        
        // Active l'utilisateur
        $user->setActive(true);
        $this->entityManager->flush();
        
        return $user;
    }
}