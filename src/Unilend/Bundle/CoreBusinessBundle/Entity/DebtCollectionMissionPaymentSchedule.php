<?php

namespace Unilend\Bundle\CoreBusinessBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * DebtCollectionMissionPaymentSchedule
 *
 * @ORM\Table(name="debt_collection_mission_payment_schedule", indexes={@ORM\Index(name="idx_dc_mission_payment_schedule_id_mission", columns={"id_mission"}), @ORM\Index(name="idx_dc_mission_payment_schedule_id_payment", columns={"id_payment_schedule"})})
 * @ORM\Entity
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
     * @var \Unilend\Bundle\CoreBusinessBundle\Entity\EcheanciersEmprunteur
     *
     * @ORM\ManyToOne(targetEntity="Unilend\Bundle\CoreBusinessBundle\Entity\EcheanciersEmprunteur")
     * @ORM\JoinColumns({
     *   @ORM\JoinColumn(name="id_payment_schedule", referencedColumnName="id_echeancier_emprunteur")
     * })
     */
    private $idPaymentSchedule;

    /**
     * @var \Unilend\Bundle\CoreBusinessBundle\Entity\DebtCollectionMission
     *
     * @ORM\ManyToOne(targetEntity="Unilend\Bundle\CoreBusinessBundle\Entity\DebtCollectionMission", inversedBy="debtCollectionMissionPaymentSchedules")
     * @ORM\JoinColumns({
     *   @ORM\JoinColumn(name="id_mission", referencedColumnName="id")
     * })
     */
    private $idMission;



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
    public function setIdPaymentSchedule(\Unilend\Bundle\CoreBusinessBundle\Entity\EcheanciersEmprunteur $idPaymentSchedule = null)
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
    public function setIdMission(\Unilend\Bundle\CoreBusinessBundle\Entity\DebtCollectionMission $idMission = null)
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
}
