<?php

declare(strict_types=1);

namespace Unilend\Entity;

use ApiPlatform\Core\Annotation\ApiResource;
use DateTimeImmutable;
use Doctrine\ORM\Mapping as ORM;
use Gedmo\Mapping\Annotation as Gedmo;
use Symfony\Component\Serializer\Annotation\Groups;
use Unilend\Entity\{Embeddable\NullableMoney, Embeddable\Offer, Traits\BlamableAddedTrait, Traits\PublicizeIdentityTrait, Traits\TimestampableTrait};
use Unilend\Traits\ConstantsAwareTrait;

/**
 * @ApiResource(
 *     normalizationContext={"groups": {
 *         "projectParticipationTranche:read",
 *         "offer:read",
 *         "nullableMoney:read"
 *     }},
 *     denormalizationContext={"groups": {
 *         "offer:write",
 *         "nullableMoney:write"
 *     }},
 *     collectionOperations={
 *         "post": {
 *             "denormalization_context": {"groups": {"projectParticipationTranche:create"}},
 *             "security_post_denormalize": "is_granted('create', object)"
 *         }
 *     },
 *     itemOperations={
 *         "get",
 *         "put": {"security_post_denormalize": "is_granted('edit', previous_object)"},
 *         "patch": {"security_post_denormalize": "is_granted('edit', previous_object)"}
 *     }
 * )
 *
 * @Gedmo\Loggable(logEntryClass="Unilend\Entity\Versioned\VersionedProjectParticipationTranche")
 *
 * @ORM\Table(uniqueConstraints={@ORM\UniqueConstraint(columns={"id_tranche", "id_project_participation"})})
 * @ORM\Entity
 * @ORM\HasLifecycleCallbacks
 */
class ProjectParticipationTranche
{
    use PublicizeIdentityTrait;
    use TimestampableTrait;
    use ConstantsAwareTrait;
    use BlamableAddedTrait;

    // Additional normalizer group that is available for public visibility project. It's also available for the participation owner and arranger
    public const SERIALIZER_GROUP_SENSITIVE_READ = 'projectParticipationTranche:sensitive:read';
    // Additional denormalizer group that is available for the participation owner
    public const SERIALIZER_GROUP_PARTICIPANT_OWNER_WRITE = 'projectParticipationTranche:participantOwner:write';
    // Additional denormalizer group that is available for the arranger
    public const SERIALIZER_GROUP_ARRANGER_WRITE = 'projectParticipationTranche:arranger:write';

    /**
     * @var Tranche
     *
     * @ORM\ManyToOne(targetEntity="Unilend\Entity\Tranche")
     * @ORM\JoinColumns({
     *     @ORM\JoinColumn(name="id_tranche", nullable=false)
     * })
     *
     * @Groups({"projectParticipationTranche:read", "projectParticipationTranche:create"})
     */
    private $tranche;

    /**
     * @var ProjectParticipation
     *
     * @ORM\ManyToOne(targetEntity="Unilend\Entity\ProjectParticipation", inversedBy="projectParticipationTranches")
     * @ORM\JoinColumns({
     *     @ORM\JoinColumn(name="id_project_participation", nullable=false)
     * })
     *
     * @Groups({"projectParticipationTranche:create"})
     */
    private $projectParticipation;

    /**
     * @var Offer
     *
     * @ORM\Embedded(class="Unilend\Entity\Embeddable\Offer")
     *
     * @Groups({"projectParticipationTranche:sensitive:read", "projectParticipationTranche:participantOwner:write"})
     */
    private $invitationReply;

    /**
     * @var Offer
     *
     * @ORM\Embedded(class="Unilend\Entity\Embeddable\Offer")
     *
     * @Groups({"projectParticipationTranche:sensitive:read", "projectParticipationTranche:arranger:write"})
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
        $this->addedBy              = $addedBy;
        $this->added                = new DateTimeImmutable();
        $this->invitationReply      = new Offer(new NullableMoney());
        $this->allocation           = new Offer(new NullableMoney());
    }

    /**
     * @return Tranche
     */
    public function getTranche(): Tranche
    {
        return $this->tranche;
    }

    /**
     * @return ProjectParticipation
     */
    public function getProjectParticipation(): ProjectParticipation
    {
        return $this->projectParticipation;
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
}
