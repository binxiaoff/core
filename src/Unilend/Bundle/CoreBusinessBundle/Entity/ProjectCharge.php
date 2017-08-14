<?php

namespace Unilend\Bundle\CoreBusinessBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * ProjectCharge
 *
 * @ORM\Table(name="project_charge", indexes={@ORM\Index(name="fk_project_charge_id_project", columns={"id_project"}), @ORM\Index(name="idx_project_charge_id_type", columns={"id_type"})})
 * @ORM\Entity
 *
 * @ORM\HasLifecycleCallbacks
 */
class ProjectCharge
{
    /**
     * @var int
     *
     * @ORM\Column(name="status", type="smallint", nullable=false)
     */
    private $status;

    /**
     * @var string
     *
     * @ORM\Column(name="amount_excl_vat", type="decimal", precision=12, scale=2, nullable=false)
     */
    private $amountExclVat;

    /**
     * @var string
     *
     * @ORM\Column(name="amount_vat", type="decimal", precision=12, scale=2, nullable=false)
     */
    private $amountVat;

    /**
     * @var \DateTime
     *
     * @ORM\Column(name="invoice_date", type="datetime", nullable=true)
     */
    private $invoiceDate;

    /**
     * @var \DateTime
     *
     * @ORM\Column(name="added", type="datetime", nullable=false)
     */
    private $added;

    /**
     * @var \DateTime
     *
     * @ORM\Column(name="updated", type="datetime", nullable=true)
     */
    private $updated;

    /**
     * @var integer
     *
     * @ORM\Column(name="id", type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="IDENTITY")
     */
    private $id;

    /**
     * @var \Unilend\Bundle\CoreBusinessBundle\Entity\ProjectChargeType
     *
     * @ORM\ManyToOne(targetEntity="Unilend\Bundle\CoreBusinessBundle\Entity\ProjectChargeType")
     * @ORM\JoinColumns({
     *   @ORM\JoinColumn(name="id_type", referencedColumnName="id")
     * })
     */
    private $idType;

    /**
     * @var \Unilend\Bundle\CoreBusinessBundle\Entity\Projects
     *
     * @ORM\ManyToOne(targetEntity="Unilend\Bundle\CoreBusinessBundle\Entity\Projects")
     * @ORM\JoinColumns({
     *   @ORM\JoinColumn(name="id_project", referencedColumnName="id_project")
     * })
     */
    private $idProject;

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
    public function getAmountExclVat()
    {
        return $this->amountExclVat;
    }

    /**
     * @param string $amountExclVat
     *
     * @return ProjectCharge
     */
    public function setAmountExclVat($amountExclVat)
    {
        $this->amountExclVat = $amountExclVat;

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
}
