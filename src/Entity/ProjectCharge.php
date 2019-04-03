<?php

namespace Unilend\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * ProjectCharge
 *
 * @ORM\Table(name="project_charge", indexes={@ORM\Index(name="idx_project_charge_id_project", columns={"id_project"}), @ORM\Index(name="idx_project_charge_id_type", columns={"id_type"}), @ORM\Index(name="idx_project_charge_id_mission", columns={"id_mission"})})
 * @ORM\Entity(repositoryClass="Unilend\Repository\ProjectChargeRepository")
 * @ORM\HasLifecycleCallbacks
 */
class ProjectCharge
{
    const STATUS_PAID_BY_UNILEND    = 0;
    const STATUS_REPAID_BY_BORROWER = 1;

    /**
     * @var int
     *
     * @ORM\Column(name="status", type="smallint")
     */
    private $status;

    /**
     * @var string
     *
     * @ORM\Column(name="amount_incl_vat", type="decimal", precision=12, scale=2)
     */
    private $amountInclVat;

    /**
     * @var string
     *
     * @ORM\Column(name="amount_vat", type="decimal", precision=12, scale=2)
     */
    private $amountVat;

    /**
     * @var \DateTime
     *
     * @ORM\Column(name="invoice_date", type="date")
     */
    private $invoiceDate;

    /**
     * @var \DateTime
     *
     * @ORM\Column(name="added", type="datetime")
     */
    private $added;

    /**
     * @var \DateTime
     *
     * @ORM\Column(name="updated", type="datetime", nullable=true)
     */
    private $updated;

    /**
     * @var int
     *
     * @ORM\Column(name="id", type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="IDENTITY")
     */
    private $id;

    /**
     * @var ProjectChargeType
     *
     * @ORM\ManyToOne(targetEntity="Unilend\Entity\ProjectChargeType")
     * @ORM\JoinColumns({
     *   @ORM\JoinColumn(name="id_type", referencedColumnName="id", nullable=false)
     * })
     */
    private $idType;

    /**
     * @var Projects
     *
     * @ORM\ManyToOne(targetEntity="Unilend\Entity\Projects")
     * @ORM\JoinColumns({
     *   @ORM\JoinColumn(name="id_project", referencedColumnName="id_project", nullable=false)
     * })
     */
    private $idProject;

    /**
     * @var Receptions
     *
     * @ORM\ManyToOne(targetEntity="Unilend\Entity\Receptions")
     * @ORM\JoinColumns({
     *   @ORM\JoinColumn(name="id_wire_transfer_in", referencedColumnName="id_reception")
     * })
     */
    private $idWireTransferIn;

    /**
     * @var DebtCollectionMission
     *
     * @ORM\ManyToOne(targetEntity="Unilend\Entity\DebtCollectionMission")
     * @ORM\JoinColumns({
     *   @ORM\JoinColumn(name="id_mission", referencedColumnName="id")
     * })
     */
    private $idMission;

    /**
     * @var \DateTime
     *
     * @ORM\Column(name="repayment_date", type="datetime", nullable=true)
     */
    private $repaymentDate;

    /**
     * @return int
     */
    public function getStatus()
    {
        return $this->status;
    }

    /**
     * @param int $status
     *
     * @return ProjectCharge
     */
    public function setStatus($status)
    {
        $this->status = $status;

        return $this;
    }

    /**
     * @return string
     */
    public function getAmountInclVat()
    {
        return $this->amountInclVat;
    }

    /**
     * @param string $amountInclVat
     *
     * @return ProjectCharge
     */
    public function setAmountInclVat($amountInclVat)
    {
        $this->amountInclVat = $amountInclVat;

        return $this;
    }

    /**
     * @return string
     */
    public function getAmountVat()
    {
        return $this->amountVat;
    }

    /**
     * @param string $amountVat
     *
     * @return ProjectCharge
     */
    public function setAmountVat($amountVat)
    {
        $this->amountVat = $amountVat;

        return $this;
    }

    /**
     * @return \DateTime
     */
    public function getInvoiceDate()
    {
        return $this->invoiceDate;
    }

    /**
     * @param \DateTime $invoiceDate
     *
     * @return ProjectCharge
     */
    public function setInvoiceDate($invoiceDate)
    {
        $this->invoiceDate = $invoiceDate;

        return $this;
    }

    /**
     * @return \DateTime
     */
    public function getAdded()
    {
        return $this->added;
    }

    /**
     * @param \DateTime $added
     *
     * @return ProjectCharge
     */
    public function setAdded($added)
    {
        $this->added = $added;

        return $this;
    }

    /**
     * @return \DateTime
     */
    public function getUpdated()
    {
        return $this->updated;
    }

    /**
     * @param \DateTime $updated
     *
     * @return ProjectCharge
     */
    public function setUpdated($updated)
    {
        $this->updated = $updated;

        return $this;
    }

    /**
     * @return int
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * @return ProjectChargeType
     */
    public function getIdType()
    {
        return $this->idType;
    }

    /**
     * @param ProjectChargeType $idType
     *
     * @return ProjectCharge
     */
    public function setIdType($idType)
    {
        $this->idType = $idType;

        return $this;
    }

    /**
     * @return Projects
     */
    public function getIdProject()
    {
        return $this->idProject;
    }

    /**
     * @param Projects $idProject
     *
     * @return ProjectCharge
     */
    public function setIdProject($idProject)
    {
        $this->idProject = $idProject;

        return $this;
    }

    /**
     * @ORM\PrePersist
     */
    public function setAddedValue()
    {
        if (! $this->added instanceof \DateTime || 1 > $this->getAdded()->getTimestamp()) {
            $this->added = new \DateTime();
        }
    }

    /**
     * @ORM\PreUpdate
     */
    public function setUpdatedValue()
    {
        $this->updated = new \DateTime();
    }

    /**
     * Set idWireTransferIn
     *
     * @param \Unilend\Entity\Receptions $idWireTransferIn
     *
     * @return ProjectCharge
     */
    public function setIdWireTransferIn(Receptions $idWireTransferIn = null)
    {
        $this->idWireTransferIn = $idWireTransferIn;

        return $this;
    }

    /**
     * Get idWireTransferIn
     *
     * @return Receptions
     */
    public function getIdWireTransferIn()
    {
        return $this->idWireTransferIn;
    }

    /**
     * Get idMission
     *
     * @return DebtCollectionMission
     */
    public function getIdMission()
    {
        return $this->idMission;
    }

    /**
     * Set idMission
     *
     * @param DebtCollectionMission $idMission
     *
     * @return ProjectCharge
     */
    public function setIdMission($idMission)
    {
        $this->idMission = $idMission;

        return $this;
    }

    /**
     * Get repaymentDate
     *
     * @return \DateTime
     */
    public function getRepaymentDate()
    {
        return $this->repaymentDate;
    }

    /**
     * Set repaymentDate
     *
     * @param \DateTime $repaymentDate
     *
     * @return ProjectCharge
     */
    public function setRepaymentDate(\DateTime $repaymentDate = null)
    {
        $this->repaymentDate = $repaymentDate;
        return $this;
    }
}
