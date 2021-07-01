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
use Unilend\Core\Controller\Dataroom\Delete;
use Unilend\Core\Controller\Dataroom\Get;
use Unilend\Core\Controller\Dataroom\Post;
use Unilend\Core\Entity\Constant\SyndicationModality\ParticipationType;
use Unilend\Core\Entity\Constant\SyndicationModality\RiskType;
use Unilend\Core\Entity\Constant\SyndicationModality\SyndicationType;
use Unilend\Core\Entity\Drive;
use Unilend\Core\Entity\Traits\PublicizeIdentityTrait;

/**
 * @ApiResource(
 *     normalizationContext={
 *         "groups": {
 *             "agency:participationPool:read",
 *             "money:read",
 *             "nullableMoney:read",
 *             "lendingRate:read"
 *         }
 *     },
 *     collectionOperations={},
 *     itemOperations={
 *         "get": {
 *             "security": "is_granted('view', object)"
 *         },
 *         "patch": {
 *             "security": "is_granted('edit', object)",
 *             "validation_groups": {ParticipationPool::class, "getCurrentValidationGroups"}
 *         },
 *         "get_dataroom": {
 *             "method": "GET",
 *             "path": "/agency/participation_pools/{publicId}/dataroom/{path?}",
 *             "security": "is_granted('view', object)",
 *             "controller": Get::class,
 *             "normalization_context": {
 *                 "groups": {"core:folder:read", "core:drive:read", "core:abstractFolder:read", "file:read"}
 *             },
 *             "requirements": {
 *                 "path": ".+"
 *             },
 *             "defaults": {
 *                 "path": "/"
 *             },
 *         },
 *         "post_dataroom": {
 *             "method": "POST",
 *             "deserialize": false,
 *             "path": "/agency/participation_pools/{publicId}/dataroom/{path?}",
 *             "security": "is_granted('view', object)",
 *             "controller": Post::class,
 *             "normalization_context": {
 *                 "groups": {"core:folder:read", "core:drive:read", "core:abstractFolder:read", "file:read"}
 *             },
 *             "requirements": {
 *                 "path": ".+"
 *             },
 *             "defaults": {
 *                 "path": "/"
 *             },
 *         },
 *         "delete_dataroom": {
 *             "method": "DELETE",
 *             "path": "/agency/participation_pools/{publicId}/dataroom/{path?}",
 *             "security": "is_granted('view', object)",
 *             "controller": Delete::class,
 *             "requirements": {
 *                 "path": ".+"
 *             },
 *             "defaults": {
 *                 "path": "/"
 *             },
 *         }
 *     }
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
class ParticipationPool
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
     * @Assert\NotBlank(groups={"ParticipationPool:published"})
     *
     * @Groups({"agency:participationPool:read", "agency:participationPool:write"})
     */
    private ?string $syndicationType;

    /**
     * @ORM\Column(type="string", length=30, nullable=true)
     *
     * @Assert\Choice(callback={ParticipationType::class, "getConstList"})
     * @Assert\NotBlank(groups={"ParticipationPool:published"})
     *
     * @Groups({"agency:participationPool:read", "agency:participationPool:write"})
     */
    private ?string $participationType;

    /**
     * @ORM\Column(type="string", nullable=true, length=30)
     *
     * @Assert\Choice(callback={RiskType::class, "getConstList"})
     * @Assert\Expression(
     *     expression="(false === this.isPrincipalSubParticipation() and null === value) or (this.isPrincipalSubParticipation() and value)",
     *     groups={"ParticipationPool:published"}
     * )
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
        $this->project        = $project;
        $this->participations = new ArrayCollection();
        $this->secondary      = $secondary;
        $this->sharedDrive    = new Drive();
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

    public function getCurrentValidationGroups(self $pool): array
    {
        $validationGroups = ['Default', 'ParticipationPool'];

        if ($pool->getProject()->isPublished()) {
            $validationGroups[] = 'ParticipationPool:published';
        }

        return $validationGroups;
    }

    public function getSharedDrive(): Drive
    {
        return $this->sharedDrive;
    }
}
