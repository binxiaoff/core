<?php

namespace Unilend\Entity;

use ApiPlatform\Core\Annotation\ApiResource;
use DateTimeImmutable;
use Doctrine\ORM\Mapping as ORM;
use Exception;
use Symfony\Component\Serializer\Annotation\Groups;
use Symfony\Component\Validator\Constraints as Assert;
use Unilend\Entity\Embeddable\Offer;
use Unilend\Entity\Traits\BlamableAddedTrait;
use Unilend\Entity\Traits\TimestampableAddedOnlyTrait;

/**
 * @ORM\Entity
 *
 * @ApiResource(
 *     normalizationContext={"groups": {"invitationReplyVersion:read"}},
 *     itemOperations={
 *         "get": {
 *             "controller": "ApiPlatform\Core\Action\NotFoundAction",
 *             "read": false,
 *             "output": false,
 *         }
 *     },
 *     collectionOperations={}
 * )
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
    public int $id;

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
}
