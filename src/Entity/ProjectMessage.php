<?php

declare(strict_types=1);

namespace Unilend\Entity;

use ApiPlatform\Core\Annotation\{ApiFilter, ApiResource};
use DateTimeImmutable;
use Doctrine\ORM\Mapping as ORM;
use Exception;
use Gedmo\Mapping\Annotation as Gedmo;
use Symfony\Component\Serializer\Annotation\Groups;
use Unilend\Entity\Traits\{BlamableAddedTrait, PublicizeIdentityTrait, TimestampableTrait};

/**
 * @ApiResource(
 *     attributes={"order": {"added", "id"}, "pagination_enabled": false},
 *     normalizationContext={"groups": {"message:read", "blameable:read", "client:read", "timestampable:read"}},
 *     denormalizationContext={"groups": {"message:write"}},
 *     collectionOperations={
 *         "post": {"security_post_denormalize": "is_granted('create', object)", "denormalization_context": {"groups": {"message:create", "message:write"}}}
 *     },
 *     itemOperations={
 *         "get": {
 *             "controller": "ApiPlatform\Core\Action\NotFoundAction",
 *             "read": false,
 *             "output": false,
 *         },
 *         "patch": {"security_post_denormalize": "is_granted('edit', previous_object)"},
 *         "delete": {"security": "is_granted('delete', object)"},
 *     }
 * )
 *
 * @ApiFilter("ApiPlatform\Core\Bridge\Doctrine\Orm\Filter\OrderFilter", properties={"added": "ASC"})
 *
 * @ORM\Entity
 *
 * @Gedmo\SoftDeleteable(fieldName="archived", hardDelete=false)
 */
class ProjectMessage
{
    use TimestampableTrait;
    use BlamableAddedTrait;
    use PublicizeIdentityTrait;

    /**
     * @var ProjectParticipation
     *
     * @ORM\ManyToOne(targetEntity="Unilend\Entity\ProjectParticipation", inversedBy="messages")
     * @ORM\JoinColumn(nullable=false, name="id_participation")
     *
     * @Groups({"message:create"})
     */
    private $participation;

    /**
     * @var string
     *
     * @ORM\Column(type="text")
     *
     * @Groups({"message:read", "message:write"})
     */
    private $content;

    /**
     * @var DateTimeImmutable|null
     *
     * @ORM\Column(type="datetime_immutable", nullable=true)
     */
    private $archived;

    /**
     * @param ProjectParticipation $participation
     * @param Staff                $addedBy
     * @param string               $content
     *
     * @throws Exception
     */
    public function __construct(ProjectParticipation $participation, Staff $addedBy, string $content)
    {
        $this->participation = $participation;
        $this->addedBy       = $addedBy;
        $this->added         = new DateTimeImmutable();
        $this->content       = $content;
    }

    /**
     * @return string
     */
    public function getContent(): string
    {
        return $this->content;
    }

    /**
     * @param string $content
     *
     * @return ProjectMessage
     */
    public function setContent(string $content): ProjectMessage
    {
        $this->content = $content;

        return $this;
    }

    /**
     * @return ProjectParticipation
     */
    public function getParticipation(): ProjectParticipation
    {
        return $this->participation;
    }

    /**
     * @return DateTimeImmutable
     */
    public function getArchived(): ?DateTimeImmutable
    {
        return $this->archived;
    }

    /**
     * @param DateTimeImmutable $archived
     *
     * @return ProjectMessage
     */
    public function setArchived(?DateTimeImmutable $archived): ProjectMessage
    {
        $this->archived = $archived;

        return $this;
    }

    /**
     * @return bool
     *
     * @Groups({"message:view"})
     */
    public function isModified(): bool
    {
        return null !== $this->updated;
    }
}
