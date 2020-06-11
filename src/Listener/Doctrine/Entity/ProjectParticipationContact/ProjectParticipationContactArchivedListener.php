<?php

declare(strict_types=1);

namespace Unilend\Listener\Doctrine\Entity\ProjectParticipationContact;

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
     *
     * I do not use ArchivedByListener because we need to get all contacts on the front side. Soft deleteable is not adapted for this case.
     */
    public function setArchivedBy(ProjectParticipationContact $participationContact): void
    {
        if ($participationContact->isArchived()) {
            /** @var Clients $client */
            $client = $this->security->getUser();
            $participationContact->setArchivedBy($client->getCurrentStaff());
        }
    }
}
