<?php

declare(strict_types=1);

namespace KLS\Syndication\Entity;

use ApiPlatform\Core\Annotation\ApiResource;
use DateTimeImmutable;
use Doctrine\ORM\Mapping as ORM;
use Exception;
use KLS\Core\Entity\Interfaces\StatusInterface;
use KLS\Core\Entity\Interfaces\TraceableStatusAwareInterface;
use KLS\Core\Entity\Staff;
use KLS\Core\Entity\Traits\BlamableAddedTrait;
use KLS\Core\Entity\Traits\PublicizeIdentityTrait;
use KLS\Core\Entity\Traits\TimestampableAddedOnlyTrait;
use KLS\Core\Traits\ConstantsAwareTrait;
use Symfony\Component\Serializer\Annotation\Groups;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * @ApiResource(
 *     normalizationContext={"groups": {"projectParticipationStatus:read", "timestampable:read"}},
 *     collectionOperations={
 *         "post": {
 *             "denormalization_context": {"groups": {"projectParticipationStatus:create"}},
 *             "security_post_denormalize": "is_granted('create', object)"
 *         }
 *     },
 *     itemOperations={
 *         "get": {
 *             "controller": "ApiPlatform\Core\Action\NotFoundAction",
 *             "read": false,
 *             "output": false,
 *         }
 *     }
 * )
 *
 * @ORM\Entity
 * @ORM\Table(
 *     name="syndication_project_participation_status",
 *     indexes={@ORM\Index(columns={"status", "id_project_participation"})}
 * )
 *
 * @Assert\Callback(
 *     callback={"KLS\Core\Validator\Constraints\TraceableStatusValidator", "validate"},
 *     payload={ "path": "status" }
 * )
 */
class ProjectParticipationStatus implements StatusInterface
{
    use PublicizeIdentityTrait;
    use ConstantsAwareTrait;
    use BlamableAddedTrait;
    use TimestampableAddedOnlyTrait;

    public const STATUS_CREATED            = 10;
    public const STATUS_COMMITTEE_PENDED   = 20;
    public const STATUS_COMMITTEE_ACCEPTED = 30;

    public const STATUS_ARCHIVED_BY_ARRANGER    = -10;
    public const STATUS_ARCHIVED_BY_PARTICIPANT = -20;
    public const STATUS_COMMITTEE_REJECTED      = -30;

    /**
     * @ORM\ManyToOne(targetEntity="KLS\Syndication\Entity\ProjectParticipation", inversedBy="statuses")
     * @ORM\JoinColumn(name="id_project_participation", nullable=false, onDelete="CASCADE")
     *
     * @Groups({"projectParticipationStatus:create"})
     */
    private ProjectParticipation $projectParticipation;

    /**
     * @ORM\Column(type="smallint")
     *
     * @Assert\Choice(callback="getPossibleStatuses")
     * @Assert\Expression(
     *     "this.isStatusValid()",
     *     message="Syndication.ProjectParticipationStatus.status.invalid"
     * )
     *
     * @Groups({"projectParticipationStatus:read", "projectParticipationStatus:create"})
     */
    private int $status;

    /**
     * @throws Exception
     */
    public function __construct(ProjectParticipation $projectParticipation, int $status, Staff $addedBy)
    {
        $this->projectParticipation = $projectParticipation;
        $this->status               = $status;
        $this->addedBy              = $addedBy;
        $this->added                = new DateTimeImmutable();
    }

    public function getProjectParticipation(): ProjectParticipation
    {
        return $this->projectParticipation;
    }

    public function getStatus(): int
    {
        return $this->status;
    }

    /**
     * @return TraceableStatusAwareInterface|ProjectParticipation
     */
    public function getAttachedObject()
    {
        return $this->getProjectParticipation();
    }

    /**
     * @return array|string[]
     */
    public static function getPossibleStatuses(): array
    {
        return static::getConstants('STATUS_');
    }

    public function isStatusValid(): bool
    {
        // Arranger participation is not archivable
        return false === (\in_array($this->status, [self::STATUS_ARCHIVED_BY_ARRANGER, self::STATUS_ARCHIVED_BY_PARTICIPANT])
                && $this->getProjectParticipation()->isArrangerParticipation());
    }
}
