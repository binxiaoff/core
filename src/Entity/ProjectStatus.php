<?php

declare(strict_types=1);

namespace Unilend\Entity;

use Doctrine\ORM\Mapping as ORM;
use Exception;
use Unilend\Entity\Traits\{BlamableAddedTrait, TimestampableAddedOnlyTrait};

/**
 * @ORM\Table(
 *     name="project_status",
 *     indexes={
 *         @ORM\Index(columns={"status"}, name="idx_project_status_status"),
 *         @ORM\Index(columns={"project_id"}, name="idx_project_status_project_id"),
 *         @ORM\Index(columns={"added_by"}, name="idx_project_status_added_by")
 *     }
 * )
 * @ORM\Entity(repositoryClass="Unilend\Repository\ProjectStatusRepository")
 */
class ProjectStatus extends AbstractStatus
{
    use BlamableAddedTrait;

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
     * @ORM\JoinColumn(name="project_id", nullable=false, onDelete="CASCADE")
     */
    private $project;

    /**
     * ProjectStatus constructor.
     *
     * @param Project $project
     * @param int     $status
     *
     * @throws Exception
     */
    public function __construct(Project $project, int $status)
    {
        parent::__construct($status);
        $this->project = $project;
    }

    /**
     * @return Project
     */
    public function getProject(): Project
    {
        return $this->project;
    }
}
