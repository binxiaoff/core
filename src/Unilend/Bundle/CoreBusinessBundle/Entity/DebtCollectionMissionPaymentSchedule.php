<?php

namespace Unilend\Bundle\CoreBusinessBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * DebtCollectionMissionPaymentSchedule
 *
 * @ORM\Table(name="debt_collection_mission_payment_schedule", indexes={@ORM\Index(name="idx_dc_mission_payment_schedule_id_mission", columns={"id_mission"}), @ORM\Index(name="idx_dc_mission_payment_schedule_id_payment", columns={"id_payment_schedule"})})
 * @ORM\Entity(repositoryClass="Unilend\Bundle\CoreBusinessBundle\Repository\DebtCollectionMissionPaymentScheduleRepository")
 */
class DebtCollectionMissionPaymentSchedule
{
    /**
     * @var integer
     *
     * @ORM\Column(name="id", type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="IDENTITY")
     */
    private $id;

    /**
     * @var EcheanciersEmprunteur
     *
     * @ORM\ManyToOne(targetEntity="Unilend\Bundle\CoreBusinessBundle\Entity\EcheanciersEmprunteur")
     * @ORM\JoinColumns({
     *   @ORM\JoinColumn(name="id_payment_schedule", referencedColumnName="id_echeancier_emprunteur")
     * })
     */
    private $idPaymentSchedule;

    /**
     * @var DebtCollectionMission
     *
     * @ORM\ManyToOne(targetEntity="Unilend\Bundle\CoreBusinessBundle\Entity\DebtCollectionMission", inversedBy="debtCollectionMissionPaymentSchedules")
     * @ORM\JoinColumns({
     *   @ORM\JoinColumn(name="id_mission", referencedColumnName="id")
     * })
     */
    private $idMission;

    /**
     * @var float
     *
     * @ORM\Column(name="capital", type="decimal", precision=11, scale=2, nullable=false)
     */
    private $capital;

    /**
     * @var integer
     *
     * @ORM\Column(name="interest", type="decimal", precision=11, scale=2, nullable=false)
     */
    private $interest;

    /**
     * @var integer
     *
     * @ORM\Column(name="commission_vat_incl", type="decimal", precision=11, scale=2, nullable=false)
     */
    private $commissionVatIncl;

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
     * Set idPaymentSchedule
     *
     * @param \Unilend\Bundle\CoreBusinessBundle\Entity\EcheanciersEmprunteur $idPaymentSchedule
     *
     * @return DebtCollectionMissionPaymentSchedule
     */
    public function setIdPaymentSchedule(EcheanciersEmprunteur $idPaymentSchedule = null)
    {
        $this->idPaymentSchedule = $idPaymentSchedule;

        return $this;
    }

    /**
     * Get idPaymentSchedule
     *
     * @return \Unilend\Bundle\CoreBusinessBundle\Entity\EcheanciersEmprunteur
     */
    public function getIdPaymentSchedule()
    {
        return $this->idPaymentSchedule;
    }

    /**
     * Set idMission
     *
     * @param \Unilend\Bundle\CoreBusinessBundle\Entity\DebtCollectionMission $idMission
     *
     * @return DebtCollectionMissionPaymentSchedule
     */
    public function setIdMission(DebtCollectionMission $idMission = null)
    {
        $this->idMission = $idMission;

        return $this;
    }

    /**
     * Get idMission
     *
     * @return \Unilend\Bundle\CoreBusinessBundle\Entity\DebtCollectionMission
     */
    public function getIdMission()
    {
        return $this->idMission;
    }

    /**
     * get entrusted capital
     *
     * @return float
     */
    public function getCapital()
    {
        return $this->capital;
    }

    /**
     * set entrusted capital amount
     *
     * @param float $capital
     *
     * @return DebtCollectionMissionPaymentSchedule
     */
    public function setCapital($capital)
    {
        $this->capital = $capital;

        return $this;
    }

    /**
     * get entrusted interest amount
     *
     * @return float
     */
    public function getInterest()
    {
        return $this->interest;
    }

    /**
     * set entrusted interests amount
     *
     * @param float $interest
     *
     * @return DebtCollectionMissionPaymentSchedule
     */
    public function setInterest($interest)
    {
        $this->interest = $interest;

        return $this;
    }

    /**
     * get entrusted commission VAT included amount
     *
     * @return float
     */
    public function getCommissionVatIncl()
    {
        return $this->commissionVatIncl;
    }

    /**
     * set entrusted commission VAT included amount
     *
     * @param float $commissionVatIncl
     *
     * @return DebtCollectionMissionPaymentSchedule
     */
    public function setCommissionVatIncl($commissionVatIncl)
    {
        $this->commissionVatIncl = $commissionVatIncl;

        return $this;
    }
}
