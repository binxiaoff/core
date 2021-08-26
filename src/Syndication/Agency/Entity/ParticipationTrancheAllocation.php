<?php

declare(strict_types=1);

namespace KLS\Syndication\Agency\Entity;

use ApiPlatform\Core\Annotation\ApiProperty;
use ApiPlatform\Core\Annotation\ApiResource;
use Doctrine\ORM\Mapping as ORM;
use KLS\Core\Entity\Embeddable\Money;
use KLS\Core\Entity\Traits\PublicizeIdentityTrait;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;
use Symfony\Component\Serializer\Annotation\Groups;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * @ApiResource(
 *     normalizationContext={
 *         "groups": {
 *             "agency:participationTrancheAllocation:read",
 *         },
 *         "openapi_definition_name": "read",
 *     },
 *     denormalizationContext={
 *         "groups": {
 *             "agency:participationTrancheAllocation:write",
 *         },
 *         "openapi_definition_name": "write",
 *     },
 *     itemOperations={
 *         "get": {
 *             "controller": "ApiPlatform\Core\Action\NotFoundAction",
 *             "read": false,
 *             "output": false,
 *             "openapi_context": {
 *                 "x-visibility": "hide",
 *             },
 *         },
 *     },
 *     collectionOperations={},
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
     * @ORM\ManyToOne(targetEntity=Participation::class, inversedBy="allocations", cascade={"persist"})
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
