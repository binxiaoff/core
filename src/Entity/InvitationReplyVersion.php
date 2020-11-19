<?php

namespace Unilend\Entity;

use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Annotation\Groups;
use Symfony\Component\Validator\Constraints as Assert;
use Unilend\Entity\Embeddable\Offer;
use Unilend\Entity\Traits\BlamableAddedTrait;

/**
 * @ORM\Entity
 */
class InvitationReplyVersion
{
    use BlamableAddedTrait;

    /**
     * @var int
     *
     * @ORM\Id
     * @ORM\GeneratedValue
     * @ORM\Column(type="integer")
     */
    private int $id;

    /**
     * @var ProjectParticipationTranche
     *
     * @ORM\ManyToOne(targetEntity="Unilend\Entity\ProjectParticipationTranche", inversedBy="invitationReplyVersions")
     * @ORM\JoinColumn(name="id_project_participation_tranche", nullable=false)
     */
    private ProjectParticipationTranche $projectParticipationTranche;

    /**
     *
     * @var Offer
     *
     * @ORM\Embedded(class="Unilend\Entity\Embeddable\Offer")
     *
     * @Assert\Valid
     *
     * @Groups({"invitationReplyVersion:read"})
     */
    private Offer $invitationReply;

    /**
     * @var ProjectParticipationStatus
     *
     * @ORM\ManyToOne(targetEntity="Unilend\Entity\ProjectParticipationStatus")
     * @ORM\JoinColumn(name="id_project_participation_status", nullable=false)
     *
     * @Groups({"invitationReplyVersion:read"})
     */
    private ProjectParticipationStatus $currentProjectParticipationStatus;

    /**
     * @param ProjectParticipationTranche $projectParticipationTranche
     * @param Staff                       $addedBy
     */
    public function __construct(ProjectParticipationTranche $projectParticipationTranche, Staff $addedBy)
    {
        $this->projectParticipationTranche       = $projectParticipationTranche;
        $this->invitationReply                   = $projectParticipationTranche->getInvitationReply();
        $this->currentProjectParticipationStatus = $projectParticipationTranche->getProjectParticipation()->getCurrentStatus();
        $this->addedBy                           = $addedBy;
    }

    /**
     * @return int
     */
    public function getId(): int
    {
        return $this->id;
    }

    /**
     * @return ProjectParticipationTranche
     */
    public function getProjectParticipationTranche(): ProjectParticipationTranche
    {
        return $this->projectParticipationTranche;
    }

    /**
     * @return Offer
     */
    public function getInvitationReply(): Offer
    {
        return $this->invitationReply;
    }

    /**
     * @return ProjectParticipationStatus
     */
    public function getCurrentProjectParticipationStatus(): ProjectParticipationStatus
    {
        return $this->currentProjectParticipationStatus;
    }
}
