<?php

declare(strict_types=1);

namespace Unilend\Entity;

use DateTimeImmutable;
use Doctrine\ORM\Mapping as ORM;
use Gedmo\Mapping\Annotation as Gedmo;
use Symfony\Component\Serializer\Annotation\Groups;
use Unilend\Entity\{Embeddable\Offer, Traits\BlamableAddedTrait, Traits\TimestampableTrait};
use Unilend\Traits\ConstantsAwareTrait;

/**
 * @Gedmo\Loggable(logEntryClass="Unilend\Entity\Versioned\VersionedProjectParticipationTranche")
 *
 * @ORM\Table(uniqueConstraints={@ORM\UniqueConstraint(columns={"id_tranche", "id_project_participation"})})
 * @ORM\Entity
 * @ORM\HasLifecycleCallbacks
 */
class ProjectParticipationTranche
{
    use TimestampableTrait;
    use ConstantsAwareTrait;
    use BlamableAddedTrait;

    /**
     * @var int
     *
     * @ORM\Column(type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="IDENTITY")
     */
    private $id;

    /**
     * @var Tranche
     *
     * @ORM\ManyToOne(targetEntity="Unilend\Entity\Tranche")
     * @ORM\JoinColumns({
     *     @ORM\JoinColumn(name="id_tranche", nullable=false)
     * })
     */
    private $tranche;

    /**
     * @var ProjectParticipation
     *
     * @ORM\ManyToOne(targetEntity="Unilend\Entity\ProjectParticipation", inversedBy="projectParticipationTranches")
     * @ORM\JoinColumns({
     *     @ORM\JoinColumn(name="id_project_participation", nullable=false)
     * })
     */
    private $projectParticipation;

    /**
     * @var Offer
     *
     * @ORM\Embedded(class="Unilend\Entity\Embeddable\Offer")
     *
     * @Gedmo\Versioned
     *
     * @Groups({"ProjectParticipationTranche:sensitive:read", "ProjectParticipationTranche:participantOwner:write"})
     */
    private $invitationReply;

    /**
     * @var Offer
     *
     * @ORM\Embedded(class="Unilend\Entity\Embeddable\Offer")
     *
     * @Gedmo\Versioned
     *
     * @Groups({"ProjectParticipationTranche:sensitive:read", "ProjectParticipationTranche:arrangerOwner:write"})
     */
    private $allocation;

    /**
     * @param ProjectParticipation $projectParticipation
     * @param Tranche              $tranche
     * @param Staff                $addedBy
     */
    public function __construct(ProjectParticipation $projectParticipation, Tranche $tranche, Staff $addedBy)
    {
        $this->projectParticipation = $projectParticipation;
        $this->tranche              = $tranche;
        $this->added                = new DateTimeImmutable();
        $this->addedBy              = $addedBy;
    }

    /**
     * @return int|null
     */
    public function getId(): ?int
    {
        return $this->id;
    }

    /**
     * @return Tranche
     */
    public function getTranche(): Tranche
    {
        return $this->tranche;
    }

    /**
     * @param Tranche $tranche
     *
     * @return ProjectParticipationTranche
     */
    public function setTranche(Tranche $tranche): ProjectParticipationTranche
    {
        $this->tranche = $tranche;

        return $this;
    }

    /**
     * @return ProjectParticipation
     */
    public function getProjectParticipation(): ProjectParticipation
    {
        return $this->projectParticipation;
    }

    /**
     * @param ProjectParticipation $projectParticipation
     *
     * @return ProjectParticipationTranche
     */
    public function setProjectParticipation(ProjectParticipation $projectParticipation): ProjectParticipationTranche
    {
        $this->projectParticipation = $projectParticipation;

        return $this;
    }

    /**
     * @return Offer
     */
    public function getInvitationReply(): Offer
    {
        return $this->invitationReply;
    }

    /**
     * @param Offer $invitationReply
     *
     * @return ProjectParticipationTranche
     */
    public function setInvitationReply(Offer $invitationReply): ProjectParticipationTranche
    {
        $this->invitationReply = $invitationReply;

        return $this;
    }

    /**
     * @return Offer
     */
    public function getAllocation(): Offer
    {
        return $this->allocation;
    }

    /**
     * @param Offer $allocation
     *
     * @return ProjectParticipationTranche
     */
    public function setAllocation(Offer $allocation): ProjectParticipationTranche
    {
        $this->allocation = $allocation;

        return $this;
    }

    /**
     * @return array
     */
    public function getAvailableStatus(): array
    {
        return self::getConstants('STATUS_');
    }
}
