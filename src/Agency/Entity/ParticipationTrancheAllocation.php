<?php

declare(strict_types=1);

namespace Unilend\Agency\Entity;

use Doctrine\ORM\Mapping as ORM;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;
use Symfony\Component\Validator\Constraints as Assert;
use Unilend\Core\Entity\Embeddable\Money;

/**
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
    /**
     * @var int|null
     *
     * @ORM\Column(type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    private ?int $id;

    /**
     * @var Participation
     *
     * @ORM\ManyToOne(targetEntity=Participation::class)
     * @ORM\JoinColumn(name="id_participation", nullable=false, onDelete="CASCADE")
     *
     * @Assert\NotBlank
     */
    private Participation $participation;

    /**
     * @var Tranche
     *
     * @ORM\ManyToOne(targetEntity=Tranche::class)
     * @ORM\JoinColumn(name="id_tranche", nullable=false, onDelete="CASCADE")
     *
     * @Assert\NotBlank
     */
    private Tranche $tranche;

    /**
     * @var Money
     *
     * @Assert\NotBlank
     * @Assert\Valid
     *
     * @ORM\Embedded(class=Money::class)
     */
    private Money $allocation;

    /**
     * @param Participation $participant
     * @param Tranche       $tranche
     * @param Money         $allocation
     */
    public function __construct(Participation $participant, Tranche $tranche, Money $allocation)
    {
        $this->participation = $participant;
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
