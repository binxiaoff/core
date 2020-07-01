<?php

declare(strict_types=1);

namespace Unilend\Entity;

use ApiPlatform\Core\Annotation\ApiResource;
use DateTimeImmutable;
use Doctrine\ORM\Mapping as ORM;
use Exception;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;
use Symfony\Component\Serializer\Annotation\Groups;
use Symfony\Component\Validator\Constraints as Assert;
use Unilend\Entity\Traits\{ArchivableTrait, BlamableAddedTrait, BlamableArchivedTrait, PublicizeIdentityTrait, TimestampableAddedOnlyTrait};

/**
 * @ApiResource(
 *     itemOperations={
 *         "get": {
 *             "controller": "ApiPlatform\Core\Action\NotFoundAction",
 *             "read": false,
 *             "output": false,
 *         },
 *         "patch": {
 *             "security": "is_granted('edit', object)",
 *             "denormalization_context": {"groups": {"archivable:write"}}
 *         }
 *     },
 *     collectionOperations={
 *         "post": {
 *             "security_post_denormalize": "is_granted('create', object)",
 *             "denormalization_context": {"groups": {"projectParticipationMember:create", "projectParticipationMember:write"}}
 *         }
 *     }
 * )
 * @ORM\Table(uniqueConstraints={@ORM\UniqueConstraint(columns={"id_staff", "id_project_participation"})})
 * @ORM\Entity
 * @ORM\HasLifecycleCallbacks
 *
 * @UniqueEntity({"staff", "projectParticipation"})
 */
class ProjectParticipationMember
{
    use TimestampableAddedOnlyTrait;
    use BlamableAddedTrait;
    use PublicizeIdentityTrait;
    use ArchivableTrait;
    use BlamableArchivedTrait;

    public const SERIALIZER_GROUP_PROJECT_PARTICIPATION_MEMBER_OWNER_WRITE = 'projectParticipationMember:owner:write';

    /**
     * @var ProjectParticipation
     *
     * @ORM\ManyToOne(targetEntity="Unilend\Entity\ProjectParticipation", inversedBy="projectParticipationMembers")
     * @ORM\JoinColumn(name="id_project_participation", nullable=false, onDelete="CASCADE")
     *
     * @Groups({"projectParticipationMember:create"})
     *
     * @Assert\NotBlank
     */
    private ProjectParticipation $projectParticipation;

    /**
     * @var Staff
     *
     * @ORM\ManyToOne(targetEntity="Unilend\Entity\Staff")
     * @ORM\JoinColumns({
     *     @ORM\JoinColumn(name="id_staff", referencedColumnName="id", nullable=false)
     * })
     *
     * @Groups({"projectParticipationMember:read", "projectParticipationMember:create"})
     *
     * @Assert\NotBlank
     * @Assert\Expression(
     *     expression="this.getStaff().getCompany() === this.getProjectParticipation().getParticipant()",
     *     message="ProjectParticipationMember.staff.incorrectCompany"
     * )
     */
    private Staff $staff;

    /**
     * @var DateTimeImmutable|null
     *
     * @ORM\Column(type="datetime_immutable", nullable=true)
     *
     * @Groups({"projectParticipationMember:read", "projectParticipationMember:write", "projectParticipationMember:owner:write"})
     */
    private ?DateTimeImmutable $ndaAccepted;

    /**
     * @var FileVersion|null
     *
     * @ORM\ManyToOne(targetEntity="Unilend\Entity\FileVersion")
     * @ORM\JoinColumn(name="id_accepted_nda_version")
     *
     * @Groups({"projectParticipationMember:owner:write"})
     */
    private ?FileVersion $acceptedNdaVersion;

    /**
     * @param ProjectParticipation $projectParticipation
     * @param Staff                $staff
     * @param Staff                $addedBy
     *
     * @throws Exception
     */
    public function __construct(ProjectParticipation $projectParticipation, Staff $staff, Staff $addedBy)
    {
        $this->projectParticipation = $projectParticipation;
        $this->staff                = $staff;
        $this->addedBy              = $addedBy;
        $this->added                = new DateTimeImmutable();
    }

    /**
     * @return ProjectParticipation
     */
    public function getProjectParticipation(): ProjectParticipation
    {
        return $this->projectParticipation;
    }

    /**
     * @return DateTimeImmutable|null
     */
    public function getNdaAccepted(): ?DateTimeImmutable
    {
        return $this->ndaAccepted;
    }

    /**
     * @param DateTimeImmutable|null $ndaAccepted
     *
     * @return ProjectParticipationMember
     */
    public function setNdaAccepted(?DateTimeImmutable $ndaAccepted): ProjectParticipationMember
    {
        $this->ndaAccepted = $ndaAccepted;

        return $this;
    }

    /**
     * @return FileVersion|null
     */
    public function getAcceptedNdaVersion(): ?FileVersion
    {
        return $this->acceptedNdaVersion;
    }

    /**
     * @param FileVersion|null $acceptedNdaVersion
     *
     * @return $this
     */
    public function setAcceptedNdaVersion(?FileVersion $acceptedNdaVersion): ProjectParticipationMember
    {
        $this->acceptedNdaVersion = $acceptedNdaVersion;

        return $this;
    }

    /**
     * @return bool
     */
    public function isArchived(): bool
    {
        return null !== $this->archived;
    }

    /**
     * @return Staff
     */
    public function getStaff(): Staff
    {
        return $this->staff;
    }
}
