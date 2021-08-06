<?php

declare(strict_types=1);

namespace Unilend\Core\Entity;

use ApiPlatform\Core\Annotation\ApiFilter;
use ApiPlatform\Core\Annotation\ApiProperty;
use ApiPlatform\Core\Annotation\ApiResource;
use ApiPlatform\Core\Bridge\Doctrine\Orm\Filter\SearchFilter;
use DateTimeImmutable;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Annotation\Groups;
use Unilend\Core\Entity\Traits\PublicizeIdentityTrait;
use Unilend\Core\Entity\Traits\TimestampableAddedOnlyTrait;
use Unilend\Syndication\Entity\Project;
use Unilend\Syndication\Entity\ProjectParticipation;

/**
 * @ORM\Entity
 * @ORM\Table(name="core_message_thread")
 *
 * @ApiResource(
 *     attributes={"pagination_enabled": false},
 *     normalizationContext={"groups": {
 *         "messageThread:read",
 *         "message:read",
 *         "messageStatus:read",
 *         "messageFile:read",
 *         "staff:read",
 *         "company:read",
 *         "timestampable:read",
 *         "file:read",
 *         "user:read",
 *         "fileVersion:read",
 *         "companyGroupTag:read"
 *     }},
 *     itemOperations={
 *         "get": {
 *             "security": "is_granted('view', object)"
 *         }
 *     },
 *     collectionOperations={
 *         "get"
 *     }
 * )
 * @ApiFilter(SearchFilter::class, properties={"projectParticipation.publicId": "exact"})
 */
class MessageThread
{
    use PublicizeIdentityTrait;
    use TimestampableAddedOnlyTrait;

    /**
     * @ORM\OneToOne(targetEntity="Unilend\Syndication\Entity\ProjectParticipation")
     * @ORM\JoinColumn(name="id_project_participation", referencedColumnName="id")
     *
     * @ApiProperty(readableLink=false, writableLink=false)
     *
     * @Groups({"messageThread:read"})
     */
    private ?ProjectParticipation $projectParticipation = null;

    /**
     * @var ArrayCollection|Collection
     *
     * @ORM\OneToMany(targetEntity="Unilend\Core\Entity\Message", mappedBy="messageThread")
     * @ORM\OrderBy({"added": "ASC"})
     *
     * @Groups({"messageThread:read"})
     */
    private Collection $messages;

    public function __construct()
    {
        $this->added    = new DateTimeImmutable();
        $this->messages = new ArrayCollection();
    }

    /**
     * @return Message[]|Collection
     */
    public function getMessages(): Collection
    {
        return $this->messages;
    }

    /**
     * @return $this
     */
    public function setProjectParticipation(?ProjectParticipation $projectParticipation): self
    {
        $this->projectParticipation = $projectParticipation;

        return $this;
    }

    public function getProjectParticipation(): ?ProjectParticipation
    {
        return $this->projectParticipation;
    }

    /**
     * @ApiProperty(readableLink=false, writableLink=false)
     *
     * @Groups({"messageThread:read"})
     */
    public function getProject(): Project
    {
        return $this->projectParticipation->getProject();
    }

    /**
     * @Groups({"messageThread:read"})
     */
    public function getProjectTitle(): string
    {
        return $this->projectParticipation->getProject()->getTitle();
    }

    /**
     * @Groups({"messageThread:read"})
     */
    public function getParticipantName(): string
    {
        return $this->projectParticipation->getParticipant()->getDisplayName();
    }

    /**
     * @ApiProperty(readableLink=false, writableLink=false)
     *
     * @Groups({"messageThread:read"})
     */
    public function getParticipant(): Company
    {
        return $this->projectParticipation->getParticipant();
    }

    /**
     * @ApiProperty(readableLink=false, writableLink=false)
     *
     * @Groups({"messageThread:read"})
     */
    public function getProjectSubmitterCompany(): Company
    {
        return $this->projectParticipation->getProject()->getSubmitterCompany();
    }

    /**
     * @Groups({"messageThread:read"})
     */
    public function getProjectSubmitterCompanyName(): string
    {
        return $this->projectParticipation->getProject()->getSubmitterCompany()->getDisplayName();
    }
}
