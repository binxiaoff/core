<?php

declare(strict_types=1);

namespace Unilend\Listener\Doctrine\Entity\ProjectParticipationMember;

use Doctrine\ORM\Event\PreUpdateEventArgs;
use Symfony\Component\Security\Core\Security;
use Unilend\Core\Entity\Clients;
use Unilend\Syndication\Entity\ProjectParticipationMember;

class ProjectParticipationMemberArchivedListener
{
    /** @var Security */
    private Security $security;

    /**
     * @param Security $security
     */
    public function __construct(Security $security)
    {
        $this->security = $security;
    }

    /**
     * @param ProjectParticipationMember $projectParticipationMember
     * @param PreUpdateEventArgs         $args
     *
     * I do not use ArchivedByListener because we need to get all members on the front side. Soft deletable is not adapted for this case.
     */
    public function setArchivedBy(ProjectParticipationMember $projectParticipationMember, PreUpdateEventArgs $args): void
    {
        if ($args->hasChangedField('archived') && null !== $args->getNewValue('archived')) {
            /** @var Clients $client */
            $client = $this->security->getUser();
            $projectParticipationMember->setArchivedBy($client->getCurrentStaff());
        }
    }
}
