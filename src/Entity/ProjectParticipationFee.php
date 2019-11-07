<?php

declare(strict_types=1);

namespace Unilend\Entity;

use ApiPlatform\Core\Annotation\ApiResource;
use Doctrine\ORM\Mapping as ORM;
use Gedmo\Mapping\Annotation as Gedmo;
use Symfony\Component\Validator\Constraints as Assert;
use Unilend\Entity\Embeddable\Fee;
use Unilend\Entity\Traits\TimestampableTrait;
use Unilend\Traits\ConstantsAwareTrait;

/**
 * @ApiResource(
 *     collectionOperations={
 *         "get": {"security": "is_granted('ROLE_ADMIN')"},
 *         "post": {"security_post_denormalize": "is_granted('edit', object.getProject())"}
 *     },
 *     itemOperations={
 *         "get": {"security": "is_granted('view', object.getProject())"},
 *         "put": {"security_post_denormalize": "is_granted('edit', previous_object.getProject())"}
 *     }
 * )
 *
 * @ORM\Entity
 * @ORM\HasLifecycleCallbacks
 *
 * @Gedmo\Loggable(logEntryClass="Unilend\Entity\Versioned\VersionedProjectFee")
 */
class ProjectParticipationFee
{
    use TimestampableTrait;
    use ConstantsAwareTrait;

    public const TYPE_PARTICIPATION  = 'participation';
    public const TYPE_ADMINISTRATIVE = 'administrative';

    /**
     * @var int
     *
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="IDENTITY")
     * @ORM\Column(type="integer")
     */
    private $id;

    /**
     * @var Fee
     *
     * @ORM\Embedded(class="Unilend\Entity\Embeddable\Fee")
     *
     * @Gedmo\Versioned
     *
     * @Assert\Valid
     */
    private $fee;

    /**
     * @var Project
     *
     * @ORM\ManyToOne(targetEntity="Unilend\Entity\ProjectParticipation", inversedBy="projectParticipationFees")
     * @ORM\JoinColumns({
     *     @ORM\JoinColumn(name="id_project_participation", nullable=false)
     * })
     *
     * @Assert\Valid
     */
    private $projectParticipation;

    /**
     * @param ProjectParticipation $projectParticipation
     * @param Fee                  $fee
     */
    public function __construct(ProjectParticipation $projectParticipation, Fee $fee)
    {
        $this->projectParticipation = $projectParticipation;
        $this->fee                  = $fee;
    }

    /**
     * @return int
     */
    public function getId(): int
    {
        return $this->id;
    }

    /**
     * @return Fee
     */
    public function getFee(): Fee
    {
        return $this->fee;
    }

    /**
     * @return Project
     */
    public function getProjectParticipation(): Project
    {
        return $this->projectParticipation;
    }

    /**
     * @return array
     */
    public static function getFeeTypes(): array
    {
        return self::getConstants('TYPE_');
    }

    /**
     * @return string
     *
     * @Assert\Choice(callback="getFeeTypes")
     */
    public function getFeeType(): string
    {
        return $this->fee->getType();
    }
}
