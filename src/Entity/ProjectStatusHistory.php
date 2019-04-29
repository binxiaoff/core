<?php

declare(strict_types=1);

namespace Unilend\Entity;

use Doctrine\ORM\Mapping as ORM;
use Unilend\Entity\Traits\{BlamableAddedOnlyTrait, ConstantsAwareTrait, TimestampableAddedOnlyTrait};

/**
 * @ORM\Table(indexes={@ORM\Index(columns={"status"})})
 * @ORM\Entity(repositoryClass="Unilend\Repository\ProjectStatusHistoryRepository")
 * @ORM\HasLifecycleCallbacks
 */
class ProjectStatusHistory
{
    use ConstantsAwareTrait;
    use TimestampableAddedOnlyTrait;
    use BlamableAddedOnlyTrait;

    public const STATUS_REQUESTED          = 10;
    public const STATUS_PUBLISHED          = 20;
    public const STATUS_FUNDED             = 30;
    public const STATUS_CONTRACTS_REDACTED = 40;
    public const STATUS_CONTRACTS_SIGNED   = 50;
    public const STATUS_FINISHED           = 60;
    public const STATUS_LOST               = 70;
    public const STATUS_CANCELLED          = 100;

    /**
     * @var int
     *
     * @ORM\Column(type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="IDENTITY")
     */
    private $id;

    /**
     * @var Project
     *
     * @ORM\ManyToOne(targetEntity="Unilend\Entity\Project", inversedBy="projectStatusHistories")
     * @ORM\JoinColumns({
     *     @ORM\JoinColumn(name="id_project", nullable=false)
     * })
     */
    private $project;

    /**
     * @var int
     *
     * @ORM\Column(type="smallint")
     */
    private $status;

    /**
     * @return int
     */
    public function getId(): int
    {
        return $this->id;
    }

    /**
     * @param Project $project
     *
     * @return ProjectStatusHistory
     */
    public function setProject(Project $project): ProjectStatusHistory
    {
        $this->project = $project;

        return $this;
    }

    /**
     * @return Project
     */
    public function getProject(): Project
    {
        return $this->project;
    }

    /**
     * @param int $status
     *
     * @return ProjectStatusHistory
     */
    public function setStatus(int $status): ProjectStatusHistory
    {
        if (in_array($status, $this->getAllProjectStatus())) {
            $this->status = $status;
        }

        return $this;
    }

    /**
     * @return int
     */
    public function getStatus(): int
    {
        return $this->status;
    }

    /**
     * @return array
     */
    private function getAllProjectStatus(): iterable
    {
        return self::getConstants('STATUS_');
    }
}
