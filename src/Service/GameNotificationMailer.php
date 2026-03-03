<?php

namespace App\Service;

use App\Entity\Game;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Email;

class GameNotificationMailer
{
    public function __construct(private MailerInterface $mailer) {}
/**
 * @param string[] $childEmails Liste des emails des enfants
 */
    public function sendNewGameToChildren(array $childEmails, Game $game): void
    {
        if (empty($childEmails)) {
            return;
        }

        $email = (new Email())
            ->from('EduPlay <eduplayeduplay4@gmail.com>')
            ->to('no-reply@eduplay.tn')     // ✅ adresse “to” neutre
            ->bcc(...$childEmails)          // ✅ enfants en BCC (personne ne voit les autres)
            ->subject('New game added: ' . $game->getName())
            ->html(sprintf(
                '<h2>A new game is available!</h2>
                 <p><strong>%s</strong></p>
                 <p>Type: %s</p>
                 <p>%s</p>',
                htmlspecialchars($game->getName()?? ''),
                htmlspecialchars($game->getType()?? ''),
                nl2br(htmlspecialchars($game->getDescription()?? ''))
            ));

        $this->mailer->send($email);
    }
}
