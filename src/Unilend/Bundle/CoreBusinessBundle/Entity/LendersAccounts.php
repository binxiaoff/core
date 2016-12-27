<?php

namespace Unilend\Bundle\CoreBusinessBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * LendersAccounts
 *
 * @ORM\Table(name="lenders_accounts", indexes={@ORM\Index(name="id_company_owner", columns={"id_company_owner"}), @ORM\Index(name="id_client_owner", columns={"id_client_owner"})})
 * @ORM\Entity
 */
class LendersAccounts
{
    /**
     * @var integer
     *
     * @ORM\Column(name="id_client_owner", type="integer", nullable=false)
     */
    private $idClientOwner;

    /**
     * @var integer
     *
     * @ORM\Column(name="id_company_owner", type="integer", nullable=false)
     */
    private $idCompanyOwner;

    /**
     * @var string
     *
     * @ORM\Column(name="iban", type="string", length=28, nullable=false)
     */
    private $iban;

    /**
     * @var string
     *
     * @ORM\Column(name="bic", type="string", length=100, nullable=false)
     */
    private $bic;

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
     * @ORM\Column(name="status", type="integer", nullable=false)
     */
    private $status;

    /**
     * @var integer
     *
     * @ORM\Column(name="type_transfert", type="integer", nullable=false)
     */
    private $typeTransfert;

    /**
     * @var string
     *
     * @ORM\Column(name="motif", type="string", length=50, nullable=false)
     */
    private $motif;

    /**
     * @var integer
     *
     * @ORM\Column(name="fonds", type="integer", nullable=false)
     */
    private $fonds;

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
     * @ORM\Column(name="id_lender_account", type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="IDENTITY")
     */
    private $idLenderAccount;



    /**
     * Set idClientOwner
     *
     * @param integer $idClientOwner
     *
     * @return LendersAccounts
     */
    public function setIdClientOwner($idClientOwner)
    {
        $this->idClientOwner = $idClientOwner;

        return $this;
    }

    /**
     * Get idClientOwner
     *
     * @return integer
     */
    public function getIdClientOwner()
    {
        return $this->idClientOwner;
    }

    /**
     * Set idCompanyOwner
     *
     * @param integer $idCompanyOwner
     *
     * @return LendersAccounts
     */
    public function setIdCompanyOwner($idCompanyOwner)
    {
        $this->idCompanyOwner = $idCompanyOwner;

        return $this;
    }

    /**
     * Get idCompanyOwner
     *
     * @return integer
     */
    public function getIdCompanyOwner()
    {
        return $this->idCompanyOwner;
    }

    /**
     * Set iban
     *
     * @param string $iban
     *
     * @return LendersAccounts
     */
    public function setIban($iban)
    {
        $this->iban = $iban;

        return $this;
    }

    /**
     * Get iban
     *
     * @return string
     */
    public function getIban()
    {
        return $this->iban;
    }

    /**
     * Set bic
     *
     * @param string $bic
     *
     * @return LendersAccounts
     */
    public function setBic($bic)
    {
        $this->bic = $bic;

        return $this;
    }

    /**
     * Get bic
     *
     * @return string
     */
    public function getBic()
    {
        return $this->bic;
    }

    /**
     * Set idPartenaire
     *
     * @param integer $idPartenaire
     *
     * @return LendersAccounts
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
     * @return LendersAccounts
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
     * Set status
     *
     * @param integer $status
     *
     * @return LendersAccounts
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
     * Set typeTransfert
     *
     * @param integer $typeTransfert
     *
     * @return LendersAccounts
     */
    public function setTypeTransfert($typeTransfert)
    {
        $this->typeTransfert = $typeTransfert;

        return $this;
    }

    /**
     * Get typeTransfert
     *
     * @return integer
     */
    public function getTypeTransfert()
    {
        return $this->typeTransfert;
    }

    /**
     * Set motif
     *
     * @param string $motif
     *
     * @return LendersAccounts
     */
    public function setMotif($motif)
    {
        $this->motif = $motif;

        return $this;
    }

    /**
     * Get motif
     *
     * @return string
     */
    public function getMotif()
    {
        return $this->motif;
    }

    /**
     * Set fonds
     *
     * @param integer $fonds
     *
     * @return LendersAccounts
     */
    public function setFonds($fonds)
    {
        $this->fonds = $fonds;

        return $this;
    }

    /**
     * Get fonds
     *
     * @return integer
     */
    public function getFonds()
    {
        return $this->fonds;
    }

    /**
     * Set added
     *
     * @param \DateTime $added
     *
     * @return LendersAccounts
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
     * @return LendersAccounts
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
     * Get idLenderAccount
     *
     * @return integer
     */
    public function getIdLenderAccount()
    {
        return $this->idLenderAccount;
    }
}
