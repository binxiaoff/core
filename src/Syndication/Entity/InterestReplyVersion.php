<?php

namespace Unilend\Syndication\Entity;

use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Annotation\Groups;
use Symfony\Component\Validator\Constraints as Assert;
use Unilend\Core\Entity\Staff;
use Unilend\Core\Entity\Traits\BlamableAddedTrait;
use Unilend\Syndication\Entity\Embeddable\Offer;

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
     * @var Offer
     *
     * @ORM\Embedded(class="Unilend\Syndication\Entity\Embeddable\Offer")
     *
     * @Assert\Valid
     *
     * @Groups({"interestReplyVersion:read"})
     */
    private Offer $interestReply;

    /**
     * @var ProjectParticipation
     *
     * @ORM\ManyToOne(targetEntity="Unilend\Syndication\Entity\ProjectParticipation", inversedBy="interestReplyVersions")
     * @ORM\JoinColumn(name="id_project_participation", nullable=false)
     */
    private ProjectParticipation $projectParticipation;

    /**
     * @param ProjectParticipation $projectParticipation
     * @param Staff                $addedBy
     */
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

    /**
     * @return Offer
     */
    public function getInterestReply(): Offer
    {
        return $this->interestReply;
    }

    /**
     * @return ProjectParticipation
     */
    public function getProjectParticipation(): ProjectParticipation
    {
        return $this->projectParticipation;
    }
}
