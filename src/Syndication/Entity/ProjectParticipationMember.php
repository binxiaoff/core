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
use Unilend\Core\Entity\CompanyGroupTag;
use Unilend\Core\Entity\FileVersion;
use Unilend\Core\Entity\Staff;
use Unilend\Core\Entity\Traits\ArchivableTrait;
use Unilend\Core\Entity\Traits\BlamableAddedTrait;
use Unilend\Core\Entity\Traits\BlamableArchivedTrait;
use Unilend\Core\Entity\Traits\PermissionTrait;
use Unilend\Core\Entity\Traits\PublicizeIdentityTrait;
use Unilend\Core\Entity\Traits\TimestampableAddedOnlyTrait;
use Unilend\Core\Model\Bitmask;

/**
 * @ApiResource(
 *     normalizationContext={"groups": {"projectParticipationMember:read", "archivable:read", "companyGroupTag:read", "permission:read"}},
 *     itemOperations={
 *         "get": {
 *             "controller": "ApiPlatform\Core\Action\NotFoundAction",
 *             "read": false,
 *             "output": false,
 *         },
 *         "patch": {
 *             "security": "is_granted('edit', object)",
 *             "denormalization_context": {"groups": {"archivable:write", "permission:write"}}
 *         }
 *     },
 *     collectionOperations={
 *         "post": {
 *             "security_post_denormalize": "is_granted('create', object)",
 *             "denormalization_context": {"groups": {"projectParticipationMember:create", "projectParticipationMember:write", "permission:write"}}
 *         }
 *     }
 * )
 * @ORM\Table(
 *     name="syndication_project_participation_member",
 *     uniqueConstraints={@ORM\UniqueConstraint(columns={"id_staff", "id_project_participation"})}
 * )
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
    use PermissionTrait;

    public const PERMISSION_READ  = 0;
    public const PERMISSION_WRITE = 1 << 0;

    /**
     * @ORM\ManyToOne(targetEntity="Unilend\Syndication\Entity\ProjectParticipation", inversedBy="projectParticipationMembers")
     * @ORM\JoinColumn(name="id_project_participation", nullable=false, onDelete="CASCADE")
     *
     * @Groups({"projectParticipationMember:create"})
     *
     * @Assert\NotBlank
     */
    private ProjectParticipation $projectParticipation;

    /**
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
     *     message="Syndication.ProjectParticipationMember.staff.incorrectCompany"
     * )
     */
    private Staff $staff;

    /**
     * @throws Exception
     */
    public function __construct(ProjectParticipation $projectParticipation, Staff $staff, Staff $addedBy)
    {
        $this->projectParticipation = $projectParticipation;
        $this->staff                = $staff;
        $this->addedBy              = $addedBy;
        $this->added                = new DateTimeImmutable();
        $this->permissions          = new Bitmask(0);
    }

    public function getProjectParticipation(): ProjectParticipation
    {
        return $this->projectParticipation;
    }

    /**
     * @deprecated
     *
     * @Groups({"projectParticipationMember:read"})
     */
    public function getNdaAccepted(): ?DateTimeImmutable
    {
        $ndaSignature = $this->getNDASignature();

        return $ndaSignature ? $ndaSignature->getAdded() : null;
    }

    /**
     * @deprecated
     *
     * @Groups({"projectParticipationMember:read"})
     */
    public function getAcceptedNDAVersion(): ?FileVersion
    {
        $ndaSignature = $this->getNDASignature();

        return $ndaSignature ? $ndaSignature->getFileVersion() : null;
    }

    /**
     * @Groups({"projectParticipationMember:read"})
     */
    public function getAcceptableNdaVersion(): ?FileVersion
    {
        $file = $this->projectParticipation->getNda() ?? $this->getProjectParticipation()->getProject()->getNda();

        return $file ? $file->getCurrentFileVersion() : null;
    }

    public function isArchived(): bool
    {
        return null !== $this->archived;
    }

    public function getStaff(): Staff
    {
        return $this->staff;
    }

    /**
     * @deprecated
     *
     * @Groups({"projectParticipationMember:read"})
     */
    public function getAcceptedNdaTerm(): ?string
    {
        $ndaSignature = $this->getNDASignature();

        return $ndaSignature ? $ndaSignature->getTerm() : null;
    }

    /**
     * @deprecated
     *
     * @return NDASignature
     */
    public function getNDASignature(): ?NDASignature
    {
        foreach ($this->projectParticipation->getNDASignatures() as $signature) {
            if ($signature->getSignatory() === $this->getStaff()) {
                return $signature;
            }
        }

        return null;
    }

    /**
     * @Groups({"projectParticipationMember:read"})
     */
    public function getMemberName(): string
    {
        $firstName = $this->staff->getUser()->getFirstName();
        $lastName  = $this->staff->getUser()->getLastName();
        $email     = $this->staff->getUser()->getEmail();

        return (!$firstName || !$lastName) ? $email : ($firstName . ' ' . $lastName);
    }

    /**
     * @Groups({"projectParticipationMember:read"})
     */
    public function isManager(): bool
    {
        return $this->staff->isManager();
    }

    /**
     * @return iterable|CompanyGroupTag[]
     *
     * @Groups({"projectParticipationMember:read"})
     */
    public function getCompanyGroupTags(): iterable
    {
        return $this->staff->getCompanyGroupTags();
    }

    /**
     * @Assert\Callback
     */
    public function validateArchived(ExecutionContextInterface $context): void
    {
        if ($this->isArchived() && $this->getProjectParticipation()->getActiveProjectParticipationMembers()->count() < 1) {
            $context->buildViolation('ProjectParticipationMember.archived.lastActiveMember')
                ->atPath('archived')
                ->addViolation()
            ;
        }
    }
}
