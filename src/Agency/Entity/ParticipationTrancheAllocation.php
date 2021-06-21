<?php

declare(strict_types=1);

namespace Unilend\Agency\Entity;

use ApiPlatform\Core\Annotation\ApiProperty;
use ApiPlatform\Core\Annotation\ApiResource;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;
use Symfony\Component\Serializer\Annotation\Groups;
use Symfony\Component\Validator\Constraints as Assert;
use Unilend\Core\Entity\Embeddable\Money;
use Unilend\Core\Entity\Traits\PublicizeIdentityTrait;

/**
 * @ApiResource(
 *     normalizationContext={
 *         "groups": {"agency:participationTrancheAllocation:read"}
 *     },
 *     denormalizationContext={
 *         "groups": {"agency:participationTrancheAllocation:write"}
 *     },
 *     itemOperations={
 *         "get": {
 *             "controller": "ApiPlatform\Core\Action\NotFoundAction",
 *             "read": false,
 *             "output": false,
 *         }
 *     },
 *     collectionOperations={}
 * )
 *
 * @ORM\Entity
 * @ORM\Table(name="agency_participation_tranche_allocation", uniqueConstraints={
 *     @ORM\UniqueConstraint(columns={"id_participation", "id_tranche"})
 * })
 *
 * @UniqueEntity(fields={"tranche", "participation"})
 *
 * @Assert\Expression(expression="this.getParticipation().getProject() === this.getTranche().getProject()", message="Agency.ParticipantTrancheAllocation.project")
 */
class ParticipationTrancheAllocation
{
    use PublicizeIdentityTrait;

    /**
     * @ORM\ManyToOne(targetEntity=Participation::class, inversedBy="allocations")
     * @ORM\JoinColumn(name="id_participation", nullable=false, onDelete="CASCADE")
     *
     * @Assert\NotBlank
     *
     * @ApiProperty(readableLink=false)
     *
     * @Groups({"agency:participationTrancheAllocation:read", "agency:participationTrancheAllocation:write"})
     */
    private Participation $participation;

    /**
     * @ORM\ManyToOne(targetEntity=Tranche::class, inversedBy="allocations")
     * @ORM\JoinColumn(name="id_tranche", nullable=false, onDelete="CASCADE")
     *
     * @Assert\NotBlank
     * @Assert\Expression("value.isSyndicated()", message="Agency.ParticipantTrancheAllocation.tranche")
     *
     * @Groups({"agency:participationTrancheAllocation:read", "agency:participationTrancheAllocation:write"})
     */
    private Tranche $tranche;

    /**
     * @Assert\NotBlank
     * @Assert\Valid
     *
     * @ORM\Embedded(class=Money::class)
     *
     * @Groups({"agency:participationTrancheAllocation:read", "agency:participationTrancheAllocation:write"})
     */
    private Money $allocation;

    public function __construct(Participation $participation, Tranche $tranche, Money $allocation)
    {
        $this->participation = $participation;
        $this->tranche       = $tranche;
        $this->allocation    = $allocation;
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getParticipation(): Participation
    {
        return $this->participation;
    }

    public function getTranche(): Tranche
    {
        return $this->tranche;
    }

    public function getAllocation(): Money
    {
        return $this->allocation;
    }

    public function setAllocation(Money $allocation): ParticipationTrancheAllocation
    {
        $this->allocation = $allocation;

        return $this;
    }
}
