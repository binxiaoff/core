<?php

declare(strict_types=1);

namespace Unilend\Syndication\Listener\Doctrine\Entity\ProjectParticipationMember;

use Doctrine\ORM\Event\PreUpdateEventArgs;
use Symfony\Component\Security\Core\Security;
use Unilend\Core\Entity\User;
use Unilend\Syndication\Entity\ProjectParticipationMember;

class ProjectParticipationMemberArchivedListener
{
    private Security $security;

    public function __construct(Security $security)
    {
        $this->security = $security;
    }

    /**
     * @param PreUpdateEventArgs $args
     *
     * I do not use ArchivedByListener because we need to get all members on the front side. Soft deletable is not adapted for this case.
     */
    public function setArchivedBy(ProjectParticipationMember $projectParticipationMember, PreUpdateEventArgs $args): void
    {
        if ($args->hasChangedField('archived') && null !== $args->getNewValue('archived')) {
            /** @var User $user */
            $user = $this->security->getUser();
            $projectParticipationMember->setArchivedBy($user->getCurrentStaff());
        }
    }
}
