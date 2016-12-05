<?php

namespace Unilend\Bundle\CoreBusinessBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * Promotions
 *
 * @ORM\Table(name="promotions", uniqueConstraints={@ORM\UniqueConstraint(name="code", columns={"code"})})
 * @ORM\Entity
 */
class Promotions
{
    /**
     * @var string
     *
     * @ORM\Column(name="type", type="string", nullable=false)
     */
    private $type;

    /**
     * @var string
     *
     * @ORM\Column(name="code", type="string", length=50, nullable=false)
     */
    private $code;

    /**
     * @var \DateTime
     *
     * @ORM\Column(name="from", type="date", nullable=false)
     */
    private $from;

    /**
     * @var \DateTime
     *
     * @ORM\Column(name="to", type="date", nullable=false)
     */
    private $to;

    /**
     * @var float
     *
     * @ORM\Column(name="value", type="float", precision=10, scale=0, nullable=false)
     */
    private $value;

    /**
     * @var float
     *
     * @ORM\Column(name="seuil", type="float", precision=10, scale=0, nullable=false)
     */
    private $seuil;

    /**
     * @var boolean
     *
     * @ORM\Column(name="fdp", type="boolean", nullable=false)
     */
    private $fdp = '0';

    /**
     * @var string
     *
     * @ORM\Column(name="id_tree", type="string", length=191, nullable=false)
     */
    private $idTree;

    /**
     * @var string
     *
     * @ORM\Column(name="id_produit", type="string", length=191, nullable=false)
     */
    private $idProduit;

    /**
     * @var string
     *
     * @ORM\Column(name="id_tree2", type="string", length=191, nullable=false)
     */
    private $idTree2;

    /**
     * @var string
     *
     * @ORM\Column(name="id_produit2", type="string", length=191, nullable=false)
     */
    private $idProduit2;

    /**
     * @var integer
     *
     * @ORM\Column(name="nb_minimum2", type="integer", nullable=false)
     */
    private $nbMinimum2;

    /**
     * @var integer
     *
     * @ORM\Column(name="id_groupe", type="integer", nullable=false)
     */
    private $idGroupe;

    /**
     * @var integer
     *
     * @ORM\Column(name="id_client", type="integer", nullable=false)
     */
    private $idClient;

    /**
     * @var integer
     *
     * @ORM\Column(name="id_produit_kdo", type="integer", nullable=false)
     */
    private $idProduitKdo;

    /**
     * @var integer
     *
     * @ORM\Column(name="nb_utilisations", type="integer", nullable=false)
     */
    private $nbUtilisations;

    /**
     * @var integer
     *
     * @ORM\Column(name="nb_minimum", type="integer", nullable=false)
     */
    private $nbMinimum;

    /**
     * @var boolean
     *
     * @ORM\Column(name="plus_cher", type="boolean", nullable=false)
     */
    private $plusCher;

    /**
     * @var boolean
     *
     * @ORM\Column(name="moins_cher", type="boolean", nullable=false)
     */
    private $moinsCher;

    /**
     * @var integer
     *
     * @ORM\Column(name="duree", type="integer", nullable=false)
     */
    private $duree;

    /**
     * @var integer
     *
     * @ORM\Column(name="id_promo", type="integer", nullable=false)
     */
    private $idPromo;

    /**
     * @var boolean
     *
     * @ORM\Column(name="premiere_cmde", type="boolean", nullable=false)
     */
    private $premiereCmde;

    /**
     * @var boolean
     *
     * @ORM\Column(name="status", type="boolean", nullable=false)
     */
    private $status;

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
     * @ORM\Column(name="id_code", type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="IDENTITY")
     */
    private $idCode;



    /**
     * Set type
     *
     * @param string $type
     *
     * @return Promotions
     */
    public function setType($type)
    {
        $this->type = $type;

        return $this;
    }

    /**
     * Get type
     *
     * @return string
     */
    public function getType()
    {
        return $this->type;
    }

    /**
     * Set code
     *
     * @param string $code
     *
     * @return Promotions
     */
    public function setCode($code)
    {
        $this->code = $code;

        return $this;
    }

    /**
     * Get code
     *
     * @return string
     */
    public function getCode()
    {
        return $this->code;
    }

    /**
     * Set from
     *
     * @param \DateTime $from
     *
     * @return Promotions
     */
    public function setFrom($from)
    {
        $this->from = $from;

        return $this;
    }

    /**
     * Get from
     *
     * @return \DateTime
     */
    public function getFrom()
    {
        return $this->from;
    }

    /**
     * Set to
     *
     * @param \DateTime $to
     *
     * @return Promotions
     */
    public function setTo($to)
    {
        $this->to = $to;

        return $this;
    }

    /**
     * Get to
     *
     * @return \DateTime
     */
    public function getTo()
    {
        return $this->to;
    }

    /**
     * Set value
     *
     * @param float $value
     *
     * @return Promotions
     */
    public function setValue($value)
    {
        $this->value = $value;

        return $this;
    }

    /**
     * Get value
     *
     * @return float
     */
    public function getValue()
    {
        return $this->value;
    }

    /**
     * Set seuil
     *
     * @param float $seuil
     *
     * @return Promotions
     */
    public function setSeuil($seuil)
    {
        $this->seuil = $seuil;

        return $this;
    }

    /**
     * Get seuil
     *
     * @return float
     */
    public function getSeuil()
    {
        return $this->seuil;
    }

    /**
     * Set fdp
     *
     * @param boolean $fdp
     *
     * @return Promotions
     */
    public function setFdp($fdp)
    {
        $this->fdp = $fdp;

        return $this;
    }

    /**
     * Get fdp
     *
     * @return boolean
     */
    public function getFdp()
    {
        return $this->fdp;
    }

    /**
     * Set idTree
     *
     * @param string $idTree
     *
     * @return Promotions
     */
    public function setIdTree($idTree)
    {
        $this->idTree = $idTree;

        return $this;
    }

    /**
     * Get idTree
     *
     * @return string
     */
    public function getIdTree()
    {
        return $this->idTree;
    }

    /**
     * Set idProduit
     *
     * @param string $idProduit
     *
     * @return Promotions
     */
    public function setIdProduit($idProduit)
    {
        $this->idProduit = $idProduit;

        return $this;
    }

    /**
     * Get idProduit
     *
     * @return string
     */
    public function getIdProduit()
    {
        return $this->idProduit;
    }

    /**
     * Set idTree2
     *
     * @param string $idTree2
     *
     * @return Promotions
     */
    public function setIdTree2($idTree2)
    {
        $this->idTree2 = $idTree2;

        return $this;
    }

    /**
     * Get idTree2
     *
     * @return string
     */
    public function getIdTree2()
    {
        return $this->idTree2;
    }

    /**
     * Set idProduit2
     *
     * @param string $idProduit2
     *
     * @return Promotions
     */
    public function setIdProduit2($idProduit2)
    {
        $this->idProduit2 = $idProduit2;

        return $this;
    }

    /**
     * Get idProduit2
     *
     * @return string
     */
    public function getIdProduit2()
    {
        return $this->idProduit2;
    }

    /**
     * Set nbMinimum2
     *
     * @param integer $nbMinimum2
     *
     * @return Promotions
     */
    public function setNbMinimum2($nbMinimum2)
    {
        $this->nbMinimum2 = $nbMinimum2;

        return $this;
    }

    /**
     * Get nbMinimum2
     *
     * @return integer
     */
    public function getNbMinimum2()
    {
        return $this->nbMinimum2;
    }

    /**
     * Set idGroupe
     *
     * @param integer $idGroupe
     *
     * @return Promotions
     */
    public function setIdGroupe($idGroupe)
    {
        $this->idGroupe = $idGroupe;

        return $this;
    }

    /**
     * Get idGroupe
     *
     * @return integer
     */
    public function getIdGroupe()
    {
        return $this->idGroupe;
    }

    /**
     * Set idClient
     *
     * @param integer $idClient
     *
     * @return Promotions
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
     * Set idProduitKdo
     *
     * @param integer $idProduitKdo
     *
     * @return Promotions
     */
    public function setIdProduitKdo($idProduitKdo)
    {
        $this->idProduitKdo = $idProduitKdo;

        return $this;
    }

    /**
     * Get idProduitKdo
     *
     * @return integer
     */
    public function getIdProduitKdo()
    {
        return $this->idProduitKdo;
    }

    /**
     * Set nbUtilisations
     *
     * @param integer $nbUtilisations
     *
     * @return Promotions
     */
    public function setNbUtilisations($nbUtilisations)
    {
        $this->nbUtilisations = $nbUtilisations;

        return $this;
    }

    /**
     * Get nbUtilisations
     *
     * @return integer
     */
    public function getNbUtilisations()
    {
        return $this->nbUtilisations;
    }

    /**
     * Set nbMinimum
     *
     * @param integer $nbMinimum
     *
     * @return Promotions
     */
    public function setNbMinimum($nbMinimum)
    {
        $this->nbMinimum = $nbMinimum;

        return $this;
    }

    /**
     * Get nbMinimum
     *
     * @return integer
     */
    public function getNbMinimum()
    {
        return $this->nbMinimum;
    }

    /**
     * Set plusCher
     *
     * @param boolean $plusCher
     *
     * @return Promotions
     */
    public function setPlusCher($plusCher)
    {
        $this->plusCher = $plusCher;

        return $this;
    }

    /**
     * Get plusCher
     *
     * @return boolean
     */
    public function getPlusCher()
    {
        return $this->plusCher;
    }

    /**
     * Set moinsCher
     *
     * @param boolean $moinsCher
     *
     * @return Promotions
     */
    public function setMoinsCher($moinsCher)
    {
        $this->moinsCher = $moinsCher;

        return $this;
    }

    /**
     * Get moinsCher
     *
     * @return boolean
     */
    public function getMoinsCher()
    {
        return $this->moinsCher;
    }

    /**
     * Set duree
     *
     * @param integer $duree
     *
     * @return Promotions
     */
    public function setDuree($duree)
    {
        $this->duree = $duree;

        return $this;
    }

    /**
     * Get duree
     *
     * @return integer
     */
    public function getDuree()
    {
        return $this->duree;
    }

    /**
     * Set idPromo
     *
     * @param integer $idPromo
     *
     * @return Promotions
     */
    public function setIdPromo($idPromo)
    {
        $this->idPromo = $idPromo;

        return $this;
    }

    /**
     * Get idPromo
     *
     * @return integer
     */
    public function getIdPromo()
    {
        return $this->idPromo;
    }

    /**
     * Set premiereCmde
     *
     * @param boolean $premiereCmde
     *
     * @return Promotions
     */
    public function setPremiereCmde($premiereCmde)
    {
        $this->premiereCmde = $premiereCmde;

        return $this;
    }

    /**
     * Get premiereCmde
     *
     * @return boolean
     */
    public function getPremiereCmde()
    {
        return $this->premiereCmde;
    }

    /**
     * Set status
     *
     * @param boolean $status
     *
     * @return Promotions
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
     * Set added
     *
     * @param \DateTime $added
     *
     * @return Promotions
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
     * @return Promotions
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
     * Get idCode
     *
     * @return integer
     */
    public function getIdCode()
    {
        return $this->idCode;
    }
}
