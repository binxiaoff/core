<?php

declare(strict_types=1);

namespace KLS\Syndication\Agency\Entity;

use ApiPlatform\Core\Annotation\ApiFilter;
use ApiPlatform\Core\Annotation\ApiProperty;
use ApiPlatform\Core\Annotation\ApiResource;
use ApiPlatform\Core\Serializer\Filter\GroupFilter;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use KLS\Core\Controller\Dataroom\Delete;
use KLS\Core\Controller\Dataroom\Get;
use KLS\Core\Controller\Dataroom\Post;
use KLS\Core\Entity\Drive;
use KLS\Core\Entity\Interfaces\DriveCarrierInterface;
use KLS\Core\Entity\Interfaces\MoneyInterface;
use KLS\Core\Entity\Traits\PublicizeIdentityTrait;
use KLS\Core\Service\MoneyCalculator;
use KLS\Syndication\Common\Constant\Modality\ParticipationType;
use KLS\Syndication\Common\Constant\Modality\RiskType;
use KLS\Syndication\Common\Constant\Modality\SyndicationType;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;
use Symfony\Component\Serializer\Annotation\Groups;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * @ApiResource(
 *     normalizationContext={
 *         "groups": {
 *             "agency:participationPool:read",
 *             "money:read",
 *             "nullableMoney:read",
 *             "lendingRate:read",
 *         },
 *         "openapi_definition_name": "read",
 *     },
 *     collectionOperations={},
 *     itemOperations={
 *         "get": {
 *             "security": "is_granted('view', object)",
 *         },
 *         "patch": {
 *             "security": "is_granted('edit', object)",
 *             "validation_groups": {ParticipationPool::class, "getCurrentValidationGroups"},
 *         },
 *         "get_dataroom": {
 *             "method": "GET",
 *             "path": "/agency/participation_pools/{publicId}/dataroom/{path?}",
 *             "security": "is_granted('view', object)",
 *             "controller": Get::class,
 *             "normalization_context": {
 *                 "groups": {
 *                     "core:folder:read",
 *                     "core:drive:read",
 *                     "core:abstractFolder:read",
 *                     "file:read",
 *                 },
 *                 "openapi_definition_name": "read",
 *             },
 *             "requirements": {
 *                 "path": ".+",
 *             },
 *             "defaults": {
 *                 "path": "/",
 *                 "drive": "sharedDrive",
 *             },
 *         },
 *         "post_dataroom": {
 *             "method": "POST",
 *             "deserialize": false,
 *             "path": "/agency/participation_pools/{publicId}/dataroom/{path?}",
 *             "security": "is_granted('edit', object)",
 *             "controller": Post::class,
 *             "normalization_context": {
 *                 "groups": {
 *                     "core:folder:read",
 *                     "core:drive:read",
 *                     "core:abstractFolder:read",
 *                     "file:read",
 *                 },
 *                 "openapi_definition_name": "read",
 *             },
 *             "requirements": {
 *                 "path": ".+",
 *             },
 *             "defaults": {
 *                 "path": "/",
 *                 "drive": "sharedDrive",
 *             },
 *         },
 *         "delete_dataroom": {
 *             "method": "DELETE",
 *             "path": "/agency/participation_pools/{publicId}/dataroom/{path?}",
 *             "security": "is_granted('edit', object)",
 *             "controller": Delete::class,
 *             "requirements": {
 *                 "path": ".+",
 *             },
 *             "defaults": {
 *                 "path": "/",
 *                 "drive": "sharedDrive",
 *             },
 *         },
 *     },
 * )
 *
 * @ORM\Table(name="agency_participation_pool", uniqueConstraints={
 *     @ORM\UniqueConstraint(name="uniq_participant_pool_project_secondary", columns={"id_project", "secondary"})
 * })
 * @ORM\Entity
 *
 * @ApiFilter(
 *     filterClass=GroupFilter::class,
 *     arguments={
 *         "whitelist": {
 *             "company:read",
 *             "agency:participation:read",
 *             "agency:participationTrancheAllocation:read",
 *             "agency:tranche:read"
 *         }
 *     }
 * )
 *
 * @UniqueEntity(fields={"project", "secondary"})
 */
class ParticipationPool implements DriveCarrierInterface
{
    use PublicizeIdentityTrait;

    /**
     * @ORM\ManyToOne(targetEntity=Project::class, inversedBy="participationPools")
     * @ORM\JoinColumn(name="id_project", nullable=false, onDelete="CASCADE")
     *
     * @Assert\NotBlank
     *
     * @Groups({"agency:participationPool:read"})
     *
     * @ApiProperty(readableLink=false, writableLink=false)
     */
    private Project $project;

    /**
     * @ORM\OneToMany(targetEntity=Participation::class, mappedBy="pool", cascade={"persist", "remove"})
     *
     * @Assert\All({
     *     @Assert\Expression("value.getPool() === this")
     * })
     * @Assert\Valid
     *
     * @Groups({"agency:participationPool:read"})
     */
    private Collection $participations;

    /**
     * @ORM\Column(type="string", length=30, nullable=true)
     *
     * @Assert\Choice(callback={SyndicationType::class, "getConstList"})
     * @Assert\NotBlank(groups={"ParticipationPool:published:active"})
     *
     * @Groups({"agency:participationPool:read", "agency:participationPool:write"})
     */
    private ?string $syndicationType;

    /**
     * @ORM\Column(type="string", length=30, nullable=true)
     *
     * @Assert\Choice(callback={ParticipationType::class, "getConstList"})
     * @Assert\NotBlank(groups={"ParticipationPool:published:active"})
     *
     * @Groups({"agency:participationPool:read", "agency:participationPool:write"})
     */
    private ?string $participationType;

    /**
     * @ORM\Column(type="string", nullable=true, length=30)
     *
     * @Assert\Choice(callback={RiskType::class, "getConstList"})
     * @Assert\AtLeastOneOf({
     *     @Assert\Expression("this.isSubParticipation()"),
     *     @Assert\Expression("false === this.isSubParticipation() && null === value")
     * }, groups={"ParticipationPool:published:active"})
     *
     * @Groups({"agency:participationPool:read", "agency:participationPool:write"})
     */
    private ?string $riskType;

    /**
     * @ORM\Column(type="boolean", nullable=false)
     *
     * @Groups({"agency:participationPool:read"})
     */
    private bool $secondary;

    /**
     * @ORM\OneToOne(targetEntity=Drive::class, cascade={"persist", "remove"})
     * @ORM\JoinColumn(nullable=false, unique=true)
     */
    private Drive $sharedDrive;

    public function __construct(Project $project, bool $secondary)
    {
        $this->project           = $project;
        $this->participations    = new ArrayCollection();
        $this->secondary         = $secondary;
        $this->sharedDrive       = new Drive();
        $this->riskType          = null;
        $this->syndicationType   = null;
        $this->participationType = null;
    }

    public function getProject(): Project
    {
        return $this->project;
    }

    /**
     * @return ArrayCollection|iterable
     */
    public function getParticipations(): Collection
    {
        return $this->participations;
    }

    public function addParticipation(Participation $participation): ParticipationPool
    {
        if ($this->project->findParticipationByParticipant($participation->getParticipant())) {
            return $this;
        }

        $this->participations->add($participation);

        return $this;
    }

    public function removeParticipation(Participation $participation): ParticipationPool
    {
        $this->participations->removeElement($participation);

        return $this;
    }

    public function getSyndicationType(): ?string
    {
        return $this->syndicationType;
    }

    public function setSyndicationType(?string $syndicationType): ParticipationPool
    {
        $this->syndicationType = $syndicationType;

        return $this;
    }

    public function getParticipationType(): ?string
    {
        return $this->participationType;
    }

    public function setParticipationType(?string $participationType): ParticipationPool
    {
        $this->participationType = $participationType;

        if ($this->riskType && false === $this->isSubParticipation()) {
            $this->riskType = null;
        }

        return $this;
    }

    public function getRiskType(): ?string
    {
        return $this->riskType;
    }

    public function setRiskType(?string $riskType): ParticipationPool
    {
        $this->riskType = $riskType;

        return $this;
    }

    public function isSecondary(): bool
    {
        return $this->secondary;
    }

    public function isPrimary(): bool
    {
        return false === $this->isSecondary();
    }

    public function isSubParticipation(): bool
    {
        return ParticipationType::SUB_PARTICIPATION === $this->participationType;
    }

    public function isEmpty(): bool
    {
        return 0 === \count($this->getParticipations());
    }

    public static function getCurrentValidationGroups(self $pool): array
    {
        $validationGroups = ['Default', 'ParticipationPool'];

        if ($pool->getProject()->isPublished() && ($pool->isPrimary() || ($pool->isSecondary() && false === $pool->isEmpty()))) {
            $validationGroups[] = 'ParticipationPool:published:active';
        }

        return $validationGroups;
    }

    public function getSharedDrive(): Drive
    {
        return $this->sharedDrive;
    }

    public function getAllocationSum(): MoneyInterface
    {
        return MoneyCalculator::sum(
            $this->participations->map(fn (Participation $participation) => $participation->getFinalAllocation())->toArray()
        );
    }

    public function getActiveParticipantAllocationSum(): MoneyInterface
    {
        return MoneyCalculator::sum(
            $this->participations
                ->filter(fn (Participation $participation) => false === $participation->isArchived())
                ->map(fn (Participation $participation)    => $participation->getFinalAllocation())
                ->toArray()
        );
    }
}
