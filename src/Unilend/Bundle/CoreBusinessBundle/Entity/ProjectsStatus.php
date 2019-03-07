<?php

namespace Unilend\Bundle\CoreBusinessBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * ProjectsStatus
 *
 * @ORM\Table(name="projects_status")
 * @ORM\Entity
 */
class ProjectsStatus
{
    const STATUS_REQUEST   = 10;
    const STATUS_REVIEW    = 20;
    const STATUS_ONLINE    = 30;
    const STATUS_FUNDED    = 40;
    const STATUS_SIGNED    = 50;
    const STATUS_REPAYMENT = 60;
    const STATUS_REPAID    = 70;
    const STATUS_LOSS      = 80;
    const STATUS_CANCELLED = 100;

    const UNEXPECTED_RESPONSE = 'unexpected_response_from_';

    const AFTER_REPAYMENT = [
        self::STATUS_REPAYMENT,
        self::STATUS_REPAID,
        self::STATUS_LOSS
    ];

    /**
     * List of project status when project should be assigned to a commercial
     * @var array
     */
    const SALES_TEAM = [
        self::STATUS_REQUEST,
        self::STATUS_REVIEW
    ];

    /**
     * List of project status when project is considered as part of the risk team pipe
     * @var array
     */
    const RISK_TEAM = [
        self::STATUS_REQUEST,
        self::STATUS_REVIEW
    ];

    /**
     * List of project status when project is considered as part of the commercial team pipe
     * @var array
     */
    const SALES_TEAM_UPCOMING_STATUS = [
        self::STATUS_REQUEST
    ];

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
     * Set label
     *
     * @param string $label
     *
     * @return ProjectsStatus
     */
    public function setLabel($label)
    {
        $this->label = $label;

        return $this;
    }

    /**
     * Get label
     *
     * @return string
     */
    public function getLabel()
    {
        return $this->label;
    }

    /**
     * Set status
     *
     * @param integer $status
     *
     * @return ProjectsStatus
     */
    public function setStatus($status)
    {
        $this->status = $status;

        return $this;
    }

    /**
     * Get status
     *
     * @return integer
     */
    public function getStatus()
    {
        return $this->status;
    }

    /**
     * Get idProjectStatus
     *
     * @return integer
     */
    public function getIdProjectStatus()
    {
        return $this->idProjectStatus;
    }
}
