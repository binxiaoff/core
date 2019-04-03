<?php

namespace Unilend\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * LendersImpositionHistory
 *
 * @ORM\Table(name="lenders_imposition_history", indexes={@ORM\Index(name="id_lender", columns={"id_lender"}), @ORM\Index(name="idx_lenders_imposition_history_added", columns={"added"})})
 * @ORM\Entity(repositoryClass="Unilend\Repository\LendersImpositionHistoryRepository")
 */
class LendersImpositionHistory
{
    /**
     * A lender can live outside of France but with a resident of France for tax purposes. Vise versa.
     *
     *
     * @var bool
     *
     * @ORM\Column(name="resident_etranger", type="boolean")
     */
    private $residentEtranger;

    /**
     * @var int
     *
     * @ORM\Column(name="id_pays", type="integer")
     */
    private $idPays;

    /**
     * @var int
     *
     * @ORM\Column(name="id_user", type="integer")
     */
    private $idUser;

    /**
     * @var \DateTime
     *
     * @ORM\Column(name="added", type="datetime")
     */
    private $added;

    /**
     * @var \DateTime
     *
     * @ORM\Column(name="updated", type="datetime")
     */
    private $updated;

    /**
     * @var int
     *
     * @ORM\Column(name="id_lenders_imposition_history", type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="IDENTITY")
     */
    private $idLendersImpositionHistory;

    /**
     * @var \Unilend\Entity\Wallet
     *
     * @ORM\ManyToOne(targetEntity="Unilend\Entity\Wallet")
     * @ORM\JoinColumns({
     *   @ORM\JoinColumn(name="id_lender", referencedColumnName="id", nullable=false)
     * })
     */
    private $idLender;



    /**
     * Set residentEtranger
     *
     * @param bool $residentEtranger
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
     * @return bool
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

    /**
     * Set idLender
     *
     * @param \Unilend\Entity\Wallet $idLender
     *
     * @return LendersImpositionHistory
     */
    public function setIdLender(Wallet $idLender)
    {
        $this->idLender = $idLender;

        return $this;
    }

    /**
     * Get idLender
     *
     * @return \Unilend\Entity\Wallet
     */
    public function getIdLender()
    {
        return $this->idLender;
    }
}
