<?php

namespace App\Security\Voter;

use App\Entity\User;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\Voter\Voter;

/**
 * @extends Voter<string, User>
 */
class EnfantVoter extends Voter
{
    public const EDIT = 'EDIT_ENFANT';
    public const DELETE = 'DELETE_ENFANT';

    public function __construct(private Security $security) {}

    protected function supports(string $attribute, mixed $subject): bool
    {
        return in_array($attribute, [self::EDIT, self::DELETE])
            && $subject instanceof User;
    }

    protected function voteOnAttribute(string $attribute, mixed $subject, TokenInterface $token): bool
    {
        $user = $token->getUser();

        if (!$user instanceof User) {
            return false;
        }

        // Admin peut tout faire
        if ($this->security->isGranted('ROLE_ADMIN')) {
            return true;
        }

        // Seulement parent propriétaire
        return $subject->getParent() === $user;
    }
}