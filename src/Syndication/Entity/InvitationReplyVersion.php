<?php

namespace Unilend\Syndication\Entity;

use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Annotation\Groups;
use Symfony\Component\Validator\Constraints as Assert;
use Unilend\Core\Entity\Staff;
use Unilend\Core\Entity\Traits\BlamableAddedTrait;
use Unilend\Syndication\Entity\Embeddable\Offer;

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
     * @ORM\ManyToOne(targetEntity="Unilend\Syndication\Entity\ProjectParticipationTranche", inversedBy="invitationReplyVersions")
     * @ORM\JoinColumn(name="id_project_participation_tranche", nullable=false)
     */
    private ProjectParticipationTranche $projectParticipationTranche;

    /**
     *
     * @var Offer
     *
     * @ORM\Embedded(class="Unilend\Syndication\Entity\Embeddable\Offer")
     *
     * @Assert\Valid
     *
     * @Groups({"invitationReplyVersion:read"})
     */
    private Offer $invitationReply;

    /**
     * @var ProjectParticipationStatus
     *
     * @ORM\ManyToOne(targetEntity="Unilend\Syndication\Entity\ProjectParticipationStatus")
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
