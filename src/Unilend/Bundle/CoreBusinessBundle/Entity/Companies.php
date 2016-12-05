<?php

namespace Unilend\Bundle\CoreBusinessBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * Companies
 *
 * @ORM\Table(name="companies", indexes={@ORM\Index(name="id_client_owner", columns={"id_client_owner"})})
 * @ORM\Entity
 */
class Companies
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
     * @var string
     *
     * @ORM\Column(name="email_facture", type="string", length=191, nullable=false)
     */
    private $emailFacture;

    /**
     * @var string
     *
     * @ORM\Column(name="name", type="text", length=16777215, nullable=false)
     */
    private $name;

    /**
     * @var string
     *
     * @ORM\Column(name="forme", type="string", length=191, nullable=false)
     */
    private $forme;

    /**
     * @var string
     *
     * @ORM\Column(name="siren", type="string", length=15, nullable=false)
     */
    private $siren;

    /**
     * @var string
     *
     * @ORM\Column(name="siret", type="string", length=14, nullable=false)
     */
    private $siret;

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
     * @var boolean
     *
     * @ORM\Column(name="execices_comptables", type="boolean", nullable=false)
     */
    private $execicesComptables;

    /**
     * @var string
     *
     * @ORM\Column(name="rcs", type="string", length=45, nullable=false)
     */
    private $rcs;

    /**
     * @var string
     *
     * @ORM\Column(name="tribunal_com", type="string", length=191, nullable=false)
     */
    private $tribunalCom;

    /**
     * @var string
     *
     * @ORM\Column(name="activite", type="string", length=191, nullable=false)
     */
    private $activite;

    /**
     * @var string
     *
     * @ORM\Column(name="lieu_exploi", type="string", length=191, nullable=false)
     */
    private $lieuExploi;

    /**
     * @var float
     *
     * @ORM\Column(name="tva", type="float", precision=10, scale=0, nullable=false)
     */
    private $tva;

    /**
     * @var float
     *
     * @ORM\Column(name="capital", type="float", precision=10, scale=0, nullable=false)
     */
    private $capital;

    /**
     * @var \DateTime
     *
     * @ORM\Column(name="date_creation", type="date", nullable=false)
     */
    private $dateCreation;

    /**
     * @var string
     *
     * @ORM\Column(name="adresse1", type="string", length=191, nullable=false)
     */
    private $adresse1;

    /**
     * @var string
     *
     * @ORM\Column(name="adresse2", type="string", length=191, nullable=false)
     */
    private $adresse2;

    /**
     * @var string
     *
     * @ORM\Column(name="zip", type="string", length=10, nullable=false)
     */
    private $zip;

    /**
     * @var string
     *
     * @ORM\Column(name="city", type="string", length=191, nullable=false)
     */
    private $city;

    /**
     * @var integer
     *
     * @ORM\Column(name="id_pays", type="integer", nullable=false)
     */
    private $idPays;

    /**
     * @var float
     *
     * @ORM\Column(name="latitude", type="float", precision=10, scale=0, nullable=true)
     */
    private $latitude;

    /**
     * @var float
     *
     * @ORM\Column(name="longitude", type="float", precision=10, scale=0, nullable=true)
     */
    private $longitude;

    /**
     * @var string
     *
     * @ORM\Column(name="phone", type="string", length=45, nullable=false)
     */
    private $phone;

    /**
     * @var boolean
     *
     * @ORM\Column(name="status_adresse_correspondance", type="boolean", nullable=false)
     */
    private $statusAdresseCorrespondance;

    /**
     * @var boolean
     *
     * @ORM\Column(name="status_client", type="boolean", nullable=false)
     */
    private $statusClient;

    /**
     * @var integer
     *
     * @ORM\Column(name="status_conseil_externe_entreprise", type="integer", nullable=false)
     */
    private $statusConseilExterneEntreprise;

    /**
     * @var string
     *
     * @ORM\Column(name="preciser_conseil_externe_entreprise", type="string", length=191, nullable=false)
     */
    private $preciserConseilExterneEntreprise;

    /**
     * @var string
     *
     * @ORM\Column(name="civilite_dirigeant", type="string", nullable=false)
     */
    private $civiliteDirigeant;

    /**
     * @var string
     *
     * @ORM\Column(name="nom_dirigeant", type="string", length=191, nullable=false)
     */
    private $nomDirigeant;

    /**
     * @var string
     *
     * @ORM\Column(name="prenom_dirigeant", type="string", length=191, nullable=false)
     */
    private $prenomDirigeant;

    /**
     * @var string
     *
     * @ORM\Column(name="fonction_dirigeant", type="string", length=191, nullable=false)
     */
    private $fonctionDirigeant;

    /**
     * @var string
     *
     * @ORM\Column(name="email_dirigeant", type="string", length=191, nullable=false)
     */
    private $emailDirigeant;

    /**
     * @var string
     *
     * @ORM\Column(name="phone_dirigeant", type="string", length=45, nullable=false)
     */
    private $phoneDirigeant;

    /**
     * @var integer
     *
     * @ORM\Column(name="sector", type="integer", nullable=false)
     */
    private $sector;

    /**
     * @var string
     *
     * @ORM\Column(name="risk", type="string", length=45, nullable=false)
     */
    private $risk;

    /**
     * @var string
     *
     * @ORM\Column(name="code_naf", type="string", length=5, nullable=false)
     */
    private $codeNaf;

    /**
     * @var string
     *
     * @ORM\Column(name="libelle_naf", type="string", length=130, nullable=false)
     */
    private $libelleNaf;

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
     * @ORM\Column(name="id_company", type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="IDENTITY")
     */
    private $idCompany;



    /**
     * Set idClientOwner
     *
     * @param integer $idClientOwner
     *
     * @return Companies
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
     * Set idPartenaire
     *
     * @param integer $idPartenaire
     *
     * @return Companies
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
     * @return Companies
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
     * Set emailFacture
     *
     * @param string $emailFacture
     *
     * @return Companies
     */
    public function setEmailFacture($emailFacture)
    {
        $this->emailFacture = $emailFacture;

        return $this;
    }

    /**
     * Get emailFacture
     *
     * @return string
     */
    public function getEmailFacture()
    {
        return $this->emailFacture;
    }

    /**
     * Set name
     *
     * @param string $name
     *
     * @return Companies
     */
    public function setName($name)
    {
        $this->name = $name;

        return $this;
    }

    /**
     * Get name
     *
     * @return string
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * Set forme
     *
     * @param string $forme
     *
     * @return Companies
     */
    public function setForme($forme)
    {
        $this->forme = $forme;

        return $this;
    }

    /**
     * Get forme
     *
     * @return string
     */
    public function getForme()
    {
        return $this->forme;
    }

    /**
     * Set siren
     *
     * @param string $siren
     *
     * @return Companies
     */
    public function setSiren($siren)
    {
        $this->siren = $siren;

        return $this;
    }

    /**
     * Get siren
     *
     * @return string
     */
    public function getSiren()
    {
        return $this->siren;
    }

    /**
     * Set siret
     *
     * @param string $siret
     *
     * @return Companies
     */
    public function setSiret($siret)
    {
        $this->siret = $siret;

        return $this;
    }

    /**
     * Get siret
     *
     * @return string
     */
    public function getSiret()
    {
        return $this->siret;
    }

    /**
     * Set iban
     *
     * @param string $iban
     *
     * @return Companies
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
     * @return Companies
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
     * Set execicesComptables
     *
     * @param boolean $execicesComptables
     *
     * @return Companies
     */
    public function setExecicesComptables($execicesComptables)
    {
        $this->execicesComptables = $execicesComptables;

        return $this;
    }

    /**
     * Get execicesComptables
     *
     * @return boolean
     */
    public function getExecicesComptables()
    {
        return $this->execicesComptables;
    }

    /**
     * Set rcs
     *
     * @param string $rcs
     *
     * @return Companies
     */
    public function setRcs($rcs)
    {
        $this->rcs = $rcs;

        return $this;
    }

    /**
     * Get rcs
     *
     * @return string
     */
    public function getRcs()
    {
        return $this->rcs;
    }

    /**
     * Set tribunalCom
     *
     * @param string $tribunalCom
     *
     * @return Companies
     */
    public function setTribunalCom($tribunalCom)
    {
        $this->tribunalCom = $tribunalCom;

        return $this;
    }

    /**
     * Get tribunalCom
     *
     * @return string
     */
    public function getTribunalCom()
    {
        return $this->tribunalCom;
    }

    /**
     * Set activite
     *
     * @param string $activite
     *
     * @return Companies
     */
    public function setActivite($activite)
    {
        $this->activite = $activite;

        return $this;
    }

    /**
     * Get activite
     *
     * @return string
     */
    public function getActivite()
    {
        return $this->activite;
    }

    /**
     * Set lieuExploi
     *
     * @param string $lieuExploi
     *
     * @return Companies
     */
    public function setLieuExploi($lieuExploi)
    {
        $this->lieuExploi = $lieuExploi;

        return $this;
    }

    /**
     * Get lieuExploi
     *
     * @return string
     */
    public function getLieuExploi()
    {
        return $this->lieuExploi;
    }

    /**
     * Set tva
     *
     * @param float $tva
     *
     * @return Companies
     */
    public function setTva($tva)
    {
        $this->tva = $tva;

        return $this;
    }

    /**
     * Get tva
     *
     * @return float
     */
    public function getTva()
    {
        return $this->tva;
    }

    /**
     * Set capital
     *
     * @param float $capital
     *
     * @return Companies
     */
    public function setCapital($capital)
    {
        $this->capital = $capital;

        return $this;
    }

    /**
     * Get capital
     *
     * @return float
     */
    public function getCapital()
    {
        return $this->capital;
    }

    /**
     * Set dateCreation
     *
     * @param \DateTime $dateCreation
     *
     * @return Companies
     */
    public function setDateCreation($dateCreation)
    {
        $this->dateCreation = $dateCreation;

        return $this;
    }

    /**
     * Get dateCreation
     *
     * @return \DateTime
     */
    public function getDateCreation()
    {
        return $this->dateCreation;
    }

    /**
     * Set adresse1
     *
     * @param string $adresse1
     *
     * @return Companies
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
     * @return Companies
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
     * Set zip
     *
     * @param string $zip
     *
     * @return Companies
     */
    public function setZip($zip)
    {
        $this->zip = $zip;

        return $this;
    }

    /**
     * Get zip
     *
     * @return string
     */
    public function getZip()
    {
        return $this->zip;
    }

    /**
     * Set city
     *
     * @param string $city
     *
     * @return Companies
     */
    public function setCity($city)
    {
        $this->city = $city;

        return $this;
    }

    /**
     * Get city
     *
     * @return string
     */
    public function getCity()
    {
        return $this->city;
    }

    /**
     * Set idPays
     *
     * @param integer $idPays
     *
     * @return Companies
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
     * Set latitude
     *
     * @param float $latitude
     *
     * @return Companies
     */
    public function setLatitude($latitude)
    {
        $this->latitude = $latitude;

        return $this;
    }

    /**
     * Get latitude
     *
     * @return float
     */
    public function getLatitude()
    {
        return $this->latitude;
    }

    /**
     * Set longitude
     *
     * @param float $longitude
     *
     * @return Companies
     */
    public function setLongitude($longitude)
    {
        $this->longitude = $longitude;

        return $this;
    }

    /**
     * Get longitude
     *
     * @return float
     */
    public function getLongitude()
    {
        return $this->longitude;
    }

    /**
     * Set phone
     *
     * @param string $phone
     *
     * @return Companies
     */
    public function setPhone($phone)
    {
        $this->phone = $phone;

        return $this;
    }

    /**
     * Get phone
     *
     * @return string
     */
    public function getPhone()
    {
        return $this->phone;
    }

    /**
     * Set statusAdresseCorrespondance
     *
     * @param boolean $statusAdresseCorrespondance
     *
     * @return Companies
     */
    public function setStatusAdresseCorrespondance($statusAdresseCorrespondance)
    {
        $this->statusAdresseCorrespondance = $statusAdresseCorrespondance;

        return $this;
    }

    /**
     * Get statusAdresseCorrespondance
     *
     * @return boolean
     */
    public function getStatusAdresseCorrespondance()
    {
        return $this->statusAdresseCorrespondance;
    }

    /**
     * Set statusClient
     *
     * @param boolean $statusClient
     *
     * @return Companies
     */
    public function setStatusClient($statusClient)
    {
        $this->statusClient = $statusClient;

        return $this;
    }

    /**
     * Get statusClient
     *
     * @return boolean
     */
    public function getStatusClient()
    {
        return $this->statusClient;
    }

    /**
     * Set statusConseilExterneEntreprise
     *
     * @param integer $statusConseilExterneEntreprise
     *
     * @return Companies
     */
    public function setStatusConseilExterneEntreprise($statusConseilExterneEntreprise)
    {
        $this->statusConseilExterneEntreprise = $statusConseilExterneEntreprise;

        return $this;
    }

    /**
     * Get statusConseilExterneEntreprise
     *
     * @return integer
     */
    public function getStatusConseilExterneEntreprise()
    {
        return $this->statusConseilExterneEntreprise;
    }

    /**
     * Set preciserConseilExterneEntreprise
     *
     * @param string $preciserConseilExterneEntreprise
     *
     * @return Companies
     */
    public function setPreciserConseilExterneEntreprise($preciserConseilExterneEntreprise)
    {
        $this->preciserConseilExterneEntreprise = $preciserConseilExterneEntreprise;

        return $this;
    }

    /**
     * Get preciserConseilExterneEntreprise
     *
     * @return string
     */
    public function getPreciserConseilExterneEntreprise()
    {
        return $this->preciserConseilExterneEntreprise;
    }

    /**
     * Set civiliteDirigeant
     *
     * @param string $civiliteDirigeant
     *
     * @return Companies
     */
    public function setCiviliteDirigeant($civiliteDirigeant)
    {
        $this->civiliteDirigeant = $civiliteDirigeant;

        return $this;
    }

    /**
     * Get civiliteDirigeant
     *
     * @return string
     */
    public function getCiviliteDirigeant()
    {
        return $this->civiliteDirigeant;
    }

    /**
     * Set nomDirigeant
     *
     * @param string $nomDirigeant
     *
     * @return Companies
     */
    public function setNomDirigeant($nomDirigeant)
    {
        $this->nomDirigeant = $nomDirigeant;

        return $this;
    }

    /**
     * Get nomDirigeant
     *
     * @return string
     */
    public function getNomDirigeant()
    {
        return $this->nomDirigeant;
    }

    /**
     * Set prenomDirigeant
     *
     * @param string $prenomDirigeant
     *
     * @return Companies
     */
    public function setPrenomDirigeant($prenomDirigeant)
    {
        $this->prenomDirigeant = $prenomDirigeant;

        return $this;
    }

    /**
     * Get prenomDirigeant
     *
     * @return string
     */
    public function getPrenomDirigeant()
    {
        return $this->prenomDirigeant;
    }

    /**
     * Set fonctionDirigeant
     *
     * @param string $fonctionDirigeant
     *
     * @return Companies
     */
    public function setFonctionDirigeant($fonctionDirigeant)
    {
        $this->fonctionDirigeant = $fonctionDirigeant;

        return $this;
    }

    /**
     * Get fonctionDirigeant
     *
     * @return string
     */
    public function getFonctionDirigeant()
    {
        return $this->fonctionDirigeant;
    }

    /**
     * Set emailDirigeant
     *
     * @param string $emailDirigeant
     *
     * @return Companies
     */
    public function setEmailDirigeant($emailDirigeant)
    {
        $this->emailDirigeant = $emailDirigeant;

        return $this;
    }

    /**
     * Get emailDirigeant
     *
     * @return string
     */
    public function getEmailDirigeant()
    {
        return $this->emailDirigeant;
    }

    /**
     * Set phoneDirigeant
     *
     * @param string $phoneDirigeant
     *
     * @return Companies
     */
    public function setPhoneDirigeant($phoneDirigeant)
    {
        $this->phoneDirigeant = $phoneDirigeant;

        return $this;
    }

    /**
     * Get phoneDirigeant
     *
     * @return string
     */
    public function getPhoneDirigeant()
    {
        return $this->phoneDirigeant;
    }

    /**
     * Set sector
     *
     * @param integer $sector
     *
     * @return Companies
     */
    public function setSector($sector)
    {
        $this->sector = $sector;

        return $this;
    }

    /**
     * Get sector
     *
     * @return integer
     */
    public function getSector()
    {
        return $this->sector;
    }

    /**
     * Set risk
     *
     * @param string $risk
     *
     * @return Companies
     */
    public function setRisk($risk)
    {
        $this->risk = $risk;

        return $this;
    }

    /**
     * Get risk
     *
     * @return string
     */
    public function getRisk()
    {
        return $this->risk;
    }

    /**
     * Set codeNaf
     *
     * @param string $codeNaf
     *
     * @return Companies
     */
    public function setCodeNaf($codeNaf)
    {
        $this->codeNaf = $codeNaf;

        return $this;
    }

    /**
     * Get codeNaf
     *
     * @return string
     */
    public function getCodeNaf()
    {
        return $this->codeNaf;
    }

    /**
     * Set libelleNaf
     *
     * @param string $libelleNaf
     *
     * @return Companies
     */
    public function setLibelleNaf($libelleNaf)
    {
        $this->libelleNaf = $libelleNaf;

        return $this;
    }

    /**
     * Get libelleNaf
     *
     * @return string
     */
    public function getLibelleNaf()
    {
        return $this->libelleNaf;
    }

    /**
     * Set added
     *
     * @param \DateTime $added
     *
     * @return Companies
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
     * @return Companies
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
     * Get idCompany
     *
     * @return integer
     */
    public function getIdCompany()
    {
        return $this->idCompany;
    }
}
