<?php

namespace Unilend\Entity;

use DateTimeImmutable;
use Doctrine\ORM\Mapping as ORM;
use Exception;
use Unilend\Entity\Embeddable\Offer;
use Unilend\Entity\Traits\BlamableAddedTrait;
use Unilend\Entity\Traits\TimestampableAddedOnlyTrait;
use Unilend\Repository\ProjectParticipationTrancheVersionRepository;

/**
 * @ORM\Entity(repositoryClass=ProjectParticipationTrancheVersionRepository::class)
 */
class ProjectParticipationTrancheVersion
{
    use BlamableAddedTrait;
    use TimestampableAddedOnlyTrait;

    /**
     * @ORM\Id
     * @ORM\GeneratedValue
     * @ORM\Column(type="integer")
     */
    private int $id;

    /**
     * @ORM\ManyToOne(targetEntity=ProjectParticipationTranche::class, inversedBy="history")
     * @ORM\JoinColumn(name="id_project_participation_tranche", unique=true, nullable=false)
     */
    private ProjectParticipationTranche $projectParticipationTranche;

    /**
     * @ORM\Embedded(class="Unilend\Entity\Embeddable\Offer")
     */
    private Offer $invitationReply;

    /**
     * @param ProjectParticipationTranche $projectParticipationTranche
     * @param Offer                       $invitationReply
     * @param Staff                       $addedBy
     *
     * @throws Exception
     */
    public function __construct(ProjectParticipationTranche $projectParticipationTranche, Offer $invitationReply, Staff $addedBy)
    {
        $this->projectParticipationTranche = $projectParticipationTranche;
        $this->invitationReply             = $invitationReply;
        $this->addedBy                     = $addedBy;
        $this->added                       = new DateTimeImmutable();
    }
}
