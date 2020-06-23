<?php

declare(strict_types=1);

namespace Unilend\Entity;

use ApiPlatform\Core\Annotation\ApiResource;
use DateTimeImmutable;
use Doctrine\ORM\Mapping as ORM;
use Exception;
use Symfony\Component\Serializer\Annotation\Groups;
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
 * @ORM\Table(uniqueConstraints={@ORM\UniqueConstraint(columns={"id_client", "id_project_participation"})})
 * @ORM\Entity(repositoryClass="Unilend\Repository\ProjectParticipationContactRepository")
 * @ORM\HasLifecycleCallbacks
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
     */
    private $projectParticipation;

    /**
     * @var Clients
     *
     * @ORM\ManyToOne(targetEntity="Unilend\Entity\Clients")
     * @ORM\JoinColumns({
     *     @ORM\JoinColumn(name="id_client", referencedColumnName="id", nullable=false)
     * })
     *
     * @Groups({"projectParticipationContact:read", "projectParticipationContact:create"})
     */
    private $client;

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
     * @param Clients              $client
     * @param Staff                $addedBy
     *
     * @throws Exception
     */
    public function __construct(
        ProjectParticipation $projectParticipation,
        Clients $client,
        Staff $addedBy
    ) {
        $this->projectParticipation = $projectParticipation;
        $this->client               = $client;
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
     * @return Clients
     */
    public function getClient(): Clients
    {
        return $this->client;
    }

    /**
     * @param Clients $client
     *
     * @return ProjectParticipationContact
     */
    public function setClient(Clients $client): ProjectParticipationContact
    {
        $this->client = $client;

        return $this;
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
}
