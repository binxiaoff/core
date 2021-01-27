<?php

declare(strict_types=1);

namespace Unilend\Agency\Entity;

use Doctrine\ORM\Mapping as ORM;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;
use Symfony\Component\Validator\Constraints as Assert;
use Unilend\Core\DTO\Bitmask;
use Unilend\Core\Entity\Company;
use Unilend\Core\Entity\Embeddable\Money;
use Unilend\Core\Entity\Traits\PublicizeIdentityTrait;

/**
 * @ORM\Entity()
 * @ORM\Table(name="agency_participation", uniqueConstraints={
 *    @ORM\UniqueConstraint(columns={"id_project", "id_participant"})
 * })
 *
 * @UniqueEntity(fields={"project", "participant"})
 */
class Participation
{
    use PublicizeIdentityTrait;

    public const RESPONSIBILITY_AGENT = 1 << 0;
    public const RESPONSIBILITY_ARRANGER = 1 << 1;
    public const RESPONSIBILITY_DEPUTY_ARRANGER = 1 << 2;

    /**
     * @var Project
     *
     * @ORM\ManyToOne(targetEntity=Project::class)
     * @ORM\JoinColumn(name="id_project", nullable=false)
     *
     * @Assert\NotBlank
     */
    private Project $project;

    /**
     * @var Company
     *
     * @ORM\ManyToOne(targetEntity=Company::class)
     * @ORM\JoinColumn(name="id_participant", nullable=false)
     *
     * @Assert\NotBlank
     */
    private Company $participant;

    /**
     * @var string|null
     *
     * @ORM\Column(type="decimal", precision=5, scale=4, nullable=true)
     *
     * @Assert\NotBlank(allowNull=true)
     * @Assert\Type("numeric")
     * @Assert\PositiveOrZero
     */
    private ?string $participantCommission;

    /**
     * @var Bitmask
     *
     * @ORM\Column(type="bitmask", nullable=false)
     *
     * @Assert\Expression(expression="false === (0 !== value && this.isSecondary())", message="Agency.Participant.responsabilities.secondary")
     * @Assert\Expression(expression="false === (this.isArranger() && this.isDeputyArranger())", message="Agency.Participation.responsabilities.arranger")
     * @Assert\Expression(
     *     expression="(this.isAgent() && this.getParticipant() === this.getProject().getAgent()) || (false === this.isAgent())",
     *     message="Agency.Participation.responsabilities.agent"
     * )
     */
    private Bitmask $responsibilities;

    /**
     * @var string|null
     *
     * @ORM\Column(type="decimal", precision=5, scale=4, nullable=true)
     *
     * @Assert\NotBlank(allowNull=true)
     * @Assert\Type("numeric")
     * @Assert\PositiveOrZero
     * @Assert\Expression(expression="null === value || false === this.isAgent()", message="Agency.Participant.commission.agent")
     */
    private ?string $agentCommission;

    /**
     * @var string|null
     *
     * @ORM\Column(type="decimal", precision=5, scale=4, nullable=true)
     *
     * @Assert\NotBlank(allowNull=true)
     * @Assert\Type("numeric")
     * @Assert\PositiveOrZero
     * @Assert\Expression(expression="null === value || false === this.isArranger()", message="Agency.Participant.commission.arranger")
     */
    private ?string $arrangerCommission;

    /**
     * @var string|null
     *
     * @ORM\Column(type="decimal", precision=5, scale=4, nullable=true)
     *
     * @Assert\NotBlank(allowNull=true)
     * @Assert\Type("numeric")
     * @Assert\PositiveOrZero
     * @Assert\Expression(expression="null === value || false === this.isCoArranger()", message="Agency.Participant.commission.deputyArranger")
     */
    private ?string $deputyArrangerCommission;

    /**
     * @var Money
     *
     * @ORM\Embedded(class="Unilend\Core\Entity\Embeddable\Money")
     *
     * @Assert\Valid
     */
    private Money $finalAllocation;

    /**
     * @var bool
     *
     * @ORM\Column(type="boolean")
     */
    private bool $prorata;

    /**
     * @var ParticipationTrancheAllocation[]|iterable
     *
     * @ORM\OneToMany(targetEntity=ParticipationTrancheAllocation::class, cascade={"persist", "remove"}, mappedBy="participation")
     *
     * @Assert\Count(min="1")
     * @Assert\Valid
     */
    private iterable $allocations;

    /**
     * @var bool
     *
     * @ORM\Column(type="boolean")
     */
    private bool $secondary;

    /**
     * @param Project $project
     * @param Company $company
     * @param Money   $finalAllocation
     * @param bool    $secondary
     */
    public function __construct(
        Project $project,
        Company $company,
        Money $finalAllocation,
        bool $secondary = false
    ) {
        $this->responsibilities = new Bitmask(0);
        $this->project = $project;
        $this->finalAllocation = $finalAllocation;
        $this->participant = $company;
        $this->secondary = $secondary;
        $this->prorata = false;
    }

    /**
     * @return Company
     */
    public function getParticipant(): Company
    {
        return $this->participant;
    }

    /**
     * @return Project
     */
    public function getProject(): Project
    {
        return $this->project;
    }

    /**
     * @return Bitmask
     */
    public function getResponsibilities(): Bitmask
    {
        return $this->responsibilities;
    }

    /**
     * @param Bitmask $responsibilities
     *
     * @return Participation
     */
    public function setResponsibilities(Bitmask $responsibilities): Participation
    {
        $this->responsibilities = $responsibilities;

        return $this;
    }

    /**
     * @return bool
     */
    public function isAgent(): bool
    {
        return $this->responsibilities->has(static::RESPONSIBILITY_AGENT);
    }

    /**
     * @return string|null
     */
    public function getAgentCommission(): ?string
    {
        return $this->agentCommission;
    }

    /**
     * @param string|null $agentCommission
     *
     * @return Participation
     */
    public function setAgentCommission(?string $agentCommission): Participation
    {
        $this->agentCommission = $agentCommission;

        return $this;
    }

    /**
     * @return bool
     */
    public function isArranger(): bool
    {
        return $this->responsibilities->has(static::RESPONSIBILITY_ARRANGER);
    }

    /**
     * @return string|null
     */
    public function getArrangerCommission(): ?string
    {
        return $this->arrangerCommission;
    }

    /**
     * @param string|null $arrangerCommission
     *
     * @return Participation
     */
    public function setArrangerCommission(?string $arrangerCommission): Participation
    {
        $this->arrangerCommission = $arrangerCommission;

        return $this;
    }

    /**
     * @return bool
     */
    public function isDeputyArranger(): bool
    {
        return $this->responsibilities->has(static::RESPONSIBILITY_DEPUTY_ARRANGER);
    }

    /**
     * @return string|null
     */
    public function getDeputyArrangerCommission(): ?string
    {
        return $this->deputyArrangerCommission;
    }

    /**
     * @param string|null $deputyArrangerCommission
     *
     * @return Participation
     */
    public function setDeputyArrangerCommission(?string $deputyArrangerCommission): Participation
    {
        $this->deputyArrangerCommission = $deputyArrangerCommission;

        return $this;
    }

    /**
     * @return iterable|ParticipationTrancheAllocation[]
     */
    public function getAllocations()
    {
        return $this->allocations;
    }

    /**
     * @param iterable|ParticipationTrancheAllocation[] $allocations
     *
     * @return Participation
     */
    public function setAllocations($allocations)
    {
        $this->allocations = $allocations;

        return $this;
    }

    /**
     * @return bool
     */
    public function isSecondary(): bool
    {
        return $this->secondary;
    }

    /**
     * @return string|null
     */
    public function getParticipantCommission(): ?string
    {
        return $this->participantCommission;
    }

    /**
     * @param string|null $participantCommission
     *
     * @return Participation
     */
    public function setParticipantCommission(?string $participantCommission): Participation
    {
        $this->participantCommission = $participantCommission;

        return $this;
    }

    /**
     * @return Money
     */
    public function getFinalAllocation(): Money
    {
        return $this->finalAllocation;
    }

    /**
     * @param Money $finalAllocation
     *
     * @return Participation
     */
    public function setFinalAllocation(Money $finalAllocation): Participation
    {
        $this->finalAllocation = $finalAllocation;

        return $this;
    }

    /**
     * @return bool
     */
    public function isProrata(): bool
    {
        return $this->prorata;
    }

    /**
     * @param bool $prorata
     *
     * @return Participation
     */
    public function setProrata(bool $prorata): Participation
    {
        $this->prorata = $prorata;

        return $this;
    }
}
