<?php

declare(strict_types=1);

namespace Unilend\Entity;

use ApiPlatform\Core\Annotation\ApiResource;
use DateTimeImmutable;
use Doctrine\ORM\Mapping as ORM;
use Exception;
use Symfony\Component\Serializer\Annotation\Groups;
use Symfony\Component\Validator\Constraints as Assert;
use Unilend\Entity\Interfaces\{StatusInterface, TraceableStatusAwareInterface};
use Unilend\Entity\Traits\{BlamableAddedTrait, TimestampableAddedOnlyTrait};
use Unilend\Traits\ConstantsAwareTrait;

/**
 * @ApiResource(
 *     normalizationContext={"groups": {"projectParticipationStatus:read"}},
 *     collectionOperations={
 *         "post": {
 *             "denormalization_context": {"groups": {"projectParticipationStatus:create"}},
 *             "security_post_denormalize": "is_granted('create', object)"
 *         }
 *     }
 * )
 *
 * @ORM\Entity
 * @ORM\Table(indexes={@ORM\Index(columns={"status", "id_project_parcitipation"})})
 */
class ProjectParticipationStatus implements StatusInterface
{
    use ConstantsAwareTrait;
    use BlamableAddedTrait;
    use TimestampableAddedOnlyTrait;

    public const STATUS_ACTIVE   = 10;
    public const STATUS_ARCHIVED = -10;
    public const STATUS_DECLINED = -20;

    /**
     * @var int
     *
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="IDENTITY")
     * @ORM\Column(type="integer")
     */
    private $id;

    /**
     * @var ProjectParticipation
     *
     * @ORM\ManyToOne(targetEntity="Unilend\Entity\ProjectParticipation", inversedBy="statuses")
     * @ORM\JoinColumn(name="id_project_parcitipation", nullable=false)
     *
     * @Groups({"projectParticipationStatus:create"})
     */
    private $projectParticipation;

    /**
     * @var int
     *
     * @ORM\Column(type="smallint")
     *
     * @Assert\Choice(callback="getPossibleStatuses")
     * @Assert\Callback({"Unilend\Validator\Constraints\TraceableStatusValidator", "validate"})
     *
     * @Groups({"projectParticipationStatus:read", "projectParticipationStatus:create"})
     */
    private $status;

    /**
     * @param ProjectParticipation $projectParticipation
     * @param int                  $status
     * @param Staff                $addedBy
     *
     * @throws Exception
     */
    public function __construct(ProjectParticipation $projectParticipation, int $status, Staff $addedBy)
    {
        $this->projectParticipation = $projectParticipation;
        $this->status               = $status;
        $this->addedBy              = $addedBy;
        $this->added                = new DateTimeImmutable();
        $this->projectParticipation->setCurrentStatus($this);
    }

    /**
     * @return ProjectParticipation
     */
    public function getProjectParticipation(): ProjectParticipation
    {
        return $this->projectParticipation;
    }

    /**
     * @return int
     */
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
}
