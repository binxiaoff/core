<?php

declare(strict_types=1);

namespace KLS\Syndication\Entity;

use Doctrine\ORM\Mapping as ORM;
use KLS\Core\Entity\Staff;
use KLS\Core\Entity\Traits\BlamableAddedTrait;
use KLS\Syndication\Entity\Embeddable\Offer;
use Symfony\Component\Serializer\Annotation\Groups;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * @ORM\Entity
 * @ORM\Table(name="syndication_interest_reply_version")
 */
class InterestReplyVersion
{
    use BlamableAddedTrait;

    /**
     * @ORM\Id
     * @ORM\GeneratedValue
     * @ORM\Column(type="integer")
     */
    private int $id;

    /**
     * @ORM\Embedded(class="KLS\Syndication\Entity\Embeddable\Offer")
     *
     * @Assert\Valid
     *
     * @Groups({"interestReplyVersion:read"})
     */
    private Offer $interestReply;

    /**
     * @ORM\ManyToOne(targetEntity="KLS\Syndication\Entity\ProjectParticipation", inversedBy="interestReplyVersions")
     * @ORM\JoinColumn(name="id_project_participation", nullable=false)
     */
    private ProjectParticipation $projectParticipation;

    public function __construct(ProjectParticipation $projectParticipation, Staff $addedBy)
    {
        $this->projectParticipation = $projectParticipation;
        $this->interestReply        = $projectParticipation->getInterestReply();
        $this->addedBy              = $addedBy;
    }

    /**
     * @return mixed
     */
    public function getId()
    {
        return $this->id;
    }

    public function getInterestReply(): Offer
    {
        return $this->interestReply;
    }

    public function getProjectParticipation(): ProjectParticipation
    {
        return $this->projectParticipation;
    }
}
