<?php

declare(strict_types=1);

namespace Unilend\Agency\Entity;

use ApiPlatform\Core\Annotation\ApiResource;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;
use Symfony\Component\Serializer\Annotation\Groups;
use Symfony\Component\Validator\Constraints as Assert;
use Unilend\Core\Entity\Embeddable\Money;
use Unilend\Core\Entity\Traits\PublicizeIdentityTrait;

/**
 * @ApiResource(
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
 *      @ORM\UniqueConstraint(columns={"id_participation", "id_tranche"})
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
     * @var Participation
     *
     * @ORM\ManyToOne(targetEntity=Participation::class, inversedBy="allocations")
     * @ORM\JoinColumn(name="id_participation", nullable=false, onDelete="CASCADE")
     *
     * @Assert\NotBlank
     *
     * @Groups({"agency:participationTrancheAllocation:read", "agency:participationTrancheAllocation:create"})
     */
    private Participation $participation;

    /**
     * @var Tranche
     *
     * @ORM\ManyToOne(targetEntity=Tranche::class, inversedBy="allocations")
     * @ORM\JoinColumn(name="id_tranche", nullable=false, onDelete="CASCADE")
     *
     * @Assert\NotBlank
     * @Assert\Expression("value.isSyndicated()")
     *
     * @Groups({"agency:participationTrancheAllocation:read", "agency:participationTrancheAllocation:create"})
     */
    private Tranche $tranche;

    /**
     * @var Money
     *
     * @Assert\NotBlank
     * @Assert\Valid
     *
     * @ORM\Embedded(class=Money::class)
     *
     * @Groups({"agency:participationTrancheAllocation:read", "agency:participationTrancheAllocation:create"})
     */
    private Money $allocation;

    /**
     * @param Participation $participation
     * @param Tranche       $tranche
     * @param Money         $allocation
     */
    public function __construct(Participation $participation, Tranche $tranche, Money $allocation)
    {
        $this->participation = $participation;
        $this->tranche = $tranche;
        $this->allocation = $allocation;
    }

    /**
     * @return int|null
     */
    public function getId(): ?int
    {
        return $this->id;
    }

    /**
     * @return Participation
     */
    public function getParticipation(): Participation
    {
        return $this->participation;
    }

    /**
     * @return Tranche
     */
    public function getTranche(): Tranche
    {
        return $this->tranche;
    }

    /**
     * @return Money
     */
    public function getAllocation(): Money
    {
        return $this->allocation;
    }

    /**
     * @param Money $allocation
     *
     * @return ParticipationTrancheAllocation
     */
    public function setAllocation(Money $allocation): ParticipationTrancheAllocation
    {
        $this->allocation = $allocation;

        return $this;
    }
}
