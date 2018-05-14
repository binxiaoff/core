<?php

namespace Unilend\Bundle\CoreBusinessBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * ProjectsStatus
 *
 * @ORM\Table(name="projects_status", uniqueConstraints={@ORM\UniqueConstraint(name="status", columns={"status"})})
 * @ORM\Entity
 */
class ProjectsStatus
{
    const IMPOSSIBLE_AUTO_EVALUATION = 1;
    const NOT_ELIGIBLE               = 2;
    const SIMULATION                 = 3;
    const INCOMPLETE_REQUEST         = 5;
    const COMPLETE_REQUEST           = 10;
    const ABANDONED                  = 15;
    const POSTPONED                  = 19;
    const COMMERCIAL_REVIEW          = 20;
    const COMMERCIAL_REJECTION       = 25;
    const PENDING_ANALYSIS           = 30;
    const ANALYSIS_REVIEW            = 31;
    const ANALYSIS_REJECTION         = 32;
    const COMITY_REVIEW              = 33;
    const COMITY_REJECTION           = 34;
    const SUSPENSIVE_CONDITIONS      = 35;
    const PREP_FUNDING               = 37;
    const A_FUNDER                   = 40;
    const AUTO_BID_PLACED            = 45;
    const EN_FUNDING                 = 50;
    const BID_TERMINATED             = 55;
    const FUNDE                      = 60;
    const FUNDING_KO                 = 70;
    const PRET_REFUSE                = 75;
    const REMBOURSEMENT              = 80;
    const REMBOURSE                  = 90;
    const REMBOURSEMENT_ANTICIPE     = 95;
    const PROBLEME                   = 100;
    const LOSS                       = 160;

    const UNEXPECTED_RESPONSE = 'unexpected_response_from_';

    const AFTER_REPAYMENT = [
        self::REMBOURSEMENT,
        self::REMBOURSE,
        self::REMBOURSEMENT_ANTICIPE,
        self::PROBLEME,
        self::LOSS
    ];

    /**
     * List of project status when project should be assigned to a commercial
     * @var array
     */
    const SALES_TEAM = [
        self::POSTPONED,
        self::COMMERCIAL_REVIEW,
        self::PENDING_ANALYSIS,
        self::ANALYSIS_REVIEW,
        self::COMITY_REVIEW,
        self::SUSPENSIVE_CONDITIONS,
        self::PREP_FUNDING,
        self::A_FUNDER,
        self::AUTO_BID_PLACED,
        self::EN_FUNDING,
        self::BID_TERMINATED,
        self::FUNDE
    ];

    /**
     * List of project status when project is considered as part of the risk team pipe
     * @var array
     */
    const RISK_TEAM = [
        self::PENDING_ANALYSIS,
        self::ANALYSIS_REVIEW,
        self::COMITY_REVIEW,
        self::SUSPENSIVE_CONDITIONS
    ];

    /**
     * List of project status when project is considered as part of the commercial team pipe
     * @var array
     */
    const SALES_TEAM_UPCOMING_STATUS = [
        self::INCOMPLETE_REQUEST,
        self::COMPLETE_REQUEST
    ];

    /**
     * @var string
     *
     * @ORM\Column(name="label", type="string", length=191, nullable=false)
     */
    private $label;

    /**
     * @var integer
     *
     * @ORM\Column(name="status", type="integer", nullable=false)
     */
    private $status;

    /**
     * @var integer
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
