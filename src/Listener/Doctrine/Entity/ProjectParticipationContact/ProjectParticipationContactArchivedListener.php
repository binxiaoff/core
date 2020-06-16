<?php

declare(strict_types=1);

namespace Unilend\Listener\Doctrine\Entity\ProjectParticipationContact;

use Doctrine\ORM\Event\PreUpdateEventArgs;
use Symfony\Component\Security\Core\Security;
use Unilend\Entity\Clients;
use Unilend\Entity\ProjectParticipationContact;

class ProjectParticipationContactArchivedListener
{
    /** @var Security */
    private $security;

    /**
     * @param Security $security
     */
    public function __construct(Security $security)
    {
        $this->security = $security;
    }

    /**
     * @param ProjectParticipationContact $participationContact
     * @param PreUpdateEventArgs          $args
     *
     * I do not use ArchivedByListener because we need to get all contacts on the front side. Soft deleteable is not adapted for this case.
     */
    public function setArchivedBy(ProjectParticipationContact $participationContact, PreUpdateEventArgs $args): void
    {
        if ($args->hasChangedField('archived') && null !== $args->getNewValue('archived')) {
            /** @var Clients $client */
            $client = $this->security->getUser();
            $participationContact->setArchivedBy($client->getCurrentStaff());
        }
    }
}
