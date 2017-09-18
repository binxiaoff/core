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
     * @ORM\Column(name="id", type="integer", nullable=false)
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="IDENTITY")
     */
    private $id;

    /**
     * @var DebtCollectionMission
     *
     * @ORM\ManyToOne(targetEntity="DebtCollectionMission")
     * @ORM\JoinColumns({
     *   @ORM\JoinColumn(name="id_mission", referencedColumnName="id")
     * })
     */
    private $idMission;

    /**
     * @var EcheanciersEmprunteur
     *
     * @ORM\ManyToOne(targetEntity="EcheanciersEmprunteur")
     * @ORM\JoinColumns({
     *   @ORM\JoinColumn(name="id_payment_schedule", referencedColumnName="id_echeancier_emprunteur")
     * })
     */
    private $idPaymentSchedule;

    /**
     * @return int
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * @return DebtCollectionMission
     */
    public function getIdMission()
    {
        return $this->idMission;
    }

    /**
     * @param DebtCollectionMission $idMission
     *
     * @return DebtCollectionMissionPaymentSchedule
     */
    public function setIdMission(DebtCollectionMission $idMission)
    {
        $this->idMission = $idMission;

        return $this;
    }

    /**
     * @return EcheanciersEmprunteur
     */
    public function getIdPaymentSchedule()
    {
        return $this->idPaymentSchedule;
    }

    /**
     * @param EcheanciersEmprunteur $idPaymentSchedule
     *
     * @return DebtCollectionMissionPaymentSchedule
     */
    public function setIdPaymentSchedule(EcheanciersEmprunteur $idPaymentSchedule)
    {
        $this->idPaymentSchedule = $idPaymentSchedule;

        return $this;
    }
}
