<?php

namespace Unilend\Bundle\CoreBusinessBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * CloseOutNettingPayment
 *
 * @ORM\Table(name="close_out_netting_payment", indexes={@ORM\Index(name="idx_close_out_netting_payment_id_project", columns={"id_project"})})
 * @ORM\Entity
 * @ORM\HasLifecycleCallbacks
 */
class CloseOutNettingPayment
{
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
     * @var string
     *
     * @ORM\Column(name="capital", type="decimal", precision=10, scale=0, nullable=false)
     */
    private $capital;

    /**
     * @var string
     *
     * @ORM\Column(name="paid_capital", type="decimal", precision=10, scale=0, nullable=false)
     */
    private $paidCapital;

    /**
     * @var string
     *
     * @ORM\Column(name="interest", type="decimal", precision=10, scale=0, nullable=false)
     */
    private $interest;

    /**
     * @var string
     *
     * @ORM\Column(name="paid_interest", type="decimal", precision=10, scale=0, nullable=false)
     */
    private $paidInterest;

    /**
     * @var string
     *
     * @ORM\Column(name="commission_tax_incl", type="decimal", precision=10, scale=0, nullable=false)
     */
    private $commissionTaxIncl;

    /**
     * @var string
     *
     * @ORM\Column(name="paid_commission_tax_incl", type="decimal", precision=10, scale=0, nullable=false)
     */
    private $paidCommissionTaxIncl;

    /**
     * @var boolean
     *
     * @ORM\Column(name="notified", type="boolean", nullable=false)
     */
    private $notified;

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
     * Set idProject
     *
     * @param Projects $idProject
     *
     * @return CloseOutNettingPayment
     */
    public function setIdProject(Projects $idProject)
    {
        $this->idProject = $idProject;

        return $this;
    }

    /**
     * Get idProject
     *
     * @return Projects
     */
    public function getIdProject()
    {
        return $this->idProject;
    }

    /**
     * Set capital
     *
     * @param string $capital
     *
     * @return CloseOutNettingPayment
     */
    public function setCapital($capital)
    {
        $this->capital = $capital;

        return $this;
    }

    /**
     * Get capital
     *
     * @return string
     */
    public function getCapital()
    {
        return $this->capital;
    }

    /**
     * Set paidCapital
     *
     * @param string $paidCapital
     *
     * @return CloseOutNettingPayment
     */
    public function setPaidCapital($paidCapital)
    {
        $this->paidCapital = $paidCapital;

        return $this;
    }

    /**
     * Get paidCapital
     *
     * @return string
     */
    public function getPaidCapital()
    {
        return $this->paidCapital;
    }

    /**
     * Set interest
     *
     * @param string $interest
     *
     * @return CloseOutNettingPayment
     */
    public function setInterest($interest)
    {
        $this->interest = $interest;

        return $this;
    }

    /**
     * Get interest
     *
     * @return string
     */
    public function getInterest()
    {
        return $this->interest;
    }

    /**
     * Set paidInterest
     *
     * @param string $paidInterest
     *
     * @return CloseOutNettingPayment
     */
    public function setPaidInterest($paidInterest)
    {
        $this->paidInterest = $paidInterest;

        return $this;
    }

    /**
     * Get paidInterest
     *
     * @return string
     */
    public function getPaidInterest()
    {
        return $this->paidInterest;
    }

    /**
     * Set commissionTaxIncl
     *
     * @param string $commissionTaxIncl
     *
     * @return CloseOutNettingPayment
     */
    public function setCommissionTaxIncl($commissionTaxIncl)
    {
        $this->commissionTaxIncl = $commissionTaxIncl;

        return $this;
    }

    /**
     * Get commissionTaxIncl
     *
     * @return string
     */
    public function getCommissionTaxIncl()
    {
        return $this->commissionTaxIncl;
    }

    /**
     * Set paidCommissionTaxIncl
     *
     * @param string $paidCommissionTaxIncl
     *
     * @return CloseOutNettingPayment
     */
    public function setPaidCommissionTaxIncl($paidCommissionTaxIncl)
    {
        $this->paidCommissionTaxIncl = $paidCommissionTaxIncl;

        return $this;
    }

    /**
     * Get paidCommissionTaxIncl
     *
     * @return string
     */
    public function getPaidCommissionTaxIncl()
    {
        return $this->paidCommissionTaxIncl;
    }

    /**
     * Set added
     *
     * @param \DateTime $added
     *
     * @return CloseOutNettingPayment
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
     * @return CloseOutNettingPayment
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
     * Get id
     *
     * @return integer
     */
    public function getId()
    {
        return $this->id;
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
     * Get notified
     *
     * @return mixed
     */
    public function getNotified()
    {
        return $this->notified;
    }

    /**
     * Set notified
     *
     * @param mixed $notified
     *
     * @return CloseOutNettingPayment
     */
    public function setNotified($notified)
    {
        $this->notified = $notified;
        return $this;
    }
}
