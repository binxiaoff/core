<?php

namespace Unilend\Entity;

use DateTimeImmutable;
use Doctrine\ORM\Mapping as ORM;
use Exception;
use Unilend\Entity\Embeddable\Offer;
use Unilend\Entity\Traits\BlamableAddedTrait;
use Unilend\Entity\Traits\TimestampableAddedOnlyTrait;
use Unilend\Repository\InvitationReplyVersionRepository;

/**
 * @ORM\Entity(repositoryClass=InvitationReplyVersionRepository::class)
 */
class InvitationReplyVersion
{
    use BlamableAddedTrait;
    use TimestampableAddedOnlyTrait;

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
     * @ORM\ManyToOne(targetEntity=ProjectParticipationTranche::class, inversedBy="history")
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
     */
    private Offer $invitationReply;

    /**
     * @param ProjectParticipationTranche $projectParticipationTranche
     * @param Staff                       $addedBy
     *
     * @throws Exception
     */
    public function __construct(ProjectParticipationTranche $projectParticipationTranche, Staff $addedBy)
    {
        $this->projectParticipationTranche = $projectParticipationTranche;
        $this->invitationReply             = $projectParticipationTranche->getInvitationReply();
        $this->addedBy                     = $addedBy;
        $this->added                       = new DateTimeImmutable();
    }
}
