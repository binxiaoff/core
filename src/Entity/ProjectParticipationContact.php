<?php

declare(strict_types=1);

namespace Unilend\Entity;

use ApiPlatform\Core\Annotation\ApiResource;
use DateTimeImmutable;
use Doctrine\ORM\Mapping as ORM;
use Exception;
use Symfony\Component\Serializer\Annotation\Groups;
use Unilend\Entity\Traits\{BlamableAddedTrait, TimestampableAddedOnlyTrait};

/**
 * @ApiResource(
 *     denormalizationContext={"groups": {"projectParticipationContact:write"}},
 *     itemOperations={
 *         "get": {"security": "object.getClient() == user"},
 *         "patch": {"security_post_denormalize": "previous_object.getClient() == user"}
 *     }
 * )
 * @ORM\Table(uniqueConstraints={@ORM\UniqueConstraint(columns={"id_client", "id_project_participation"})})
 * @ORM\Entity
 * @ORM\HasLifecycleCallbacks
 */
class ProjectParticipationContact
{
    use TimestampableAddedOnlyTrait;
    use BlamableAddedTrait;

    /**
     * @var int
     *
     * @ORM\Column(type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="IDENTITY")
     */
    private $id;

    /**
     * @var ProjectParticipation
     *
     * @ORM\ManyToOne(targetEntity="Unilend\Entity\ProjectParticipation", inversedBy="projectParticipationContacts")
     * @ORM\JoinColumns({
     *     @ORM\JoinColumn(name="id_project_participation", nullable=false)
     * })
     */
    private $projectParticipation;

    /**
     * @var Clients
     *
     * @ORM\ManyToOne(targetEntity="Unilend\Entity\Clients")
     * @ORM\JoinColumns({
     *     @ORM\JoinColumn(name="id_client", referencedColumnName="id_client", nullable=false)
     * })
     *
     * @Groups({"projectParticipationContact:read"})
     */
    private $client;

    /**
     * @var DateTimeImmutable|null
     *
     * @ORM\Column(type="datetime_immutable", nullable=true)
     *
     * @Groups({"projectParticipationContact:read", "projectParticipationContact:write"})
     */
    private $confidentialityAccepted;

    /**
     * ProjectParticipationContact constructor.
     *
     * @param ProjectParticipation $projectParticipation
     * @param Clients              $clients
     * @param Clients|null         $addedBy
     *
     * @throws Exception
     */
    public function __construct(
        ProjectParticipation $projectParticipation,
        Clients $clients,
        Clients $addedBy
    ) {
        $this->projectParticipation    = $projectParticipation;
        $this->client                  = $clients;
        $this->addedBy                 = $addedBy;
        $this->added                   = new DateTimeImmutable();
        $this->confidentialityAccepted = false;
    }

    /**
     * @return int
     */
    public function getId(): int
    {
        return $this->id;
    }

    /**
     * @return ProjectParticipation
     */
    public function getProjectParticipation(): ProjectParticipation
    {
        return $this->projectParticipation;
    }

    /**
     * @param ProjectParticipation $projectParticipation
     *
     * @return ProjectParticipationContact
     */
    public function setProjectParticipation(ProjectParticipation $projectParticipation): ProjectParticipationContact
    {
        $this->projectParticipation = $projectParticipation;

        return $this;
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
    public function getConfidentialityAccepted(): ?DateTimeImmutable
    {
        return $this->confidentialityAccepted;
    }

    /**
     * @param DateTimeImmutable|null $confidentialityAccepted
     *
     * @return ProjectParticipationContact
     */
    public function setConfidentialityAccepted(?DateTimeImmutable $confidentialityAccepted): ProjectParticipationContact
    {
        $this->confidentialityAccepted = $confidentialityAccepted;

        return $this;
    }
}
