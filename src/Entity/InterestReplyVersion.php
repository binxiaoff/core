<?php

namespace Unilend\Entity;

use ApiPlatform\Core\Annotation\ApiResource;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Annotation\Groups;
use Symfony\Component\Validator\Constraints as Assert;
use Unilend\Entity\Embeddable\Offer;
use Unilend\Entity\Traits\BlamableAddedTrait;

/**
 * @ORM\Entity
 *
 * @ApiResource(
 *     normalizationContext={"groups": {"interestReplyVersion:read"}},
 *     itemOperations={
 *         "get": {
 *             "controller": "ApiPlatform\Core\Action\NotFoundAction",
 *             "read": false,
 *             "output": false,
 *         }
 *     },
 *     collectionOperations={}
 * )
 */

class InterestReplyVersion
{
    use BlamableAddedTrait;

    /**
     * @ORM\Id
     * @ORM\GeneratedValue
     * @ORM\Column(type="integer")
     */
    public $id;

    /**
     * @var Offer
     *
     * @ORM\Embedded(class="Unilend\Entity\Embeddable\Offer")
     *
     * @Assert\Valid
     *
     * @Groups({"interestReplyVersion:read"})
     */
    private Offer $interestReply;

    /**
     * @var ProjectParticipation
     *
     * @ORM\ManyToOne(targetEntity="Unilend\Entity\ProjectParticipation", inversedBy="interestReplyVersions")
     * @ORM\JoinColumn(name="id_project_participation", nullable=false)
     */
    private ProjectParticipation $projectParticipation;

    /**
     * @param Offer                $interestReply
     * @param ProjectParticipation $projectParticipation
     * @param Staff                $addedBy
     */
    public function __construct(Offer $interestReply, ProjectParticipation $projectParticipation, Staff $addedBy)
    {
        $this->interestReply        = $interestReply;
        $this->projectParticipation = $projectParticipation;
        $this->addedBy              = $addedBy;
    }
}
