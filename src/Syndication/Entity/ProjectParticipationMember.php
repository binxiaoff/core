<?php

declare(strict_types=1);

namespace Unilend\Syndication\Entity;

use ApiPlatform\Core\Annotation\ApiResource;
use DateTimeImmutable;
use Doctrine\ORM\Mapping as ORM;
use Exception;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;
use Symfony\Component\Serializer\Annotation\Groups;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\Validator\Context\ExecutionContextInterface;
use Unilend\Core\DTO\AcceptedNDA;
use Unilend\Core\Entity\FileVersion;
use Unilend\Core\Entity\Staff;
use Unilend\Core\Entity\Traits\{ArchivableTrait, BlamableAddedTrait, BlamableArchivedTrait, PublicizeIdentityTrait, TimestampableAddedOnlyTrait};

/**
 * @ApiResource(
 *     normalizationContext={"groups": {"projectParticipationMember:read", "role:read"}},
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
     * @ORM\ManyToOne(targetEntity="Unilend\Syndication\Entity\ProjectParticipation", inversedBy="projectParticipationMembers")
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
     * @ORM\ManyToOne(targetEntity="Unilend\Core\Entity\Staff", cascade={"persist"})
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
     * @Groups({"projectParticipationMember:read"})
     */
    private ?DateTimeImmutable $ndaAccepted = null;

    /**
     * @var FileVersion|null
     *
     * @ORM\ManyToOne(targetEntity="Unilend\Core\Entity\FileVersion")
     * @ORM\JoinColumn(name="id_accepted_nda_version")
     *
     * @Groups({"projectParticipationMember:read"})
     */
    private ?FileVersion $acceptedNdaVersion = null;

    /**
     * @var string|null
     *
     * @ORM\Column(type="text", length=65535, nullable=true)
     *
     * @Groups({"projectParticipationMember:read"})
     */
    private ?string $acceptedNdaTerm = null;

    /**
     * @var array
     */
    private array $violations = [];

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
     * @return FileVersion|null
     */
    public function getAcceptedNdaVersion(): ?FileVersion
    {
        return $this->acceptedNdaVersion;
    }

    /**
     * @param AcceptedNDA $acceptedNDA
     *
     * @throws Exception
     *
     * @return ProjectParticipationMember
     *
     * @Groups({"projectParticipationMember:owner:write"})
     */
    public function setAcceptedNda(AcceptedNDA $acceptedNDA): ProjectParticipationMember
    {
        // Save the violation in $this->violations temporarily, so that it can be translated later in @Assert\Callback
        if ($this->acceptedNdaVersion) {
            // acceptedNdaVersion is only settable once
            $this->violations[] = ['path' => 'acceptableNdaVersion', 'message' => 'ProjectParticipationMember.acceptedNdaVersion.accepted'];
        }

        if (null === $this->getAcceptableNdaVersion()) {
            // The acceptable version is not available
            $this->violations[] = ['path' => 'acceptableNdaVersion', 'message' => 'ProjectParticipationMember.acceptableNdaVersion.empty'];
        }

        if ($acceptedNDA->getFileVersionId() !== $this->getAcceptableNdaVersion()->getPublicId()) {
            // We can only accept the acceptable version
            $this->violations[] = ['path' => 'acceptedNdaVersion', 'message' => 'ProjectParticipationMember.acceptedNdaVersion.unacceptableVersion'];
        }

        if (
            null === $this->acceptedNdaVersion
            && null !== $this->getAcceptableNdaVersion()
            && $acceptedNDA->getFileVersionId() === $this->getAcceptableNdaVersion()->getPublicId()
        ) {
            $this->acceptedNdaVersion = $this->getAcceptableNdaVersion();
            $this->acceptedNdaTerm    = $acceptedNDA->getTerm();
            $this->ndaAccepted        = new DateTimeImmutable();
        }

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

    /**
     * @return string|null
     */
    public function getAcceptedNdaTerm(): ?string
    {
        return $this->acceptedNdaTerm;
    }

    /**
     * @return FileVersion|null
     *
     * @Groups({"projectParticipationMember:read"})
     */
    public function getAcceptableNdaVersion(): ?FileVersion
    {
        $file = $this->projectParticipation->getNda() ?? $this->getProjectParticipation()->getProject()->getNda();

        return $file ? $file->getCurrentFileVersion() : null;
    }

    /**
     * @Groups({"projectParticipationMember:read"})
     *
     * @return string
     */
    public function getMemberName(): string
    {
        $firstName = $this->staff->getClient()->getFirstName();
        $lastName = $this->staff->getClient()->getLastName();
        $email = $this->staff->getClient()->getEmail();

        return (!$firstName || !$lastName) ? $email : ($firstName . ' ' . $lastName);
    }

    /**
     * @Assert\Callback
     *
     * @param ExecutionContextInterface $context
     */
    public function validateArchived(ExecutionContextInterface $context): void
    {
        if ($this->isArchived()) {
            if ($this->getProjectParticipation()->getActiveProjectParticipationMembers()->count() < 1) {
                $context->buildViolation('ProjectParticipationMember.archived.lastActiveMember')
                    ->atPath('archived')
                    ->addViolation()
                ;
            }
            if ($this->getStaff()->isManager()) {
                $context->buildViolation('ProjectParticipationMember.archived.isManager')
                    ->atPath('archived')
                    ->addViolation()
                ;
            }
        }
    }

    /**
     * @Assert\Callback
     *
     * @param ExecutionContextInterface $context
     */
    public function validate(ExecutionContextInterface $context): void
    {
        foreach ($this->violations as $violation) {
            $context->buildViolation($violation['message'])->atPath($violation['path'])->addViolation()
            ;
        }
    }
}
