<?php

declare(strict_types=1);

namespace Unilend\Agency\Entity;

use ApiPlatform\Core\Action\NotFoundAction;
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
 *              "denormalization_context": {
 *                  "groups": {"agency:participation:create", "money:write", "agency:participationTrancheAllocation:write"}
 *              },
 *             "security_post_denormalize": "is_granted('create', object)",
 *         }
 *     },
 *     itemOperations={
 *         "get",
 *         "patch": {
 *              "denormalization_context": {
 *                  "groups": {"agency:participation:update", "money:write", "agency:participationTrancheAllocation:write"}
 *              },
 *             "security_post_denormalize": "is_granted('edit', object)",
 *         }
 *     }
 * )
 * @ORM\Entity()
 * @ORM\Table(name="agency_participation", uniqueConstraints={
 *    @ORM\UniqueConstraint(columns={"id_project", "id_participant"})
 * })
 *
 * @UniqueEntity(fields={"project", "participant"})
 *
 * @ApiFilter(
 *     filterClass=GroupFilter::class,
 *     arguments={
 *         "whitelist": {
 *             "agency:participationTrancheAllocation:read",
 *             "company:read"
 *         }
 *     }
 * )
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
     * @var Company
     *
     * @ORM\ManyToOne(targetEntity=Company::class)
     * @ORM\JoinColumn(name="id_participant", nullable=false)
     *
     * @Groups({"agency:participation:read", "agency:participation:create"})
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
     *
     * @Groups({"agency:participation:update", "agency:participation:read", "agency:participation:create"})
     */
    private ?string $participantCommission;

    /**
     * @var Bitmask
     *
     * @ORM\Column(type="bitmask", nullable=false)
     *
     * @Assert\Expression(expression="false === (0 === value && this.isSecondary())", message="Agency.Participation.responsabilities.secondary")
     * @Assert\Expression(expression="false === (this.isArranger() && this.isDeputyArranger())", message="Agency.Participation.responsabilities.arranger")
     * @Assert\Expression(
     *     expression="(this.isAgent() && (this.getParticipant() === this.getProject().getAgent())) || (false === this.isAgent())",
     *     message="Agency.Participation.responsabilities.agent"
     * )
     *
     * @Groups({"agency:participation:update", "agency:participation:read", "agency:participation:create"})
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
     * @Assert\Expression(expression="(null === value && false === this.isAgent()) || (null !== value && this.isAgent())", message="Agency.Participation.commission.agent")
     *
     * @Groups({"agency:participation:update", "agency:participation:read", "agency:participation:create"})
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
     *
     * @Assert\Expression(expression="null === value || false === this.isArranger()", message="Agency.Participant.commission.arranger")
     *
     * @Groups({"agency:participation:update", "agency:participation:read", "agency:participation:create"})
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
     *
     * @Assert\Expression(expression="null === value || false === this.isCoArranger()", message="Agency.Participant.commission.deputyArranger")
     *
     * @Groups({"agency:participation:update", "agency:participation:read", "agency:participation:create"})
     */
    private ?string $deputyArrangerCommission;

    /**
     * @var Money
     *
     * @ORM\Embedded(class="Unilend\Core\Entity\Embeddable\Money")
     *
     * @Groups({"agency:participation:update", "agency:participation:read", "agency:participation:create"})
     *
     * @Assert\Valid
     */
    private Money $finalAllocation;

    /**
     * @var bool
     *
     * @ORM\Column(type="boolean")
     *
     * @Groups({"agency:participation:update", "agency:participation:read", "agency:participation:create"})
     */
    private bool $prorata;

    /**
     * @var ParticipationTrancheAllocation[]|iterable
     *
     * @ORM\OneToMany(targetEntity=ParticipationTrancheAllocation::class, cascade={"persist", "remove"}, mappedBy="participation")
     *
     * @Assert\Count(min="1", groups={"published"})
     * @Assert\Valid
     * @Assert\All({
     *    @Assert\Expression("value.getParticipation() === this")
     * })
     *
     * @Groups({"agency:participation:update", "agency:participation:read", "agency:participation:create"})
     */
    private iterable $allocations;

    /**
     * @var bool
     *
     * @ORM\Column(type="boolean")
     *
     * @Groups({"agency:participation:update", "agency:participation:read", "agency:participation:create"})
     */
    private bool $secondary;

    /**
     * @var ParticipationMember|null
     *
     * @ORM\ManyToOne(targetEntity=ParticipationMember::class)
     * @ORM\JoinColumn(name="id_referent", onDelete="SET NULL")
     *
     * @Assert\NotBlank(groups="published")
     * @Assert\Choice(callback="getMembers")
     * @Assert\Valid
     *
     * @Groups({"agency:participation:read"})
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
     */
    private Collection $members;

    /**
     * @param Project $project
     * @param Company $participant
     * @param Money   $finalAllocation
     * @param bool    $secondary
     */
    public function __construct(
        Project $project,
        Company $participant,
        Money $finalAllocation,
        bool $secondary = false
    ) {
        $this->responsibilities = new Bitmask(0);
        $this->project = $project;
        $this->finalAllocation = $finalAllocation;
        $this->participant = $participant;
        $this->secondary = $secondary;
        $this->prorata = false;
        $this->participantCommission = '0';
        $this->arrangerCommission = null;
        $this->agentCommission = null;
        $this->deputyArrangerCommission = null;
        $this->allocations = new ArrayCollection();
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
    public function setAllocations(iterable $allocations)
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

    /**
     * @param ParticipationMember $member
     *
     * @return Participation
     */
    public function addMember(ParticipationMember $member): Participation
    {
        if (null === $this->findMemberByUser($member->getUser())) {
            $this->members[] = $member;
        }

        return $this;
    }

    /**
     * @param ParticipationMember $member
     *
     * @return Participation
     */
    public function removeMember(ParticipationMember $member): Participation
    {
        $this->members->removeElement($member);

        if ($this->referent === $member) {
            $this->referent = null;
        }

        return $this;
    }

    /**
     * @param User $user
     *
     * @return ParticipationMember|null
     */
    public function findMemberByUser(User $user): ?ParticipationMember
    {
        foreach ($this->members as $member) {
            if ($member->getUser() === $user) {
                return $member;
            }
        }

        return null;
    }

    /**
     * @return ParticipationMember|null
     */
    public function getReferent(): ?ParticipationMember
    {
        return $this->referent;
    }


    /**
     * @param ParticipationMember $referent
     *
     * @return Participation
     */
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
     * Must be static : https://api-platform.com/docs/core/validation/#dynamic-validation-groups
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

    /**
     * @param string $type
     *
     * @return iterable|ParticipationMember[]
     */
    private function getMemberByType(string $type): iterable
    {
        return $this->members->filter(fn (ParticipationMember $member) => $type === $member->getType())->toArray();
    }
}
