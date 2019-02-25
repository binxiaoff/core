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
     * @ORM\OneToOne(targetEntity="Unilend\Bundle\CoreBusinessBundle\Entity\Projects")
     * @ORM\JoinColumns({
     *   @ORM\JoinColumn(name="id_project", referencedColumnName="id_project", nullable=false)
     * })
     */
    private $idProject;

    /**
     * @var string
     *
     * @ORM\Column(name="capital", type="decimal", precision=10, scale=2)
     */
    private $capital;

    /**
     * @var string
     *
     * @ORM\Column(name="paid_capital", type="decimal", precision=10, scale=2)
     */
    private $paidCapital;

    /**
     * @var string
     *
     * @ORM\Column(name="interest", type="decimal", precision=10, scale=2)
     */
    private $interest;

    /**
     * @var string
     *
     * @ORM\Column(name="paid_interest", type="decimal", precision=10, scale=2)
     */
    private $paidInterest;

    /**
     * @var string
     *
     * @ORM\Column(name="commission_tax_incl", type="decimal", precision=10, scale=2)
     */
    private $commissionTaxIncl;

    /**
     * @var string
     *
     * @ORM\Column(name="paid_commission_tax_incl", type="decimal", precision=10, scale=2)
     */
    private $paidCommissionTaxIncl;

    /**
     * @var bool
     *
     * @ORM\Column(name="lenders_notified", type="boolean")
     */
    private $lendersNotified;

    /**
     * @var bool
     *
     * @ORM\Column(name="borrower_notified", type="boolean")
     */
    private $borrowerNotified;

    /**
     * @var CloseOutNettingEmailExtraContent
     *
     * @ORM\OneToOne(targetEntity="Unilend\Bundle\CoreBusinessBundle\Entity\CloseOutNettingEmailExtraContent")
     * @ORM\JoinColumns({
     *   @ORM\JoinColumn(name="id_email_content", referencedColumnName="id")
     * })
     */
    private $idEmailContent;

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
     * Set idProject
     *
     * @param Projects $idProject
     *
     * @return CloseOutNettingPayment
     */
    public function setIdProject(Projects $idProject): CloseOutNettingPayment
    {
        $this->idProject = $idProject;

        return $this;
    }

    /**
     * Get idProject
     *
     * @return Projects
     */
    public function getIdProject(): Projects
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
    public function setCapital(string $capital): CloseOutNettingPayment
    {
        $this->capital = $capital;

        return $this;
    }

    /**
     * Get capital
     *
     * @return string
     */
    public function getCapital(): string
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
    public function setPaidCapital(string $paidCapital): CloseOutNettingPayment
    {
        $this->paidCapital = $paidCapital;

        return $this;
    }

    /**
     * Get paidCapital
     *
     * @return string
     */
    public function getPaidCapital(): string
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
    public function setInterest(string $interest): CloseOutNettingPayment
    {
        $this->interest = $interest;

        return $this;
    }

    /**
     * Get interest
     *
     * @return string
     */
    public function getInterest(): string
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
    public function setPaidInterest(string $paidInterest): CloseOutNettingPayment
    {
        $this->paidInterest = $paidInterest;

        return $this;
    }

    /**
     * Get paidInterest
     *
     * @return string
     */
    public function getPaidInterest(): string
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
    public function setCommissionTaxIncl($commissionTaxIncl): CloseOutNettingPayment
    {
        $this->commissionTaxIncl = $commissionTaxIncl;

        return $this;
    }

    /**
     * Get commissionTaxIncl
     *
     * @return string
     */
    public function getCommissionTaxIncl(): string
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
    public function setPaidCommissionTaxIncl($paidCommissionTaxIncl): CloseOutNettingPayment
    {
        $this->paidCommissionTaxIncl = $paidCommissionTaxIncl;

        return $this;
    }

    /**
     * Get paidCommissionTaxIncl
     *
     * @return string
     */
    public function getPaidCommissionTaxIncl(): string
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
    public function setAdded($added): CloseOutNettingPayment
    {
        $this->added = $added;

        return $this;
    }

    /**
     * Get added
     *
     * @return \DateTime
     */
    public function getAdded(): \DateTime
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
    public function setUpdated(\DateTime $updated)
    {
        $this->updated = $updated;

        return $this;
    }

    /**
     * Get updated
     *
     * @return \DateTime|null
     */
    public function getUpdated(): ?\DateTime
    {
        return $this->updated;
    }

    /**
     * Get id
     *
     * @return int
     */
    public function getId(): int
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
     * @return bool
     */
    public function getLendersNotified(): bool
    {
        return $this->lendersNotified;
    }

    /**
     * @param bool $lendersNotified
     *
     * @return CloseOutNettingPayment
     */
    public function setLendersNotified(bool $lendersNotified): CloseOutNettingPayment
    {
        $this->lendersNotified = $lendersNotified;

        return $this;
    }

    /**
     * @return bool
     */
    public function getBorrowerNotified(): bool
    {
        return $this->borrowerNotified;
    }

    /**
     * @param bool $borrowerNotified
     *
     * @return CloseOutNettingPayment
     */
    public function setBorrowerNotified(bool $borrowerNotified): CloseOutNettingPayment
    {
        $this->borrowerNotified = $borrowerNotified;

        return $this;
    }

    /**
     * @return CloseOutNettingEmailExtraContent|null
     */
    public function getIdEmailContent(): ?CloseOutNettingEmailExtraContent
    {
        return $this->idEmailContent;
    }

    /**
     * @param CloseOutNettingEmailExtraContent|null $idEmailContent
     *
     * @return CloseOutNettingPayment
     */
    public function setIdEmailContent(?CloseOutNettingEmailExtraContent $idEmailContent): CloseOutNettingPayment
    {
        $this->idEmailContent = $idEmailContent;

        return $this;
    }
}
