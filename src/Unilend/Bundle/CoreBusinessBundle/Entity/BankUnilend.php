<?php

namespace Unilend\Bundle\CoreBusinessBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * BankUnilend
 *
 * @ORM\Table(name="bank_unilend")
 * @ORM\Entity
 */
class BankUnilend
{
    /**
     * @var integer
     *
     * @ORM\Column(name="id_transaction", type="integer", nullable=false)
     */
    private $idTransaction;

    /**
     * @var integer
     *
     * @ORM\Column(name="id_echeance_emprunteur", type="integer", nullable=false)
     */
    private $idEcheanceEmprunteur;

    /**
     * @var integer
     *
     * @ORM\Column(name="id_project", type="integer", nullable=false)
     */
    private $idProject;

    /**
     * @var integer
     *
     * @ORM\Column(name="montant", type="integer", nullable=false)
     */
    private $montant;

    /**
     * @var integer
     *
     * @ORM\Column(name="etat", type="integer", nullable=false)
     */
    private $etat;

    /**
     * @var boolean
     *
     * @ORM\Column(name="type", type="boolean", nullable=false)
     */
    private $type;

    /**
     * @var boolean
     *
     * @ORM\Column(name="status", type="boolean", nullable=false)
     */
    private $status;

    /**
     * @var boolean
     *
     * @ORM\Column(name="retrait_fiscale", type="boolean", nullable=false)
     */
    private $retraitFiscale;

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
     * @ORM\Column(name="id_unilend", type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="IDENTITY")
     */
    private $idUnilend;



    /**
     * Set idTransaction
     *
     * @param integer $idTransaction
     *
     * @return BankUnilend
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
     * @return BankUnilend
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
     * @return BankUnilend
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
     * Set montant
     *
     * @param integer $montant
     *
     * @return BankUnilend
     */
    public function setMontant($montant)
    {
        $this->montant = $montant;

        return $this;
    }

    /**
     * Get montant
     *
     * @return integer
     */
    public function getMontant()
    {
        return $this->montant;
    }

    /**
     * Set etat
     *
     * @param integer $etat
     *
     * @return BankUnilend
     */
    public function setEtat($etat)
    {
        $this->etat = $etat;

        return $this;
    }

    /**
     * Get etat
     *
     * @return integer
     */
    public function getEtat()
    {
        return $this->etat;
    }

    /**
     * Set type
     *
     * @param boolean $type
     *
     * @return BankUnilend
     */
    public function setType($type)
    {
        $this->type = $type;

        return $this;
    }

    /**
     * Get type
     *
     * @return boolean
     */
    public function getType()
    {
        return $this->type;
    }

    /**
     * Set status
     *
     * @param boolean $status
     *
     * @return BankUnilend
     */
    public function setStatus($status)
    {
        $this->status = $status;

        return $this;
    }

    /**
     * Get status
     *
     * @return boolean
     */
    public function getStatus()
    {
        return $this->status;
    }

    /**
     * Set retraitFiscale
     *
     * @param boolean $retraitFiscale
     *
     * @return BankUnilend
     */
    public function setRetraitFiscale($retraitFiscale)
    {
        $this->retraitFiscale = $retraitFiscale;

        return $this;
    }

    /**
     * Get retraitFiscale
     *
     * @return boolean
     */
    public function getRetraitFiscale()
    {
        return $this->retraitFiscale;
    }

    /**
     * Set added
     *
     * @param \DateTime $added
     *
     * @return BankUnilend
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
     * @return BankUnilend
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
     * Get idUnilend
     *
     * @return integer
     */
    public function getIdUnilend()
    {
        return $this->idUnilend;
    }
}
