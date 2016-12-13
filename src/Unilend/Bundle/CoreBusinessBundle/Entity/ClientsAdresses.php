<?php

namespace Unilend\Bundle\CoreBusinessBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * ClientsAdresses
 *
 * @ORM\Table(name="clients_adresses", indexes={@ORM\Index(name="id_client", columns={"id_client"}), @ORM\Index(name="type", columns={"type"}), @ORM\Index(name="defaut", columns={"defaut"})})
 * @ORM\Entity
 * @ORM\HasLifecycleCallbacks
 */
class ClientsAdresses
{
    /**
     * @var integer
     *
     * @ORM\Column(name="id_client", type="integer", nullable=false)
     */
    private $idClient;

    /**
     * @var boolean
     *
     * @ORM\Column(name="defaut", type="boolean", nullable=false)
     */
    private $defaut = '0';

    /**
     * @var boolean
     *
     * @ORM\Column(name="type", type="boolean", nullable=false)
     */
    private $type = '0';

    /**
     * @var string
     *
     * @ORM\Column(name="nom_adresse", type="string", length=191, nullable=true)
     */
    private $nomAdresse;

    /**
     * @var string
     *
     * @ORM\Column(name="civilite", type="string", nullable=true)
     */
    private $civilite;

    /**
     * @var string
     *
     * @ORM\Column(name="nom", type="string", length=191, nullable=true)
     */
    private $nom;

    /**
     * @var string
     *
     * @ORM\Column(name="prenom", type="string", length=191, nullable=true)
     */
    private $prenom;

    /**
     * @var string
     *
     * @ORM\Column(name="societe", type="string", length=191, nullable=true)
     */
    private $societe;

    /**
     * @var string
     *
     * @ORM\Column(name="adresse1", type="string", length=191, nullable=true)
     */
    private $adresse1;

    /**
     * @var string
     *
     * @ORM\Column(name="adresse2", type="string", length=191, nullable=true)
     */
    private $adresse2;

    /**
     * @var string
     *
     * @ORM\Column(name="adresse3", type="string", length=191, nullable=true)
     */
    private $adresse3;

    /**
     * @var string
     *
     * @ORM\Column(name="cp", type="string", length=191, nullable=true)
     */
    private $cp;

    /**
     * @var string
     *
     * @ORM\Column(name="ville", type="string", length=191, nullable=true)
     */
    private $ville;

    /**
     * @var integer
     *
     * @ORM\Column(name="id_pays", type="integer", nullable=true)
     */
    private $idPays;

    /**
     * @var string
     *
     * @ORM\Column(name="telephone", type="string", length=191, nullable=true)
     */
    private $telephone;

    /**
     * @var string
     *
     * @ORM\Column(name="mobile", type="string", length=191, nullable=true)
     */
    private $mobile;

    /**
     * @var string
     *
     * @ORM\Column(name="commentaire", type="text", length=16777215, nullable=true)
     */
    private $commentaire;

    /**
     * @var boolean
     *
     * @ORM\Column(name="meme_adresse_fiscal", type="boolean", nullable=true)
     */
    private $memeAdresseFiscal;

    /**
     * @var string
     *
     * @ORM\Column(name="adresse_fiscal", type="string", length=191, nullable=true)
     */
    private $adresseFiscal;

    /**
     * @var string
     *
     * @ORM\Column(name="ville_fiscal", type="string", length=191, nullable=true)
     */
    private $villeFiscal;

    /**
     * @var string
     *
     * @ORM\Column(name="cp_fiscal", type="string", length=191, nullable=true)
     */
    private $cpFiscal;

    /**
     * @var integer
     *
     * @ORM\Column(name="id_pays_fiscal", type="integer", nullable=true)
     */
    private $idPaysFiscal;

    /**
     * @var boolean
     *
     * @ORM\Column(name="status", type="boolean", nullable=true)
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
     * @ORM\Column(name="updated", type="datetime", nullable=true)
     */
    private $updated;

    /**
     * @var integer
     *
     * @ORM\Column(name="id_adresse", type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="IDENTITY")
     */
    private $idAdresse;



    /**
     * Set idClient
     *
     * @param integer $idClient
     *
     * @return ClientsAdresses
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
     * Set defaut
     *
     * @param boolean $defaut
     *
     * @return ClientsAdresses
     */
    public function setDefaut($defaut)
    {
        $this->defaut = $defaut;

        return $this;
    }

    /**
     * Get defaut
     *
     * @return boolean
     */
    public function getDefaut()
    {
        return $this->defaut;
    }

    /**
     * Set type
     *
     * @param boolean $type
     *
     * @return ClientsAdresses
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
     * Set nomAdresse
     *
     * @param string $nomAdresse
     *
     * @return ClientsAdresses
     */
    public function setNomAdresse($nomAdresse)
    {
        $this->nomAdresse = $nomAdresse;

        return $this;
    }

    /**
     * Get nomAdresse
     *
     * @return string
     */
    public function getNomAdresse()
    {
        return $this->nomAdresse;
    }

    /**
     * Set civilite
     *
     * @param string $civilite
     *
     * @return ClientsAdresses
     */
    public function setCivilite($civilite)
    {
        $this->civilite = $civilite;

        return $this;
    }

    /**
     * Get civilite
     *
     * @return string
     */
    public function getCivilite()
    {
        return $this->civilite;
    }

    /**
     * Set nom
     *
     * @param string $nom
     *
     * @return ClientsAdresses
     */
    public function setNom($nom)
    {
        $this->nom = $nom;

        return $this;
    }

    /**
     * Get nom
     *
     * @return string
     */
    public function getNom()
    {
        return $this->nom;
    }

    /**
     * Set prenom
     *
     * @param string $prenom
     *
     * @return ClientsAdresses
     */
    public function setPrenom($prenom)
    {
        $this->prenom = $prenom;

        return $this;
    }

    /**
     * Get prenom
     *
     * @return string
     */
    public function getPrenom()
    {
        return $this->prenom;
    }

    /**
     * Set societe
     *
     * @param string $societe
     *
     * @return ClientsAdresses
     */
    public function setSociete($societe)
    {
        $this->societe = $societe;

        return $this;
    }

    /**
     * Get societe
     *
     * @return string
     */
    public function getSociete()
    {
        return $this->societe;
    }

    /**
     * Set adresse1
     *
     * @param string $adresse1
     *
     * @return ClientsAdresses
     */
    public function setAdresse1($adresse1)
    {
        $this->adresse1 = $adresse1;

        return $this;
    }

    /**
     * Get adresse1
     *
     * @return string
     */
    public function getAdresse1()
    {
        return $this->adresse1;
    }

    /**
     * Set adresse2
     *
     * @param string $adresse2
     *
     * @return ClientsAdresses
     */
    public function setAdresse2($adresse2)
    {
        $this->adresse2 = $adresse2;

        return $this;
    }

    /**
     * Get adresse2
     *
     * @return string
     */
    public function getAdresse2()
    {
        return $this->adresse2;
    }

    /**
     * Set adresse3
     *
     * @param string $adresse3
     *
     * @return ClientsAdresses
     */
    public function setAdresse3($adresse3)
    {
        $this->adresse3 = $adresse3;

        return $this;
    }

    /**
     * Get adresse3
     *
     * @return string
     */
    public function getAdresse3()
    {
        return $this->adresse3;
    }

    /**
     * Set cp
     *
     * @param string $cp
     *
     * @return ClientsAdresses
     */
    public function setCp($cp)
    {
        $this->cp = $cp;

        return $this;
    }

    /**
     * Get cp
     *
     * @return string
     */
    public function getCp()
    {
        return $this->cp;
    }

    /**
     * Set ville
     *
     * @param string $ville
     *
     * @return ClientsAdresses
     */
    public function setVille($ville)
    {
        $this->ville = $ville;

        return $this;
    }

    /**
     * Get ville
     *
     * @return string
     */
    public function getVille()
    {
        return $this->ville;
    }

    /**
     * Set idPays
     *
     * @param integer $idPays
     *
     * @return ClientsAdresses
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
     * Set telephone
     *
     * @param string $telephone
     *
     * @return ClientsAdresses
     */
    public function setTelephone($telephone)
    {
        $this->telephone = $telephone;

        return $this;
    }

    /**
     * Get telephone
     *
     * @return string
     */
    public function getTelephone()
    {
        return $this->telephone;
    }

    /**
     * Set mobile
     *
     * @param string $mobile
     *
     * @return ClientsAdresses
     */
    public function setMobile($mobile)
    {
        $this->mobile = $mobile;

        return $this;
    }

    /**
     * Get mobile
     *
     * @return string
     */
    public function getMobile()
    {
        return $this->mobile;
    }

    /**
     * Set commentaire
     *
     * @param string $commentaire
     *
     * @return ClientsAdresses
     */
    public function setCommentaire($commentaire)
    {
        $this->commentaire = $commentaire;

        return $this;
    }

    /**
     * Get commentaire
     *
     * @return string
     */
    public function getCommentaire()
    {
        return $this->commentaire;
    }

    /**
     * Set memeAdresseFiscal
     *
     * @param boolean $memeAdresseFiscal
     *
     * @return ClientsAdresses
     */
    public function setMemeAdresseFiscal($memeAdresseFiscal)
    {
        $this->memeAdresseFiscal = $memeAdresseFiscal;

        return $this;
    }

    /**
     * Get memeAdresseFiscal
     *
     * @return boolean
     */
    public function getMemeAdresseFiscal()
    {
        return $this->memeAdresseFiscal;
    }

    /**
     * Set adresseFiscal
     *
     * @param string $adresseFiscal
     *
     * @return ClientsAdresses
     */
    public function setAdresseFiscal($adresseFiscal)
    {
        $this->adresseFiscal = $adresseFiscal;

        return $this;
    }

    /**
     * Get adresseFiscal
     *
     * @return string
     */
    public function getAdresseFiscal()
    {
        return $this->adresseFiscal;
    }

    /**
     * Set villeFiscal
     *
     * @param string $villeFiscal
     *
     * @return ClientsAdresses
     */
    public function setVilleFiscal($villeFiscal)
    {
        $this->villeFiscal = $villeFiscal;

        return $this;
    }

    /**
     * Get villeFiscal
     *
     * @return string
     */
    public function getVilleFiscal()
    {
        return $this->villeFiscal;
    }

    /**
     * Set cpFiscal
     *
     * @param string $cpFiscal
     *
     * @return ClientsAdresses
     */
    public function setCpFiscal($cpFiscal)
    {
        $this->cpFiscal = $cpFiscal;

        return $this;
    }

    /**
     * Get cpFiscal
     *
     * @return string
     */
    public function getCpFiscal()
    {
        return $this->cpFiscal;
    }

    /**
     * Set idPaysFiscal
     *
     * @param integer $idPaysFiscal
     *
     * @return ClientsAdresses
     */
    public function setIdPaysFiscal($idPaysFiscal)
    {
        $this->idPaysFiscal = $idPaysFiscal;

        return $this;
    }

    /**
     * Get idPaysFiscal
     *
     * @return integer
     */
    public function getIdPaysFiscal()
    {
        return $this->idPaysFiscal;
    }

    /**
     * Set status
     *
     * @param boolean $status
     *
     * @return ClientsAdresses
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
     * @return ClientsAdresses
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
     * @return ClientsAdresses
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
     * Get idAdresse
     *
     * @return integer
     */
    public function getIdAdresse()
    {
        return $this->idAdresse;
    }

    /**
     * @ORM\PrePersist
     */
    public function setAddedValue()
    {
        if(! $this->added instanceof \DateTime || 1 > $this->getAdded()->getTimestamp()) {
            $this->added = new \DateTime();
        }
    }

    /**
     * @ORM\PreUpdate
     */
    public function setUpdatedValue()
    {
        $this->updated = new \DateTime();
    }
}
