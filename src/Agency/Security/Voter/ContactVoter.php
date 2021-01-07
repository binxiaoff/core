<?php

declare(strict_types=1);

namespace Unilend\Agency\Security\Voter;

use Unilend\Agency\Entity\Contact;
use Unilend\Core\Entity\User;
use Unilend\Core\Security\Voter\AbstractEntityVoter;

class ContactVoter extends AbstractEntityVoter
{
    public const ATTRIBUTE_DELETE = 'delete';
    public const ATTRIBUTE_EDIT   = 'edit';
    public const ATTRIBUTE_CREATE = 'create';

    /**
     * @param Contact $contact
     * @param User    $user
     *
     * @return bool
     */
    protected function isGrantedAll($contact, User $user): bool
    {
        // TODO change after new habilitations are merged
        $currentUserStaff = $user->getCurrentStaff();

        return $contact->getProject()->getAgent() === $currentUserStaff->getCompany();
    }
}
