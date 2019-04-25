<?php

declare(strict_types=1);

namespace Unilend\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Table(name="projects_status")
 * @ORM\Entity
 */
class ProjectsStatus
{
    public const STATUS_REQUEST   = 10;
    public const STATUS_ONLINE    = 20;
    public const STATUS_CONTRACTS = 30;
    public const STATUS_SIGNATURE = 40;
    public const STATUS_REPAYMENT = 50;
    public const STATUS_FINISHED  = 60;
    public const STATUS_LOSS      = 70;
    public const STATUS_CANCELLED = 100;

    /**
     * @var string
     *
     * @ORM\Column(name="label", type="string", length=191)
     */
    private $label;

    /**
     * @var int
     *
     * @ORM\Column(name="status", type="integer", unique=true)
     */
    private $status;

    /**
     * @var int
     *
     * @ORM\Column(name="id_project_status", type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="IDENTITY")
     */
    private $idProjectStatus;

    /**
     * @return string
     */
    public function getLabel(): string
    {
        return $this->label;
    }

    /**
     * @return int
     */
    public function getStatus(): int
    {
        return $this->status;
    }

    /**
     * @return int
     */
    public function getIdProjectStatus(): int
    {
        return $this->idProjectStatus;
    }
}
