<?php

namespace Unilend\Bundle\CoreBusinessBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * ProjectsStatusHistoryDetails
 *
 * @ORM\Table(name="projects_status_history_details", indexes={@ORM\Index(name="id_project_status_history", columns={"id_project_status_history"})})
 * @ORM\Entity
 */
class ProjectsStatusHistoryDetails
{
    /**
     * @var integer
     *
     * @ORM\Column(name="id_project_status_history", type="integer", nullable=false)
     */
    private $idProjectStatusHistory;

    /**
     * @var \DateTime
     *
     * @ORM\Column(name="date", type="date", nullable=true)
     */
    private $date;

    /**
     * @var string
     *
     * @ORM\Column(name="receiver", type="text", length=16777215, nullable=false)
     */
    private $receiver;

    /**
     * @var string
     *
     * @ORM\Column(name="mail_content", type="text", length=16777215, nullable=false)
     */
    private $mailContent;

    /**
     * @var string
     *
     * @ORM\Column(name="site_content", type="text", length=16777215, nullable=false)
     */
    private $siteContent;

    /**
     * @var integer
     *
     * @ORM\Column(name="analyst_rejection_reason", type="integer", nullable=false)
     */
    private $analystRejectionReason;

    /**
     * @var integer
     *
     * @ORM\Column(name="comity_rejection_reason", type="integer", nullable=false)
     */
    private $comityRejectionReason;

    /**
     * @var integer
     *
     * @ORM\Column(name="commercial_rejection_reason", type="integer", nullable=false)
     */
    private $commercialRejectionReason;

    /**
     * @var \DateTime
     *
     * @ORM\Column(name="added", type="datetime", nullable=false)
     */
    private $added;

    /**
     * @var \DateTime
     *
     * @ORM\Column(name="updated", type="datetime", nullable=false)
     */
    private $updated;

    /**
     * @var integer
     *
     * @ORM\Column(name="id_project_status_history_details", type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="IDENTITY")
     */
    private $idProjectStatusHistoryDetails;



    /**
     * Set idProjectStatusHistory
     *
     * @param integer $idProjectStatusHistory
     *
     * @return ProjectsStatusHistoryDetails
     */
    public function setIdProjectStatusHistory($idProjectStatusHistory)
    {
        $this->idProjectStatusHistory = $idProjectStatusHistory;

        return $this;
    }

    /**
     * Get idProjectStatusHistory
     *
     * @return integer
     */
    public function getIdProjectStatusHistory()
    {
        return $this->idProjectStatusHistory;
    }

    /**
     * Set date
     *
     * @param \DateTime $date
     *
     * @return ProjectsStatusHistoryDetails
     */
    public function setDate($date)
    {
        $this->date = $date;

        return $this;
    }

    /**
     * Get date
     *
     * @return \DateTime
     */
    public function getDate()
    {
        return $this->date;
    }

    /**
     * Set receiver
     *
     * @param string $receiver
     *
     * @return ProjectsStatusHistoryDetails
     */
    public function setReceiver($receiver)
    {
        $this->receiver = $receiver;

        return $this;
    }

    /**
     * Get receiver
     *
     * @return string
     */
    public function getReceiver()
    {
        return $this->receiver;
    }

    /**
     * Set mailContent
     *
     * @param string $mailContent
     *
     * @return ProjectsStatusHistoryDetails
     */
    public function setMailContent($mailContent)
    {
        $this->mailContent = $mailContent;

        return $this;
    }

    /**
     * Get mailContent
     *
     * @return string
     */
    public function getMailContent()
    {
        return $this->mailContent;
    }

    /**
     * Set siteContent
     *
     * @param string $siteContent
     *
     * @return ProjectsStatusHistoryDetails
     */
    public function setSiteContent($siteContent)
    {
        $this->siteContent = $siteContent;

        return $this;
    }

    /**
     * Get siteContent
     *
     * @return string
     */
    public function getSiteContent()
    {
        return $this->siteContent;
    }

    /**
     * Set analystRejectionReason
     *
     * @param integer $analystRejectionReason
     *
     * @return ProjectsStatusHistoryDetails
     */
    public function setAnalystRejectionReason($analystRejectionReason)
    {
        $this->analystRejectionReason = $analystRejectionReason;

        return $this;
    }

    /**
     * Get analystRejectionReason
     *
     * @return integer
     */
    public function getAnalystRejectionReason()
    {
        return $this->analystRejectionReason;
    }

    /**
     * Set comityRejectionReason
     *
     * @param integer $comityRejectionReason
     *
     * @return ProjectsStatusHistoryDetails
     */
    public function setComityRejectionReason($comityRejectionReason)
    {
        $this->comityRejectionReason = $comityRejectionReason;

        return $this;
    }

    /**
     * Get comityRejectionReason
     *
     * @return integer
     */
    public function getComityRejectionReason()
    {
        return $this->comityRejectionReason;
    }

    /**
     * Set commercialRejectionReason
     *
     * @param integer $commercialRejectionReason
     *
     * @return ProjectsStatusHistoryDetails
     */
    public function setCommercialRejectionReason($commercialRejectionReason)
    {
        $this->commercialRejectionReason = $commercialRejectionReason;

        return $this;
    }

    /**
     * Get commercialRejectionReason
     *
     * @return integer
     */
    public function getCommercialRejectionReason()
    {
        return $this->commercialRejectionReason;
    }

    /**
     * Set added
     *
     * @param \DateTime $added
     *
     * @return ProjectsStatusHistoryDetails
     */
    public function setAdded($added)
    {
        $this->added = $added;

        return $this;
    }

    /**
     * Get added
     *
     * @return \DateTime
     */
    public function getAdded()
    {
        return $this->added;
    }

    /**
     * Set updated
     *
     * @param \DateTime $updated
     *
     * @return ProjectsStatusHistoryDetails
     */
    public function setUpdated($updated)
    {
        $this->updated = $updated;

        return $this;
    }

    /**
     * Get updated
     *
     * @return \DateTime
     */
    public function getUpdated()
    {
        return $this->updated;
    }

    /**
     * Get idProjectStatusHistoryDetails
     *
     * @return integer
     */
    public function getIdProjectStatusHistoryDetails()
    {
        return $this->idProjectStatusHistoryDetails;
    }
}
