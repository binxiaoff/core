<?php

namespace Unilend\Bundle\CoreBusinessBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * Transactions
 *
 * @ORM\Table(name="transactions", indexes={@ORM\Index(name="id_transaction_old", columns={"id_transaction_old"}), @ORM\Index(name="id_client", columns={"id_client"}), @ORM\Index(name="id_partenaire", columns={"id_partenaire"}), @ORM\Index(name="status", columns={"status"}), @ORM\Index(name="id_echeancier", columns={"id_echeancier"}), @ORM\Index(name="type_transaction", columns={"type_transaction"}), @ORM\Index(name="id_bid_remb", columns={"id_bid_remb"}), @ORM\Index(name="id_loan_remb", columns={"id_loan_remb"}), @ORM\Index(name="date_transaction", columns={"date_transaction"}), @ORM\Index(name="id_backpayline", columns={"id_backpayline"})})
 * @ORM\Entity
 */
class Transactions
{
    /**
     * @var integer
     *
     * @ORM\Column(name="id_backpayline", type="integer", nullable=false)
     */
    private $idBackpayline;

    /**
     * @var integer
     *
     * @ORM\Column(name="id_offre_bienvenue_detail", type="integer", nullable=false)
     */
    private $idOffreBienvenueDetail;

    /**
     * @var integer
     *
     * @ORM\Column(name="id_virement", type="integer", nullable=false)
     */
    private $idVirement;

    /**
     * @var integer
     *
     * @ORM\Column(name="id_prelevement", type="integer", nullable=false)
     */
    private $idPrelevement;

    /**
     * @var integer
     *
     * @ORM\Column(name="id_echeancier", type="integer", nullable=false)
     */
    private $idEcheancier;

    /**
     * @var integer
     *
     * @ORM\Column(name="id_echeancier_emprunteur", type="integer", nullable=false)
     */
    private $idEcheancierEmprunteur;

    /**
     * @var integer
     *
     * @ORM\Column(name="id_bid_remb", type="integer", nullable=false)
     */
    private $idBidRemb;

    /**
     * @var integer
     *
     * @ORM\Column(name="id_loan_remb", type="integer", nullable=false)
     */
    private $idLoanRemb;

    /**
     * @var integer
     *
     * @ORM\Column(name="id_project", type="integer", nullable=false)
     */
    private $idProject;

    /**
     * @var integer
     *
     * @ORM\Column(name="id_client", type="integer", nullable=false)
     */
    private $idClient;

    /**
     * @var integer
     *
     * @ORM\Column(name="id_transfer", type="integer", nullable=false)
     */
    private $idTransfer;

    /**
     * @var integer
     *
     * @ORM\Column(name="id_partenaire", type="integer", nullable=false)
     */
    private $idPartenaire;

    /**
     * @var integer
     *
     * @ORM\Column(name="montant", type="integer", nullable=false)
     */
    private $montant;

    /**
     * @var integer
     *
     * @ORM\Column(name="montant_unilend", type="integer", nullable=false)
     */
    private $montantUnilend;

    /**
     * @var integer
     *
     * @ORM\Column(name="montant_etat", type="integer", nullable=false)
     */
    private $montantEtat;

    /**
     * @var string
     *
     * @ORM\Column(name="id_langue", type="string", length=50, nullable=false)
     */
    private $idLangue;

    /**
     * @var \DateTime
     *
     * @ORM\Column(name="date_transaction", type="datetime", nullable=false)
     */
    private $dateTransaction;

    /**
     * @var string
     *
     * @ORM\Column(name="type_paiement", type="string", length=191, nullable=false)
     */
    private $typePaiement;

    /**
     * @var boolean
     *
     * @ORM\Column(name="status", type="boolean", nullable=false)
     */
    private $status;

    /**
     * @var boolean
     *
     * @ORM\Column(name="type_transaction", type="boolean", nullable=false)
     */
    private $typeTransaction;

    /**
     * @var string
     *
     * @ORM\Column(name="ip_client", type="string", length=191, nullable=false)
     */
    private $ipClient;

    /**
     * @var string
     *
     * @ORM\Column(name="serialize_payline", type="text", length=16777215, nullable=false)
     */
    private $serializePayline;

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
     * @ORM\Column(name="id_transaction_old", type="integer", nullable=true)
     */
    private $idTransactionOld;

    /**
     * @var integer
     *
     * @ORM\Column(name="id_transaction", type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="IDENTITY")
     */
    private $idTransaction;



    /**
     * Set idBackpayline
     *
     * @param integer $idBackpayline
     *
     * @return Transactions
     */
    public function setIdBackpayline($idBackpayline)
    {
        $this->idBackpayline = $idBackpayline;

        return $this;
    }

    /**
     * Get idBackpayline
     *
     * @return integer
     */
    public function getIdBackpayline()
    {
        return $this->idBackpayline;
    }

    /**
     * Set idOffreBienvenueDetail
     *
     * @param integer $idOffreBienvenueDetail
     *
     * @return Transactions
     */
    public function setIdOffreBienvenueDetail($idOffreBienvenueDetail)
    {
        $this->idOffreBienvenueDetail = $idOffreBienvenueDetail;

        return $this;
    }

    /**
     * Get idOffreBienvenueDetail
     *
     * @return integer
     */
    public function getIdOffreBienvenueDetail()
    {
        return $this->idOffreBienvenueDetail;
    }

    /**
     * Set idVirement
     *
     * @param integer $idVirement
     *
     * @return Transactions
     */
    public function setIdVirement($idVirement)
    {
        $this->idVirement = $idVirement;

        return $this;
    }

    /**
     * Get idVirement
     *
     * @return integer
     */
    public function getIdVirement()
    {
        return $this->idVirement;
    }

    /**
     * Set idPrelevement
     *
     * @param integer $idPrelevement
     *
     * @return Transactions
     */
    public function setIdPrelevement($idPrelevement)
    {
        $this->idPrelevement = $idPrelevement;

        return $this;
    }

    /**
     * Get idPrelevement
     *
     * @return integer
     */
    public function getIdPrelevement()
    {
        return $this->idPrelevement;
    }

    /**
     * Set idEcheancier
     *
     * @param integer $idEcheancier
     *
     * @return Transactions
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
     * Set idEcheancierEmprunteur
     *
     * @param integer $idEcheancierEmprunteur
     *
     * @return Transactions
     */
    public function setIdEcheancierEmprunteur($idEcheancierEmprunteur)
    {
        $this->idEcheancierEmprunteur = $idEcheancierEmprunteur;

        return $this;
    }

    /**
     * Get idEcheancierEmprunteur
     *
     * @return integer
     */
    public function getIdEcheancierEmprunteur()
    {
        return $this->idEcheancierEmprunteur;
    }

    /**
     * Set idBidRemb
     *
     * @param integer $idBidRemb
     *
     * @return Transactions
     */
    public function setIdBidRemb($idBidRemb)
    {
        $this->idBidRemb = $idBidRemb;

        return $this;
    }

    /**
     * Get idBidRemb
     *
     * @return integer
     */
    public function getIdBidRemb()
    {
        return $this->idBidRemb;
    }

    /**
     * Set idLoanRemb
     *
     * @param integer $idLoanRemb
     *
     * @return Transactions
     */
    public function setIdLoanRemb($idLoanRemb)
    {
        $this->idLoanRemb = $idLoanRemb;

        return $this;
    }

    /**
     * Get idLoanRemb
     *
     * @return integer
     */
    public function getIdLoanRemb()
    {
        return $this->idLoanRemb;
    }

    /**
     * Set idProject
     *
     * @param integer $idProject
     *
     * @return Transactions
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
     * Set idClient
     *
     * @param integer $idClient
     *
     * @return Transactions
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
     * Set idTransfer
     *
     * @param integer $idTransfer
     *
     * @return Transactions
     */
    public function setIdTransfer($idTransfer)
    {
        $this->idTransfer = $idTransfer;

        return $this;
    }

    /**
     * Get idTransfer
     *
     * @return integer
     */
    public function getIdTransfer()
    {
        return $this->idTransfer;
    }

    /**
     * Set idPartenaire
     *
     * @param integer $idPartenaire
     *
     * @return Transactions
     */
    public function setIdPartenaire($idPartenaire)
    {
        $this->idPartenaire = $idPartenaire;

        return $this;
    }

    /**
     * Get idPartenaire
     *
     * @return integer
     */
    public function getIdPartenaire()
    {
        return $this->idPartenaire;
    }

    /**
     * Set montant
     *
     * @param integer $montant
     *
     * @return Transactions
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
     * Set montantUnilend
     *
     * @param integer $montantUnilend
     *
     * @return Transactions
     */
    public function setMontantUnilend($montantUnilend)
    {
        $this->montantUnilend = $montantUnilend;

        return $this;
    }

    /**
     * Get montantUnilend
     *
     * @return integer
     */
    public function getMontantUnilend()
    {
        return $this->montantUnilend;
    }

    /**
     * Set montantEtat
     *
     * @param integer $montantEtat
     *
     * @return Transactions
     */
    public function setMontantEtat($montantEtat)
    {
        $this->montantEtat = $montantEtat;

        return $this;
    }

    /**
     * Get montantEtat
     *
     * @return integer
     */
    public function getMontantEtat()
    {
        return $this->montantEtat;
    }

    /**
     * Set idLangue
     *
     * @param string $idLangue
     *
     * @return Transactions
     */
    public function setIdLangue($idLangue)
    {
        $this->idLangue = $idLangue;

        return $this;
    }

    /**
     * Get idLangue
     *
     * @return string
     */
    public function getIdLangue()
    {
        return $this->idLangue;
    }

    /**
     * Set dateTransaction
     *
     * @param \DateTime $dateTransaction
     *
     * @return Transactions
     */
    public function setDateTransaction($dateTransaction)
    {
        $this->dateTransaction = $dateTransaction;

        return $this;
    }

    /**
     * Get dateTransaction
     *
     * @return \DateTime
     */
    public function getDateTransaction()
    {
        return $this->dateTransaction;
    }

    /**
     * Set typePaiement
     *
     * @param string $typePaiement
     *
     * @return Transactions
     */
    public function setTypePaiement($typePaiement)
    {
        $this->typePaiement = $typePaiement;

        return $this;
    }

    /**
     * Get typePaiement
     *
     * @return string
     */
    public function getTypePaiement()
    {
        return $this->typePaiement;
    }

    /**
     * Set status
     *
     * @param boolean $status
     *
     * @return Transactions
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
     * Set typeTransaction
     *
     * @param boolean $typeTransaction
     *
     * @return Transactions
     */
    public function setTypeTransaction($typeTransaction)
    {
        $this->typeTransaction = $typeTransaction;

        return $this;
    }

    /**
     * Get typeTransaction
     *
     * @return boolean
     */
    public function getTypeTransaction()
    {
        return $this->typeTransaction;
    }

    /**
     * Set ipClient
     *
     * @param string $ipClient
     *
     * @return Transactions
     */
    public function setIpClient($ipClient)
    {
        $this->ipClient = $ipClient;

        return $this;
    }

    /**
     * Get ipClient
     *
     * @return string
     */
    public function getIpClient()
    {
        return $this->ipClient;
    }

    /**
     * Set serializePayline
     *
     * @param string $serializePayline
     *
     * @return Transactions
     */
    public function setSerializePayline($serializePayline)
    {
        $this->serializePayline = $serializePayline;

        return $this;
    }

    /**
     * Get serializePayline
     *
     * @return string
     */
    public function getSerializePayline()
    {
        return $this->serializePayline;
    }

    /**
     * Set added
     *
     * @param \DateTime $added
     *
     * @return Transactions
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
     * @return Transactions
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
     * Set idTransactionOld
     *
     * @param integer $idTransactionOld
     *
     * @return Transactions
     */
    public function setIdTransactionOld($idTransactionOld)
    {
        $this->idTransactionOld = $idTransactionOld;

        return $this;
    }

    /**
     * Get idTransactionOld
     *
     * @return integer
     */
    public function getIdTransactionOld()
    {
        return $this->idTransactionOld;
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
}
