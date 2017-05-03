<?php

namespace Unilend\Bundle\CoreBusinessBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * Loans
 *
 * @ORM\Table(name="loans", indexes={@ORM\Index(name="id_lender", columns={"id_lender"}), @ORM\Index(name="id_project", columns={"id_project"}), @ORM\Index(name="status", columns={"status"}), @ORM\Index(name="idx_loans_added", columns={"added"}), @ORM\Index(name="idx_loans_id_type_contract", columns={"id_type_contract"})})
 * @ORM\Entity(repositoryClass="Unilend\Bundle\CoreBusinessBundle\Repository\LoansRepository")
 */
class Loans
{
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
     * @ORM\Column(name="id_partenaire_subcode", type="integer", nullable=false)
     */
    private $idPartenaireSubcode;

    /**
     * @var integer
     *
     * @ORM\Column(name="id_country_juridiction", type="integer", nullable=false)
     */
    private $idCountryJuridiction;

    /**
     * @var integer
     *
     * @ORM\Column(name="number_of_terms", type="integer", nullable=false)
     */
    private $numberOfTerms;

    /**
     * @var float
     *
     * @ORM\Column(name="amount", type="float", precision=10, scale=0, nullable=false)
     */
    private $amount;

    /**
     * @var float
     *
     * @ORM\Column(name="rate", type="float", precision=10, scale=0, nullable=false)
     */
    private $rate;

    /**
     * @var integer
     *
     * @ORM\Column(name="status", type="integer", nullable=false)
     */
    private $status;

    /**
     * @var integer
     *
     * @ORM\Column(name="en_attente_mail_rejet_envoye", type="integer", nullable=false)
     */
    private $enAttenteMailRejetEnvoye;

    /**
     * @var string
     *
     * @ORM\Column(name="fichier_declarationContratPret", type="string", length=191, nullable=false)
     */
    private $fichierDeclarationcontratpret;

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
     * @ORM\Column(name="id_loan", type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="IDENTITY")
     */
    private $idLoan;

    /**
     * @var \Unilend\Bundle\CoreBusinessBundle\Entity\Wallet
     *
     * @ORM\ManyToOne(targetEntity="Unilend\Bundle\CoreBusinessBundle\Entity\Wallet")
     * @ORM\JoinColumns({
     *   @ORM\JoinColumn(name="id_lender", referencedColumnName="id")
     * })
     */
    private $idLender;

    /**
     * @var \Unilend\Bundle\CoreBusinessBundle\Entity\Projects
     *
     * @ORM\ManyToOne(targetEntity="Unilend\Bundle\CoreBusinessBundle\Entity\Projects")
     * @ORM\JoinColumns({
     *   @ORM\JoinColumn(name="id_project", referencedColumnName="id_project")
     * })
     */
    private $idProject;

    /**
     * @var \Unilend\Bundle\CoreBusinessBundle\Entity\UnderlyingContract
     *
     * @ORM\ManyToOne(targetEntity="Unilend\Bundle\CoreBusinessBundle\Entity\UnderlyingContract")
     * @ORM\JoinColumns({
     *   @ORM\JoinColumn(name="id_type_contract", referencedColumnName="id_contract")
     * })
     */
    private $idTypeContract;



    /**
     * Set idTransfer
     *
     * @param integer $idTransfer
     *
     * @return Loans
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
     * Set idProject
     *
     * @param Projects $idProject
     *
     * @return Loans
     */
    public function setProject(Projects $idProject)
    {
        $this->idProject = $idProject;

        return $this;
    }

    /**
     * Get idProject
     *
     * @return Projects
     */
    public function getProject()
    {
        return $this->idProject;
    }

    /**
     * Set idPartenaire
     *
     * @param integer $idPartenaire
     *
     * @return Loans
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
     * Set idPartenaireSubcode
     *
     * @param integer $idPartenaireSubcode
     *
     * @return Loans
     */
    public function setIdPartenaireSubcode($idPartenaireSubcode)
    {
        $this->idPartenaireSubcode = $idPartenaireSubcode;

        return $this;
    }

    /**
     * Get idPartenaireSubcode
     *
     * @return integer
     */
    public function getIdPartenaireSubcode()
    {
        return $this->idPartenaireSubcode;
    }

    /**
     * Set idCountryJuridiction
     *
     * @param integer $idCountryJuridiction
     *
     * @return Loans
     */
    public function setIdCountryJuridiction($idCountryJuridiction)
    {
        $this->idCountryJuridiction = $idCountryJuridiction;

        return $this;
    }

    /**
     * Get idCountryJuridiction
     *
     * @return integer
     */
    public function getIdCountryJuridiction()
    {
        return $this->idCountryJuridiction;
    }

    /**
     * Set numberOfTerms
     *
     * @param integer $numberOfTerms
     *
     * @return Loans
     */
    public function setNumberOfTerms($numberOfTerms)
    {
        $this->numberOfTerms = $numberOfTerms;

        return $this;
    }

    /**
     * Get numberOfTerms
     *
     * @return integer
     */
    public function getNumberOfTerms()
    {
        return $this->numberOfTerms;
    }

    /**
     * Set amount
     *
     * @param float $amount
     *
     * @return Loans
     */
    public function setAmount($amount)
    {
        $this->amount = $amount;

        return $this;
    }

    /**
     * Get amount
     *
     * @return float
     */
    public function getAmount()
    {
        return $this->amount;
    }

    /**
     * Set rate
     *
     * @param float $rate
     *
     * @return Loans
     */
    public function setRate($rate)
    {
        $this->rate = $rate;

        return $this;
    }

    /**
     * Get rate
     *
     * @return float
     */
    public function getRate()
    {
        return $this->rate;
    }

    /**
     * Set status
     *
     * @param integer $status
     *
     * @return Loans
     */
    public function setStatus($status)
    {
        $this->status = $status;

        return $this;
    }

    /**
     * Get status
     *
     * @return integer
     */
    public function getStatus()
    {
        return $this->status;
    }

    /**
     * Set enAttenteMailRejetEnvoye
     *
     * @param integer $enAttenteMailRejetEnvoye
     *
     * @return Loans
     */
    public function setEnAttenteMailRejetEnvoye($enAttenteMailRejetEnvoye)
    {
        $this->enAttenteMailRejetEnvoye = $enAttenteMailRejetEnvoye;

        return $this;
    }

    /**
     * Get enAttenteMailRejetEnvoye
     *
     * @return integer
     */
    public function getEnAttenteMailRejetEnvoye()
    {
        return $this->enAttenteMailRejetEnvoye;
    }

    /**
     * Set fichierDeclarationcontratpret
     *
     * @param string $fichierDeclarationcontratpret
     *
     * @return Loans
     */
    public function setFichierDeclarationcontratpret($fichierDeclarationcontratpret)
    {
        $this->fichierDeclarationcontratpret = $fichierDeclarationcontratpret;

        return $this;
    }

    /**
     * Get fichierDeclarationcontratpret
     *
     * @return string
     */
    public function getFichierDeclarationcontratpret()
    {
        return $this->fichierDeclarationcontratpret;
    }

    /**
     * Set added
     *
     * @param \DateTime $added
     *
     * @return Loans
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
     * @return Loans
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
     * Get idLoan
     *
     * @return integer
     */
    public function getIdLoan()
    {
        return $this->idLoan;
    }

    /**
     * Set idLender
     *
     * @param \Unilend\Bundle\CoreBusinessBundle\Entity\Wallet $idLender
     *
     * @return Loans
     */
    public function setIdLender(Wallet $idLender)
    {
        $this->idLender = $idLender;

        return $this;
    }

    /**
     * Get idLender
     *
     * @return \Unilend\Bundle\CoreBusinessBundle\Entity\Wallet
     */
    public function getIdLender()
    {
        return $this->idLender;
    }

    /**
     * Set idTypeContract
     *
     * @param UnderlyingContract $idTypeContract
     *
     * @return Loans
     */
    public function setIdTypeContract(UnderlyingContract $idTypeContract = null)
    {
        $this->idTypeContract = $idTypeContract;

        return $this;
    }

    /**
     * Get idTypeContract
     *
     * @return \Unilend\Bundle\CoreBusinessBundle\Entity\UnderlyingContract
     */
    public function getIdTypeContract()
    {
        return $this->idTypeContract;
    }
}
