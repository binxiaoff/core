<?php

declare(strict_types=1);

namespace Unilend\Security\Voter;

use LogicException;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\Voter\Voter;
use Unilend\Entity\AcceptationsLegalDocs;
use Unilend\Entity\Clients;
use Unilend\Traits\ConstantsAwareTrait;

class AcceptationLegalDocVoter extends Voter
{
    use ConstantsAwareTrait;
    public const ATTRIBUTE_DOWNLOAD = 'download';

    /**
     * {@inheritdoc}
     */
    protected function supports($attribute, $subject)
    {
        $attributes = self::getConstants('ATTRIBUTE_');

        if (false === in_array($attribute, $attributes)) {
            return false;
        }

        if (false === $subject instanceof AcceptationsLegalDocs) {
            return false;
        }

        return true;
    }

    /**
     * Perform a single access check operation on a given attribute, subject and token.
     * It is safe to assume that $attribute and $subject already passed the "supports()" method check.
     *
     * @param string         $attribute
     * @param mixed          $subject
     * @param TokenInterface $token
     *
     * @return bool
     */
    protected function voteOnAttribute($attribute, $subject, TokenInterface $token)
    {
        /** @var Clients $user */
        $user = $token->getUser();

        if (false === $user instanceof Clients) {
            return false;
        }

        switch ($attribute) {
            case self::ATTRIBUTE_DOWNLOAD:
                return $this->canDownload($subject, $user);
        }

        throw new LogicException('This code should not be reached');
    }

    /**
     * @param AcceptationsLegalDocs $attachment
     * @param Clients               $user
     *
     * @return bool
     */
    private function canDownload(AcceptationsLegalDocs $attachment, Clients $user): bool
    {
        return $attachment->getClient() === $user;
    }
}
