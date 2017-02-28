<?php

namespace Unilend\Bundle\CoreBusinessBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * IndexageVosOperations
 *
 * @ORM\Table(name="indexage_vos_operations", indexes={@ORM\Index(name="id_client", columns={"id_client", "id_transaction"}), @ORM\Index(name="id_echeancier", columns={"id_echeancier"}), @ORM\Index(name="bdc", columns={"bdc"}), @ORM\Index(name="id_projet", columns={"id_projet"}), @ORM\Index(name="type_transaction", columns={"type_transaction"}), @ORM\Index(name="lib", columns={"libelle_operation"}), @ORM\Index(name="idx_ivo_idclient", columns={"id_client"}), @ORM\Index(name="idx_ivo_idtransaction", columns={"id_transaction"})})
 * @ORM\Entity
 */
class IndexageVosOperations
{
    /**
     * @var integer
     *
     * @ORM\Column(name="id_client", type="integer", nullable=false)
     */
    private $idClient;

    /**
     * @var integer
     *
     * @ORM\Column(name="id_transaction", type="integer", nullable=false)
     */
    private $idTransaction;

    /**
     * @var integer
     *
     * @ORM\Column(name="id_echeancier", type="integer", nullable=false)
     */
    private $idEcheancier;

    /**
     * @var integer
     *
     * @ORM\Column(name="id_projet", type="integer", nullable=false)
     */
    private $idProjet;

    /**
     * @var integer
     *
     * @ORM\Column(name="type_transaction", type="integer", nullable=false)
     */
    private $typeTransaction;

    /**
     * @var string
     *
     * @ORM\Column(name="libelle_operation", type="string", length=191, nullable=false)
     */
    private $libelleOperation;

    /**
     * @var integer
     *
     * @ORM\Column(name="bdc", type="integer", nullable=false)
     */
    private $bdc;

    /**
     * @var string
     *
     * @ORM\Column(name="libelle_projet", type="string", length=191, nullable=false)
     */
    private $libelleProjet;

    /**
     * @var \DateTime
     *
     * @ORM\Column(name="date_operation", type="datetime", nullable=false)
     */
    private $dateOperation;

    /**
     * @var integer
     *
     * @ORM\Column(name="solde", type="integer", nullable=false)
     */
    private $solde;

    /**
     * @var integer
     *
     * @ORM\Column(name="montant_operation", type="integer", nullable=false)
     */
    private $montantOperation;

    /**
     * @var integer
     *
     * @ORM\Column(name="montant_capital", type="integer", nullable=false)
     */
    private $montantCapital;

    /**
     * @var integer
     *
     * @ORM\Column(name="montant_interet", type="integer", nullable=false)
     */
    private $montantInteret;

    /**
     * @var string
     *
     * @ORM\Column(name="libelle_prelevement", type="string", length=191, nullable=false)
     */
    private $libellePrelevement;

    /**
     * @var integer
     *
     * @ORM\Column(name="montant_prelevement", type="integer", nullable=false)
     */
    private $montantPrelevement;

    /**
     * @var \DateTime
     *
     * @ORM\Column(name="updated", type="datetime", nullable=false)
     */
    private $updated;

    /**
     * @var \DateTime
     *
     * @ORM\Column(name="added", type="datetime", nullable=false)
     */
    private $added;

    /**
     * @var integer
     *
     * @ORM\Column(name="id", type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="IDENTITY")
     */
    private $id;



    /**
     * Set idClient
     *
     * @param integer $idClient
     *
     * @return IndexageVosOperations
     */
    public function setIdClient($idClient)
    {
        $this->idClient = $idClient;

        return $this;
    }

    /**
     * Get idClient
     *
     * @return integer
     */
    public function getIdClient()
    {
        return $this->idClient;
    }

    /**
     * Set idTransaction
     *
     * @param integer $idTransaction
     *
     * @return IndexageVosOperations
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
     * Set idEcheancier
     *
     * @param integer $idEcheancier
     *
     * @return IndexageVosOperations
     */
    public function setIdEcheancier($idEcheancier)
    {
        $this->idEcheancier = $idEcheancier;

        return $this;
    }

    /**
     * Get idEcheancier
     *
     * @return integer
     */
    public function getIdEcheancier()
    {
        return $this->idEcheancier;
    }

    /**
     * Set idProjet
     *
     * @param integer $idProjet
     *
     * @return IndexageVosOperations
     */
    public function setIdProjet($idProjet)
    {
        $this->idProjet = $idProjet;

        return $this;
    }

    /**
     * Get idProjet
     *
     * @return integer
     */
    public function getIdProjet()
    {
        return $this->idProjet;
    }

    /**
     * Set typeTransaction
     *
     * @param integer $typeTransaction
     *
     * @return IndexageVosOperations
     */
    public function setTypeTransaction($typeTransaction)
    {
        $this->typeTransaction = $typeTransaction;

        return $this;
    }

    /**
     * Get typeTransaction
     *
     * @return integer
     */
    public function getTypeTransaction()
    {
        return $this->typeTransaction;
    }

    /**
     * Set libelleOperation
     *
     * @param string $libelleOperation
     *
     * @return IndexageVosOperations
     */
    public function setLibelleOperation($libelleOperation)
    {
        $this->libelleOperation = $libelleOperation;

        return $this;
    }

    /**
     * Get libelleOperation
     *
     * @return string
     */
    public function getLibelleOperation()
    {
        return $this->libelleOperation;
    }

    /**
     * Set bdc
     *
     * @param integer $bdc
     *
     * @return IndexageVosOperations
     */
    public function setBdc($bdc)
    {
        $this->bdc = $bdc;

        return $this;
    }

    /**
     * Get bdc
     *
     * @return integer
     */
    public function getBdc()
    {
        return $this->bdc;
    }

    /**
     * Set libelleProjet
     *
     * @param string $libelleProjet
     *
     * @return IndexageVosOperations
     */
    public function setLibelleProjet($libelleProjet)
    {
        $this->libelleProjet = $libelleProjet;

        return $this;
    }

    /**
     * Get libelleProjet
     *
     * @return string
     */
    public function getLibelleProjet()
    {
        return $this->libelleProjet;
    }

    /**
     * Set dateOperation
     *
     * @param \DateTime $dateOperation
     *
     * @return IndexageVosOperations
     */
    public function setDateOperation($dateOperation)
    {
        $this->dateOperation = $dateOperation;

        return $this;
    }

    /**
     * Get dateOperation
     *
     * @return \DateTime
     */
    public function getDateOperation()
    {
        return $this->dateOperation;
    }

    /**
     * Set solde
     *
     * @param integer $solde
     *
     * @return IndexageVosOperations
     */
    public function setSolde($solde)
    {
        $this->solde = $solde;

        return $this;
    }

    /**
     * Get solde
     *
     * @return integer
     */
    public function getSolde()
    {
        return $this->solde;
    }

    /**
     * Set montantOperation
     *
     * @param integer $montantOperation
     *
     * @return IndexageVosOperations
     */
    public function setMontantOperation($montantOperation)
    {
        $this->montantOperation = $montantOperation;

        return $this;
    }

    /**
     * Get montantOperation
     *
     * @return integer
     */
    public function getMontantOperation()
    {
        return $this->montantOperation;
    }

    /**
     * Set montantCapital
     *
     * @param integer $montantCapital
     *
     * @return IndexageVosOperations
     */
    public function setMontantCapital($montantCapital)
    {
        $this->montantCapital = $montantCapital;

        return $this;
    }

    /**
     * Get montantCapital
     *
     * @return integer
     */
    public function getMontantCapital()
    {
        return $this->montantCapital;
    }

    /**
     * Set montantInteret
     *
     * @param integer $montantInteret
     *
     * @return IndexageVosOperations
     */
    public function setMontantInteret($montantInteret)
    {
        $this->montantInteret = $montantInteret;

        return $this;
    }

    /**
     * Get montantInteret
     *
     * @return integer
     */
    public function getMontantInteret()
    {
        return $this->montantInteret;
    }

    /**
     * Set libellePrelevement
     *
     * @param string $libellePrelevement
     *
     * @return IndexageVosOperations
     */
    public function setLibellePrelevement($libellePrelevement)
    {
        $this->libellePrelevement = $libellePrelevement;

        return $this;
    }

    /**
     * Get libellePrelevement
     *
     * @return string
     */
    public function getLibellePrelevement()
    {
        return $this->libellePrelevement;
    }

    /**
     * Set montantPrelevement
     *
     * @param integer $montantPrelevement
     *
     * @return IndexageVosOperations
     */
    public function setMontantPrelevement($montantPrelevement)
    {
        $this->montantPrelevement = $montantPrelevement;

        return $this;
    }

    /**
     * Get montantPrelevement
     *
     * @return integer
     */
    public function getMontantPrelevement()
    {
        return $this->montantPrelevement;
    }

    /**
     * Set updated
     *
     * @param \DateTime $updated
     *
     * @return IndexageVosOperations
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
     * Set added
     *
     * @param \DateTime $added
     *
     * @return IndexageVosOperations
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
     * Get id
     *
     * @return integer
     */
    public function getId()
    {
        return $this->id;
    }
}
