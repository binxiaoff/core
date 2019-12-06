<?php

declare(strict_types=1);

namespace Unilend\Entity;

use ApiPlatform\Core\Annotation\ApiResource;
use DateTimeImmutable;
use Doctrine\ORM\Mapping as ORM;
use Exception;
use Gedmo\Mapping\Annotation as Gedmo;
use Symfony\Component\Serializer\Annotation\Groups;
use Symfony\Component\Validator\Constraints as Assert;
use Unilend\Entity\Embeddable\Fee;
use Unilend\Entity\Traits\TimestampableTrait;
use Unilend\Traits\ConstantsAwareTrait;

/**
 * @ApiResource(
 *     collectionOperations={
 *         "get": {"security": "is_granted('ROLE_ADMIN')"},
 *         "post": {
 *             "security_post_denormalize": "is_granted('edit', object.getProjectParticipation().getProject())",
 *             "denormalization_context": {"groups": {"projectParticipationFee:create"}}
 *         }
 *     },
 *     itemOperations={
 *         "get": {"security": "is_granted('view', object.getProject())"},
 *         "patch": {
 *             "security_post_denormalize": "is_granted('edit', previous_object.getProjectParticipation().getProject())",
 *             "denormalization_context": {"groups": {"projectParticipationFee:update"}}
 *         },
 *         "put": {
 *             "security_post_denormalize": "is_granted('edit', previous_object.getProjectParticipation().getProject())",
 *             "denormalization_context": {"groups": {"projectParticipationFee:update"}}
 *         }
 *     }
 * )
 *
 * @ORM\Entity
 * @ORM\HasLifecycleCallbacks
 *
 * @Gedmo\Loggable(logEntryClass="Unilend\Entity\Versioned\VersionedProjectParticipationFee")
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
     *
     * @Groups({"project:view"})
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
     *
     * @Groups({
     *     "project:view",
     *     "projectParticipation:create",
     *     "projectParticipation:view",
     *     "projectParticipation:list",
     *     "projectParticipation:update",
     *     "projectParticipationFee:create",
     *     "projectParticipationFee:update"
     * })
     */
    private $fee;

    /**
     * @var Project
     *
     * @ORM\OneToOne(targetEntity="Unilend\Entity\ProjectParticipation", inversedBy="projectParticipationFee")
     * @ORM\JoinColumns({
     *     @ORM\JoinColumn(name="id_project_participation", nullable=false)
     * })
     *
     * @Assert\Valid
     *
     * @Groups({"project:view", "projectParticipation:list", "projectParticipationFee:create", "projectParticipationFee:update"})
     */
    private $projectParticipation;

    /**
     * @param ProjectParticipation $projectParticipation
     * @param Fee                  $fee
     *
     * @throws Exception
     */
    public function __construct(ProjectParticipation $projectParticipation, Fee $fee)
    {
        $this->projectParticipation = $projectParticipation;
        $this->fee                  = $fee;
        $this->added                = new DateTimeImmutable();
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
     * @return ProjectParticipation
     */
    public function getProjectParticipation(): ProjectParticipation
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

    /**
     * @param Fee $fee
     *
     * @return ProjectParticipationFee
     */
    public function setFee(Fee $fee): ProjectParticipationFee
    {
        $this->fee = $fee;

        return $this;
    }
}
