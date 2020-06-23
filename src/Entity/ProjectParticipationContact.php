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
 *             "denormalization_context": {"groups": {}}
 *         }
 *     },
 *     collectionOperations={
 *         "post": {
 *             "security_post_denormalize": "is_granted('create', object)",
 *             "denormalization_context": {"groups": {"projectParticipationContact:create", "projectParticipationContact:write"}}
 *         }
 *     }
 * )
 * @ORM\Table(uniqueConstraints={@ORM\UniqueConstraint(columns={"id_staff", "id_project_participation"})})
 * @ORM\Entity(repositoryClass="Unilend\Repository\ProjectParticipationContactRepository")
 * @ORM\HasLifecycleCallbacks
 *
 * @UniqueEntity({"staff", "projectParticipation"})
 */
class ProjectParticipationContact
{
    use TimestampableAddedOnlyTrait;
    use BlamableAddedTrait;
    use PublicizeIdentityTrait;
    use ArchivableTrait;
    use BlamableArchivedTrait;

    public const SERIALIZER_GROUP_PROJECT_PARTICIPATION_CONTACT_OWNER_WRITE = 'projectParticipationContact:owner:write';

    /**
     * @var ProjectParticipation
     *
     * @ORM\ManyToOne(targetEntity="Unilend\Entity\ProjectParticipation", inversedBy="projectParticipationContacts")
     * @ORM\JoinColumn(name="id_project_participation", nullable=false, onDelete="CASCADE")
     *
     * @Groups({"projectParticipationContact:create"})
     *
     * @Assert\NotBlank
     */
    private $projectParticipation;

    /**
     * @var Staff
     *
     * @ORM\ManyToOne(targetEntity="Unilend\Entity\Staff")
     * @ORM\JoinColumns({
     *     @ORM\JoinColumn(name="id_staff", referencedColumnName="id", nullable=false)
     * })
     *
     * @Groups({"projectParticipationContact:read", "projectParticipationContact:create"})
     *
     * @Assert\NotBlank
     * @Assert\Expression(
     *     expression="this.getStaff().getCompany() === this.getProjectParticipation().getParticipant()",
     *     message="ProjectParticipationContact.staff.incorrectCompany"
     * )
     */
    private $staff;

    /**
     * @var DateTimeImmutable|null
     *
     * @ORM\Column(type="datetime_immutable", nullable=true)
     *
     * @Groups({"projectParticipationContact:read", "projectParticipationContact:write", "projectParticipationContact:owner:write"})
     */
    private $ndaAccepted;

    /**
     * @ORM\ManyToOne(targetEntity="Unilend\Entity\FileVersion")
     * @ORM\JoinColumn(name="id_accepted_nda_version")
     *
     * @Groups({"projectParticipationContact:owner:write"})
     */
    private $acceptedNdaVersion;

    /**
     * ProjectParticipationContact constructor.
     *
     * @param ProjectParticipation $projectParticipation
     * @param Staff                $staff
     * @param Staff                $addedBy
     *
     * @throws Exception
     */
    public function __construct(
        ProjectParticipation $projectParticipation,
        Staff $staff,
        Staff $addedBy
    ) {
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
     * @return ProjectParticipationContact
     */
    public function setNdaAccepted(?DateTimeImmutable $ndaAccepted): ProjectParticipationContact
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
    public function setAcceptedNdaVersion(?FileVersion $acceptedNdaVersion): ProjectParticipationContact
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
