<?php

declare(strict_types=1);

namespace KLS\Syndication\Arrangement\Entity;

use Doctrine\ORM\Mapping as ORM;
use KLS\Core\Entity\Staff;
use KLS\Core\Entity\Traits\BlamableAddedTrait;
use KLS\Syndication\Arrangement\Entity\Embeddable\Offer;
use Symfony\Component\Serializer\Annotation\Groups;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * @ORM\Entity
 * @ORM\Table(name="syndication_invitation_reply_version")
 */
class InvitationReplyVersion
{
    use BlamableAddedTrait;

    /**
     * @ORM\Id
     * @ORM\GeneratedValue
     * @ORM\Column(type="integer")
     */
    private int $id;

    /**
     * @ORM\ManyToOne(targetEntity="KLS\Syndication\Arrangement\Entity\ProjectParticipationTranche", inversedBy="invitationReplyVersions")
     * @ORM\JoinColumn(name="id_project_participation_tranche", nullable=false)
     */
    private ProjectParticipationTranche $projectParticipationTranche;

    /**
     * @ORM\Embedded(class="KLS\Syndication\Arrangement\Entity\Embeddable\Offer")
     *
     * @Assert\Valid
     *
     * @Groups({"invitationReplyVersion:read"})
     */
    private Offer $invitationReply;

    /**
     * @ORM\ManyToOne(targetEntity="KLS\Syndication\Arrangement\Entity\ProjectParticipationStatus")
     * @ORM\JoinColumn(name="id_project_participation_status", nullable=false)
     *
     * @Groups({"invitationReplyVersion:read"})
     */
    private ProjectParticipationStatus $currentProjectParticipationStatus;

    public function __construct(ProjectParticipationTranche $projectParticipationTranche, Staff $addedBy)
    {
        $this->projectParticipationTranche       = $projectParticipationTranche;
        $this->invitationReply                   = $projectParticipationTranche->getInvitationReply();
        $this->currentProjectParticipationStatus = $projectParticipationTranche->getProjectParticipation()->getCurrentStatus();
        $this->addedBy                           = $addedBy;
    }

    public function getId(): int
    {
        return $this->id;
    }

    public function getProjectParticipationTranche(): ProjectParticipationTranche
    {
        return $this->projectParticipationTranche;
    }

    public function getInvitationReply(): Offer
    {
        return $this->invitationReply;
    }

    public function getCurrentProjectParticipationStatus(): ProjectParticipationStatus
    {
        return $this->currentProjectParticipationStatus;
    }
}
