<?php

namespace Unilend\Bundle\CoreBusinessBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * LendersImpositionHistory
 *
 * @ORM\Table(name="lenders_imposition_history", indexes={@ORM\Index(name="id_lender", columns={"id_lender"}), @ORM\Index(name="idx_lenders_imposition_history_added", columns={"added"})})
 * @ORM\Entity
 */
class LendersImpositionHistory
{
    /**
     * @var integer
     *
     * @ORM\Column(name="id_lender", type="integer", nullable=false)
     */
    private $idLender;

    /**
     * @var integer
     *
     * @ORM\Column(name="resident_etranger", type="integer", nullable=false)
     */
    private $residentEtranger;

    /**
     * @var integer
     *
     * @ORM\Column(name="id_pays", type="integer", nullable=false)
     */
    private $idPays;

    /**
     * @var integer
     *
     * @ORM\Column(name="id_user", type="integer", nullable=false)
     */
    private $idUser;

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
     * @ORM\Column(name="id_lenders_imposition_history", type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="IDENTITY")
     */
    private $idLendersImpositionHistory;



    /**
     * Set idLender
     *
     * @param integer $idLender
     *
     * @return LendersImpositionHistory
     */
    public function setIdLender($idLender)
    {
        $this->idLender = $idLender;

        return $this;
    }

    /**
     * Get idLender
     *
     * @return integer
     */
    public function getIdLender()
    {
        return $this->idLender;
    }

    /**
     * Set residentEtranger
     *
     * @param integer $residentEtranger
     *
     * @return LendersImpositionHistory
     */
    public function setResidentEtranger($residentEtranger)
    {
        $this->residentEtranger = $residentEtranger;

        return $this;
    }

    /**
     * Get residentEtranger
     *
     * @return integer
     */
    public function getResidentEtranger()
    {
        return $this->residentEtranger;
    }

    /**
     * Set idPays
     *
     * @param integer $idPays
     *
     * @return LendersImpositionHistory
     */
    public function setIdPays($idPays)
    {
        $this->idPays = $idPays;

        return $this;
    }

    /**
     * Get idPays
     *
     * @return integer
     */
    public function getIdPays()
    {
        return $this->idPays;
    }

    /**
     * Set idUser
     *
     * @param integer $idUser
     *
     * @return LendersImpositionHistory
     */
    public function setIdUser($idUser)
    {
        $this->idUser = $idUser;

        return $this;
    }

    /**
     * Get idUser
     *
     * @return integer
     */
    public function getIdUser()
    {
        return $this->idUser;
    }

    /**
     * Set added
     *
     * @param \DateTime $added
     *
     * @return LendersImpositionHistory
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
     * @return LendersImpositionHistory
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
     * Get idLendersImpositionHistory
     *
     * @return integer
     */
    public function getIdLendersImpositionHistory()
    {
        return $this->idLendersImpositionHistory;
    }
}
