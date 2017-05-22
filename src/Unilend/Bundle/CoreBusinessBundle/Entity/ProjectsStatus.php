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
    const PREP_FUNDING               = 35;
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
    const PROBLEME_J_X               = 110;
    const RECOUVREMENT               = 120;
    const PROCEDURE_SAUVEGARDE       = 130;
    const REDRESSEMENT_JUDICIAIRE    = 140;
    const LIQUIDATION_JUDICIAIRE     = 150;
    const DEFAUT                     = 160;

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
