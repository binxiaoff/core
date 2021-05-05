<?php

declare(strict_types=1);

namespace Unilend\Agency\Entity;

use ApiPlatform\Core\Annotation\ApiFilter;
use ApiPlatform\Core\Annotation\ApiProperty;
use ApiPlatform\Core\Annotation\ApiResource;
use ApiPlatform\Core\Serializer\Filter\GroupFilter;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;
use Symfony\Component\Serializer\Annotation\Groups;
use Symfony\Component\Validator\Constraints as Assert;
use Unilend\Core\Entity\Company;
use Unilend\Core\Entity\Embeddable\Money;
use Unilend\Core\Entity\Traits\PublicizeIdentityTrait;
use Unilend\Core\Entity\User;
use Unilend\Core\Model\Bitmask;

/**
 * @ApiResource(
 *     attributes={
 *         "validation_groups": {Participation::class, "getCurrentValidationGroups"}
 *     },
 *     normalizationContext={
 *         "groups": {
 *             "agency:participation:read",
 *             "money:read"
 *         }
 *     },
 *     collectionOperations={
 *         "post": {
 *             "denormalization_context": {
 *                 "groups": {"agency:participation:create", "agency:participation:write", "money:write", "agency:participationTrancheAllocation:write"}
 *             },
 *             "security_post_denormalize": "is_granted('create', object)",
 *         }
 *     },
 *     itemOperations={
 *         "get",
 *         "patch": {
 *             "denormalization_context": {
 *                 "groups": {"agency:participation:update", "agency:participation:write", "money:write", "agency:participationTrancheAllocation:write"}
 *             },
 *             "security_post_denormalize": "is_granted('edit', object)",
 *         }
 *     }
 * )
 * @ORM\Entity
 * @ORM\Table(name="agency_participation", uniqueConstraints={
 *     @ORM\UniqueConstraint(columns={"id_project", "id_participant"})
 * })
 *
 * @UniqueEntity(fields={"project", "participant"})
 *
 * @ApiFilter(
 *     filterClass=GroupFilter::class,
 *     arguments={
 *         "whitelist": {
 *             "agency:participationTrancheAllocation:read",
 *             "agency:participationMember:read",
 *             "company:read",
 *             "user:read"
 *         }
 *     }
 * )
 */
class Participation
{
    use PublicizeIdentityTrait;

    public const RESPONSIBILITY_AGENT           = 1 << 0;
    public const RESPONSIBILITY_ARRANGER        = 1 << 1;
    public const RESPONSIBILITY_DEPUTY_ARRANGER = 1 << 2;

    /**
     * @ORM\ManyToOne(targetEntity=Project::class, inversedBy="participations")
     * @ORM\JoinColumn(name="id_project", nullable=false)
     *
     * @Groups({"agency:participation:read", "agency:participation:create"})
     *
     * @Assert\NotBlank
     *
     * @ApiProperty(readableLink=false)
     */
    private Project $project;

    /**
     * @ORM\ManyToOne(targetEntity=Company::class)
     * @ORM\JoinColumn(name="id_participant", nullable=false)
     *
     * @Groups({"agency:participation:read", "agency:participation:create"})
     *
     * @Assert\NotBlank
     */
    private Company $participant;

    /**
     * @ORM\Column(type="decimal", precision=5, scale=4, nullable=true)
     *
     * @Assert\NotBlank(allowNull=true)
     * @Assert\Type("numeric")
     * @Assert\PositiveOrZero
     *
     * @Groups({"agency:participation:read", "agency:participation:write"})
     */
    private ?string $participantCommission;

    /**
     * @ORM\Column(type="bitmask", nullable=false)
     *
     * @Assert\Expression(expression="false === (0 === value && this.isSecondary())", message="Agency.Participation.responsabilities.secondary")
     * @Assert\Expression(expression="false === (this.isArranger() && this.isDeputyArranger())", message="Agency.Participation.responsabilities.arranger")
     * @Assert\Expression(
     *     expression="(this.isAgent() && (this.getParticipant() === this.getProject().getAgent())) || (false === this.isAgent())",
     *     message="Agency.Participation.responsabilities.agent"
     * )
     *
     * @Groups({"agency:participation:read", "agency:participation:write"})
     */
    private Bitmask $responsibilities;

    /**
     * @ORM\Column(type="decimal", precision=5, scale=4, nullable=true)
     *
     * @Assert\NotBlank(allowNull=true)
     * @Assert\Type("numeric")
     * @Assert\PositiveOrZero
     * @Assert\Expression(expression="(null === value && false === this.isAgent()) || (null !== value && this.isAgent())", message="Agency.Participation.commission.agent")
     *
     * @Groups({"agency:participation:read", "agency:participation:write"})
     */
    private ?string $agentCommission;

    /**
     * @ORM\Column(type="decimal", precision=5, scale=4, nullable=true)
     *
     * @Assert\NotBlank(allowNull=true)
     * @Assert\Type("numeric")
     * @Assert\PositiveOrZero
     *
     * @Assert\Expression(expression="null === value || false === this.isArranger()", message="Agency.Participant.commission.arranger")
     *
     * @Groups({"agency:participation:read", "agency:participation:write"})
     */
    private ?string $arrangerCommission;

    /**
     * @ORM\Column(type="decimal", precision=5, scale=4, nullable=true)
     *
     * @Assert\NotBlank(allowNull=true)
     * @Assert\Type("numeric")
     * @Assert\PositiveOrZero
     *
     * @Assert\Expression(expression="null === value || false === this.isCoArranger()", message="Agency.Participant.commission.deputyArranger")
     *
     * @Groups({"agency:participation:read", "agency:participation:write"})
     */
    private ?string $deputyArrangerCommission;

    /**
     * @ORM\Embedded(class="Unilend\Core\Entity\Embeddable\Money")
     *
     * @Groups({"agency:participation:read", "agency:participation:write"})
     *
     * @Assert\Valid
     */
    private Money $finalAllocation;

    /**
     * @ORM\Column(type="boolean")
     *
     * @Groups({"agency:participation:read", "agency:participation:write"})
     */
    private bool $prorata;

    /**
     * @var ParticipationTrancheAllocation[]|Collection
     *
     * @ORM\OneToMany(targetEntity=ParticipationTrancheAllocation::class, cascade={"persist", "remove"}, mappedBy="participation")
     *
     * @Assert\Count(min="1", groups={"published"})
     * @Assert\Valid
     * @Assert\All({
     *     @Assert\Expression("value.getParticipation() === this")
     * })
     *
     * @Groups({"agency:participation:read", "agency:participation:write"})
     */
    private Collection $allocations;

    /**
     * @ORM\Column(type="boolean")
     *
     * @Groups({"agency:participation:read", "agency:participation:write"})
     */
    private bool $secondary;

    /**
     * @ORM\OneToOne(targetEntity=ParticipationMember::class)
     * @ORM\JoinColumn(name="id_referent", onDelete="SET NULL")
     *
     * @Assert\NotBlank(groups="published")
     * @Assert\Choice(callback="getMembers")
     * @Assert\Valid
     *
     * @Groups({"agency:participation:read", "agency:participation:write"})
     */
    private ?ParticipationMember $referent;

    /**
     * @var Collection|ParticipationMember[]
     *
     * @ORM\OneToMany(targetEntity=ParticipationMember::class, mappedBy="participation", cascade={"persist", "remove"}, orphanRemoval=true)
     *
     * @Assert\Valid
     * @Assert\All({
     *     @Assert\Expression("value.getParticipation() === this")
     * })
     *
     * @Groups({"agency:participation:read"})
     */
    private Collection $members;

    public function __construct(
        Project $project,
        Company $participant,
        Money $finalAllocation,
        bool $secondary = false
    ) {
        $this->responsibilities         = new Bitmask(0);
        $this->project                  = $project;
        $this->finalAllocation          = $finalAllocation;
        $this->participant              = $participant;
        $this->secondary                = $secondary;
        $this->prorata                  = false;
        $this->participantCommission    = '0';
        $this->arrangerCommission       = null;
        $this->agentCommission          = null;
        $this->deputyArrangerCommission = null;
        $this->allocations              = new ArrayCollection();
        $this->members                  = new ArrayCollection();
    }

    public function getParticipant(): Company
    {
        return $this->participant;
    }

    public function getProject(): Project
    {
        return $this->project;
    }

    public function getResponsibilities(): Bitmask
    {
        return $this->responsibilities;
    }

    public function setResponsibilities(Bitmask $responsibilities): Participation
    {
        $this->responsibilities = $responsibilities;

        return $this;
    }

    public function isAgent(): bool
    {
        return $this->responsibilities->has(static::RESPONSIBILITY_AGENT);
    }

    public function getAgentCommission(): ?string
    {
        return $this->agentCommission;
    }

    public function setAgentCommission(?string $agentCommission): Participation
    {
        $this->agentCommission = $agentCommission;

        return $this;
    }

    public function isArranger(): bool
    {
        return $this->responsibilities->has(static::RESPONSIBILITY_ARRANGER);
    }

    public function getArrangerCommission(): ?string
    {
        return $this->arrangerCommission;
    }

    public function setArrangerCommission(?string $arrangerCommission): Participation
    {
        $this->arrangerCommission = $arrangerCommission;

        return $this;
    }

    public function isDeputyArranger(): bool
    {
        return $this->responsibilities->has(static::RESPONSIBILITY_DEPUTY_ARRANGER);
    }

    public function getDeputyArrangerCommission(): ?string
    {
        return $this->deputyArrangerCommission;
    }

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
    public function setAllocations(iterable $allocations)
    {
        $this->allocations = $allocations;

        return $this;
    }

    public function isSecondary(): bool
    {
        return $this->secondary;
    }

    public function getParticipantCommission(): ?string
    {
        return $this->participantCommission;
    }

    public function setParticipantCommission(?string $participantCommission): Participation
    {
        $this->participantCommission = $participantCommission;

        return $this;
    }

    public function getFinalAllocation(): Money
    {
        return $this->finalAllocation;
    }

    public function setFinalAllocation(Money $finalAllocation): Participation
    {
        $this->finalAllocation = $finalAllocation;

        return $this;
    }

    public function isProrata(): bool
    {
        return $this->prorata;
    }

    public function setProrata(bool $prorata): Participation
    {
        $this->prorata = $prorata;

        return $this;
    }

    /**
     * @return array|ParticipationMember[]
     */
    public function getMembers(): array
    {
        return $this->members->toArray();
    }

    /**
     * @param iterable|ParticipationMember[] $members
     *
     * @return Participation
     */
    public function setMembers(iterable $members)
    {
        $this->members = $members;

        return $this;
    }

    public function addMember(ParticipationMember $member): Participation
    {
        if (null === $this->findMemberByUser($member->getUser())) {
            $this->members[] = $member;
        }

        return $this;
    }

    public function removeMember(ParticipationMember $member): Participation
    {
        if ($this->members->removeElement($member)) {
            // Do not merge into parent if construct because we need the removal to take place
            if ($this->referent === $member) {
                $this->referent = null;
            }
        }

        return $this;
    }

    public function findMemberByUser(User $user): ?ParticipationMember
    {
        foreach ($this->members as $member) {
            if ($member->getUser() === $user) {
                return $member;
            }
        }

        return null;
    }

    public function getReferent(): ?ParticipationMember
    {
        return $this->referent;
    }

    public function setReferent(ParticipationMember $referent): Participation
    {
        $this->referent = $this->findMemberByUser($referent->getUser()) ?? $referent;
        $this->addMember($this->referent);

        return $this;
    }

    /**
     * @return iterable|ParticipationMember[]
     *
     * @Groups({"agency:participation:read"})
     */
    public function getBackOfficeMembers(): iterable
    {
        return $this->getMemberByType(ParticipationMember::TYPE_BACK_OFFICE);
    }

    /**
     * @return iterable|ParticipationMember[]
     *
     * @Groups({"agency:participation:read"})
     */
    public function getLegalMembers(): iterable
    {
        return $this->getMemberByType(ParticipationMember::TYPE_LEGAL);
    }

    /**
     * @return iterable|ParticipationMember[]
     *
     * @Groups({"agency:participation:read"})
     */
    public function getWaiverMembers(): iterable
    {
        return $this->getMemberByType(ParticipationMember::TYPE_WAIVER);
    }

    /**
     * Must be static : https://api-platform.com/docs/core/validation/#dynamic-validation-groups.
     *
     * @param Participation $participation
     *
     * @return array|string[]
     */
    public static function getCurrentValidationGroups(self $participation): array
    {
        $validationGroups = ['Default', 'Project'];

        if ($participation->getProject()->isPublished()) {
            $validationGroups[] = ['published'];
        }

        return $validationGroups;
    }

    public function addAllocation(ParticipationTrancheAllocation $participationTrancheAllocation): Participation
    {
        if (
            false === $this->allocations->exists(
                fn ($key, ParticipationTrancheAllocation $item) => $item->getTranche() === $participationTrancheAllocation->getTranche()
            )
        ) {
            $this->allocations->add($participationTrancheAllocation);
            $participationTrancheAllocation->getTranche()->addAllocation($participationTrancheAllocation);
        }

        return $this;
    }

    public function removeAllocation(ParticipationTrancheAllocation $participationTrancheAllocation): Participation
    {
        $this->allocations->removeElement($participationTrancheAllocation);

        return $this;
    }

    /**
     * @return iterable|ParticipationMember[]
     */
    private function getMemberByType(string $type): iterable
    {
        return $this->members->filter(fn (ParticipationMember $member) => $type === $member->getType())->toArray();
    }
}
