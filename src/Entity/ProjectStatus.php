<?php

declare(strict_types=1);

namespace Unilend\Entity;

use Doctrine\ORM\Mapping as ORM;
use InvalidArgumentException;
use Unilend\Entity\Interfaces\StatusInterface;
use Unilend\Entity\Traits\{BlamableAddedTrait, TimestampableAddedOnlyTrait};
use Unilend\Service\User\RealUserFinder;
use Unilend\Traits\ConstantsAwareTrait;

/**
 * @ORM\Table(
 *     name="project_status",
 *     indexes={
 *         @ORM\Index(columns={"status"}, name="idx_project_status_status"),
 *         @ORM\Index(columns={"id_project"}, name="idx_project_status_id_project"),
 *         @ORM\Index(columns={"added_by"}, name="idx_project_status_added_by")
 *     }
 * )
 * @ORM\Entity(repositoryClass="Unilend\Repository\ProjectStatusRepository")
 * @ORM\HasLifecycleCallbacks
 */
class ProjectStatus implements StatusInterface
{
    use ConstantsAwareTrait;
    use BlamableAddedTrait;
    use TimestampableAddedOnlyTrait;

    public const STATUS_REQUESTED          = 10;
    public const STATUS_PUBLISHED          = 20;
    public const STATUS_FUNDED             = 30;
    public const STATUS_CONTRACTS_REDACTED = 40;
    public const STATUS_CONTRACTS_SIGNED   = 50;
    public const STATUS_FINISHED           = 60;
    public const STATUS_LOST               = 70;
    public const STATUS_CANCELLED          = 100;

    public const DISPLAYABLE_STATUS = [
        self::STATUS_PUBLISHED,
        self::STATUS_FUNDED,
        self::STATUS_CONTRACTS_REDACTED,
        self::STATUS_CONTRACTS_SIGNED,
        self::STATUS_FINISHED,
    ];

    /**
     * @var Project
     *
     * @ORM\ManyToOne(targetEntity="Unilend\Entity\Project", inversedBy="statuses")
     * @ORM\JoinColumn(name="id_project", nullable=false)
     */
    private $project;

    /**
     * @var int
     *
     * @ORM\Column(type="smallint")
     */
    private $status;

    /**
     * @var int
     *
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="IDENTITY")
     * @ORM\Column(type="integer")
     */
    private $id;

    /**
     * ProjectStatus constructor.
     *
     * @param Project        $project
     * @param int            $status
     * @param RealUserFinder $addedBy
     */
    public function __construct(Project $project, int $status, RealUserFinder $addedBy)
    {
        if (!in_array($status, static::getPossibleStatuses(), true)) {
            throw new InvalidArgumentException(
                sprintf('%s is not a possible status for %s', $status, __CLASS__)
            );
        }
        $this->status  = $status;
        $this->project = $project;
        $this->addedBy = $addedBy();
    }

    /**
     * @return Project
     */
    public function getProject(): Project
    {
        return $this->project;
    }

    /**
     * @return int
     */
    public function getId(): int
    {
        return $this->id;
    }

    /**
     * {@inheritdoc}
     */
    public function getStatus(): int
    {
        return $this->status;
    }

    /**
     * {@inheritdoc}
     */
    public static function getPossibleStatuses(): array
    {
        return static::getConstants('STATUS_');
    }
}
