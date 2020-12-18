<?php

declare(strict_types=1);

namespace Unilend\Core\Entity;

use ApiPlatform\Core\Annotation\{ApiFilter, ApiResource};
use ApiPlatform\Core\Bridge\Doctrine\Orm\Filter\SearchFilter;
use DateTimeImmutable;
use Doctrine\Common\Collections\{ArrayCollection, Collection};
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Annotation\Groups;
use Unilend\Core\Entity\Traits\PublicizeIdentityTrait;
use Unilend\Core\Entity\Traits\TimestampableAddedOnlyTrait;
use Unilend\Syndication\Entity\ProjectParticipation;

/**
 * @ORM\Entity
 * @ORM\Table(name="core_message_thread")
 *
 * @ApiResource(
 *  attributes={
 *      "route_prefix"="/core"
 *  },
 *  normalizationContext={"groups": {
 *      "messageThread:read",
 *      "message:read",
 *      "messageFile:read",
 *      "projectParticipation:read",
 *      "projectParticipationMember:read",
 *      "project:read",
 *      "role:read",
 *      "company:read",
 *      "nullableMoney:read",
 *      "money:read",
 *      "rangedOfferWithFee:read",
 *      "offerWithFee:read",
 *      "offer:read",
 *      "archivable:read",
 *      "timestampable:read",
 *      "file:read",
 *      "fileVersion:read",
 *      "tranche:read",
 *      "lendingRate:read",
 *      "companyStatus:read",
 *      "role:read",
 *      "invitationReplyVersion:read",
 *      "interestReplyVersion:read",
 *      "client:read",
 *      "staff:read"
 *  }},
 *  collectionOperations={
 *  },
 *  itemOperations={
 *      "get": {
 *          "security": "is_granted('view', object)"
 *      }
 *  }
 * )
 * @ApiFilter(SearchFilter::class, properties={"projectParticipation": "exact"})
 */
class MessageThread
{
    use PublicizeIdentityTrait;
    use TimestampableAddedOnlyTrait;

    /**
     * @var ProjectParticipation|null
     *
     * @ORM\OneToOne(targetEntity="Unilend\Syndication\Entity\ProjectParticipation", inversedBy="messageThread")
     * @ORM\JoinColumn(name="id_project_participation", referencedColumnName="id")
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

    /**
     * MessageThread constructor.
     */
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
     * @param Message $message
     *
     * @return MessageThread
     */
    public function addMessage(Message $message): MessageThread
    {
        if (!$this->messages->contains($message)) {
            $this->messages->add($message);
        }

        return $this;
    }

    /**
     * @param ProjectParticipation|null $projectParticipation
     *
     * @return $this
     */
    public function setProjectParticipation(?ProjectParticipation $projectParticipation): self
    {
        $this->projectParticipation = $projectParticipation;

        if ($projectParticipation instanceof ProjectParticipation) {
            $projectParticipation->setMessageThread($this);
        }

        return $this;
    }

    /**
     * @return ProjectParticipation|null
     */
    public function getProjectParticipation(): ?ProjectParticipation
    {
        return $this->projectParticipation;
    }
}
