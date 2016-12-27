<?php

namespace Unilend\Bundle\CoreBusinessBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * PlatformAccountUnilend
 *
 * @ORM\Table(name="platform_account_unilend", indexes={@ORM\Index(name="idx_platform_account_unilend_projet_type", columns={"id_project", "type"})})
 * @ORM\Entity
 */
class PlatformAccountUnilend
{
    /**
     * @var integer
     *
     * @ORM\Column(name="id_transaction", type="integer", nullable=true)
     */
    private $idTransaction;

    /**
     * @var integer
     *
     * @ORM\Column(name="id_echeance_emprunteur", type="integer", nullable=true)
     */
    private $idEcheanceEmprunteur;

    /**
     * @var integer
     *
     * @ORM\Column(name="id_project", type="integer", nullable=true)
     */
    private $idProject;

    /**
     * @var integer
     *
     * @ORM\Column(name="amount", type="integer", nullable=false)
     */
    private $amount;

    /**
     * @var integer
     *
     * @ORM\Column(name="type", type="integer", nullable=false)
     */
    private $type;

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
     * @ORM\Column(name="id", type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="IDENTITY")
     */
    private $id;



    /**
     * Set idTransaction
     *
     * @param integer $idTransaction
     *
     * @return PlatformAccountUnilend
     */
    public function setIdTransaction($idTransaction)
    {
        $this->idTransaction = $idTransaction;

        return $this;
    }

    /**
     * Get idTransaction
     *
     * @return integer
     */
    public function getIdTransaction()
    {
        return $this->idTransaction;
    }

    /**
     * Set idEcheanceEmprunteur
     *
     * @param integer $idEcheanceEmprunteur
     *
     * @return PlatformAccountUnilend
     */
    public function setIdEcheanceEmprunteur($idEcheanceEmprunteur)
    {
        $this->idEcheanceEmprunteur = $idEcheanceEmprunteur;

        return $this;
    }

    /**
     * Get idEcheanceEmprunteur
     *
     * @return integer
     */
    public function getIdEcheanceEmprunteur()
    {
        return $this->idEcheanceEmprunteur;
    }

    /**
     * Set idProject
     *
     * @param integer $idProject
     *
     * @return PlatformAccountUnilend
     */
    public function setIdProject($idProject)
    {
        $this->idProject = $idProject;

        return $this;
    }

    /**
     * Get idProject
     *
     * @return integer
     */
    public function getIdProject()
    {
        return $this->idProject;
    }

    /**
     * Set amount
     *
     * @param integer $amount
     *
     * @return PlatformAccountUnilend
     */
    public function setAmount($amount)
    {
        $this->amount = $amount;

        return $this;
    }

    /**
     * Get amount
     *
     * @return integer
     */
    public function getAmount()
    {
        return $this->amount;
    }

    /**
     * Set type
     *
     * @param integer $type
     *
     * @return PlatformAccountUnilend
     */
    public function setType($type)
    {
        $this->type = $type;

        return $this;
    }

    /**
     * Get type
     *
     * @return integer
     */
    public function getType()
    {
        return $this->type;
    }

    /**
     * Set added
     *
     * @param \DateTime $added
     *
     * @return PlatformAccountUnilend
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
     * @return PlatformAccountUnilend
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
}
