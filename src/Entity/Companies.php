<?php

namespace Unilend\Entity;

use Doctrine\Common\Collections\{ArrayCollection, Collection, Criteria};
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;
use Unilend\Entity\Traits\Timestampable;

/**
 * Companies
 *
 * @ORM\Entity(repositoryClass="Unilend\Repository\CompaniesRepository")
 * @ORM\HasLifecycleCallbacks
 */
class Companies
{
    use Timestampable;

    const INVALID_SIREN_EMPTY  = '000000000';
    const NAF_CODE_NO_ACTIVITY = '0000Z';

    const SAME_ADDRESS_FOR_POSTAL_AND_FISCAL      = 1;
    const DIFFERENT_ADDRESS_FOR_POSTAL_AND_FISCAL = 0;

    const CLIENT_STATUS_MANAGER             = 1;
    const CLIENT_STATUS_DELEGATION_OF_POWER = 2;
    const CLIENT_STATUS_EXTERNAL_CONSULTANT = 3;

    /** Warning, these constants are also setting , but added as constants for more clarity in the code*/
    const CLIENT_STATUS_EXTERNAL_COUNSEL_ACCOUNTANT    = 1;
    const CLIENT_STATUS_EXTERNAL_COUNSEL_CREDIT_BROKER = 2;
    const CLIENT_STATUS_EXTERNAL_COUNSEL_OTHER         = 3;
    const CLIENT_STATUS_EXTERNAL_COUNSEL_BANKER        = 4;

    /**
     * @var \Unilend\Entity\CompanyStatus
     *
     * @ORM\ManyToOne(targetEntity="Unilend\Entity\CompanyStatus")
     * @ORM\JoinColumns({
     *   @ORM\JoinColumn(name="id_status", referencedColumnName="id")
     * })
     */
    private $idStatus;

    /**
     * @var string
     *
     * @ORM\Column(name="email_facture", type="string", length=191, nullable=true)
     */
    private $emailFacture;

    /**
     * @var string
     *
     * @ORM\Column(name="name", type="text", length=16777215, nullable=true)
     *
     * @Assert\NotBlank()
     */
    private $name;

    /**
     * @var string
     *
     * @ORM\Column(name="forme", type="string", length=191, nullable=true)
     *
     * @Assert\NotBlank()
     */
    private $forme;

    /**
     * @var string
     *
     * @ORM\Column(name="legal_form_code", type="string", length=10, nullable=true)
     */
    private $legalFormCode;

    /**
     * @var string
     *
     * @ORM\Column(name="siren", type="string", length=15, nullable=true)
     *
     * @Assert\NotBlank()
     * @Assert\Length(min=9, max=14)
     */
    private $siren;

    /**
     * @var string
     *
     * @ORM\Column(name="siret", type="string", length=14, nullable=true)
     */
    private $siret;

    /**
     * @var int
     *
     * @ORM\Column(name="execices_comptables", type="smallint", nullable=true)
     */
    private $execicesComptables;

    /**
     * @var string
     *
     * @ORM\Column(name="rcs", type="string", length=45, nullable=true)
     */
    private $rcs;

    /**
     * @var string
     *
     * @ORM\Column(name="tribunal_com", type="string", length=191, nullable=true)
     */
    private $tribunalCom;

    /**
     * @var string
     *
     * @ORM\Column(name="activite", type="string", length=191, nullable=true)
     */
    private $activite;

    /**
     * @var float
     *
     * @ORM\Column(name="capital", type="float", precision=10, scale=0, nullable=true)
     *
     * @Assert\NotBlank()
     * @Assert\Type(type="numeric")
     */
    private $capital;

    /**
     * @var \DateTime
     *
     * @ORM\Column(name="date_creation", type="date", nullable=true)
     */
    private $dateCreation;

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
     * @ORM\Column(name="zip", type="string", length=10, nullable=true)
     */
    private $zip;

    /**
     * @var string
     *
     * @ORM\Column(name="city", type="string", length=191, nullable=true)
     */
    private $city;

    /**
     * @var int
     *
     * @ORM\Column(name="id_pays", type="integer", nullable=true)
     */
    private $idPays;

    /**
     * @var float
     *
     * @ORM\Column(name="latitude", type="decimal", precision=10, scale=8, nullable=true)
     */
    private $latitude;

    /**
     * @var float
     *
     * @ORM\Column(name="longitude", type="decimal", precision=11, scale=8, nullable=true)
     */
    private $longitude;

    /**
     * @var string
     *
     * @ORM\Column(name="phone", type="string", length=45, nullable=true)
     */
    private $phone;

    /**
     * @var int
     *
     * @ORM\Column(name="status_adresse_correspondance", type="smallint", nullable=true)
     */
    private $statusAdresseCorrespondance;

    /**
     * @var int
     *
     * @ORM\Column(name="status_client", type="smallint", nullable=true)
     */
    private $statusClient;

    /**
     * @var int
     *
     * @ORM\Column(name="status_conseil_externe_entreprise", type="smallint", nullable=true)
     */
    private $statusConseilExterneEntreprise;

    /**
     * @var string
     *
     * @ORM\Column(name="preciser_conseil_externe_entreprise", type="string", length=191, nullable=true)
     */
    private $preciserConseilExterneEntreprise;

    /**
     * @var string
     *
     * @ORM\Column(name="civilite_dirigeant", type="string", nullable=true)
     */
    private $civiliteDirigeant;

    /**
     * @var string
     *
     * @ORM\Column(name="nom_dirigeant", type="string", length=191, nullable=true)
     */
    private $nomDirigeant;

    /**
     * @var string
     *
     * @ORM\Column(name="prenom_dirigeant", type="string", length=191, nullable=true)
     */
    private $prenomDirigeant;

    /**
     * @var string
     *
     * @ORM\Column(name="fonction_dirigeant", type="string", length=191, nullable=true)
     */
    private $fonctionDirigeant;

    /**
     * @var string
     *
     * @ORM\Column(name="email_dirigeant", type="string", length=191, nullable=true)
     */
    private $emailDirigeant;

    /**
     * @var string
     *
     * @ORM\Column(name="phone_dirigeant", type="string", length=45, nullable=true)
     */
    private $phoneDirigeant;

    /**
     * @var int
     *
     * @ORM\Column(name="sector", type="integer", nullable=true)
     */
    private $sector;

    /**
     * @var string
     *
     * @ORM\Column(name="risk", type="string", length=45, nullable=true)
     */
    private $risk;

    /**
     * @var string
     *
     * @ORM\Column(name="code_naf", type="string", length=5, nullable=true)
     */
    private $codeNaf;

    /**
     * @var int
     *
     * @ORM\Column(name="id_company", type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="IDENTITY")
     */
    private $idCompany;

    /**
     * @var Companies|null
     *
     * @ORM\ManyToOne(targetEntity="Unilend\Entity\Companies")
     * @ORM\JoinColumns({
     *   @ORM\JoinColumn(name="id_parent_company", referencedColumnName="id_company")
     * })
     */
    private $idParentCompany;

    /**
     * @var CompanyAddress
     *
     * @ORM\OneToOne(targetEntity="Unilend\Entity\CompanyAddress")
     * @ORM\JoinColumn(name="id_address", referencedColumnName="id")
     */
    private $idAddress;

    /**
     * @var CompanyAddress
     *
     * @ORM\OneToOne(targetEntity="Unilend\Entity\CompanyAddress")
     * @ORM\JoinColumn(name="id_postal_address", referencedColumnName="id")
     */
    private $idPostalAddress;

    /**
     * @var Staff[]
     *
     * @ORM\OneToMany(targetEntity="Unilend\Entity\Staff", mappedBy="company", cascade={"persist"}, orphanRemoval=true)
     */
    private $staff;

    /**
     * @var ProjectParticipant[]
     *
     * @ORM\OneToMany(targetEntity="Unilend\Entity\ProjectParticipant", mappedBy="company", cascade={"persist"}, orphanRemoval=true)
     */
    private $projectParticipants;

    public function __construct()
    {
        $this->staff               = new ArrayCollection();
        $this->projectParticipants = new ArrayCollection();
    }

    /**
     * @param Clients|null $client
     *
     * @return Companies
     * @deprecated use $this->addStaff() instead
     *
     * Set idClientOwner
     *
     */
    public function setIdClientOwner(Clients $client = null)
    {
        if ($client) {
            $this->addStaff($client, Staff::STAFF_ROLE_OWNER);
        }

        return $this;
    }

    /**
     * @return Clients|null
     * @deprecated use $this->getStaff() instead
     *
     * Get idClientOwner
     *
     */
    public function getIdClientOwner()
    {
        foreach ($this->getStaff() as $staff) {
            if ($staff->hasRole(Staff::STAFF_ROLE_OWNER)) {
                return $staff->getClient();
            }
        }

        return null;
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
     * Set execicesComptables
     *
     * @param integer $execicesComptables
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
     * @return integer
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
     * Set capital
     *
     * @param float $capital
     *
     * @return Companies
     */
    public function setCapital($capital)
    {
        $this->capital = $this->cleanCapital($capital);

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
        /** @todo to be removed when projects is fully under doctrine */
        if (null !== $this->dateCreation && $this->dateCreation->getTimestamp() < 0) {
            return null;
        }

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
        $this->phone = $this->cleanPhoneNumber($phone);

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
     * @param integer $statusAdresseCorrespondance
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
     * @return integer
     */
    public function getStatusAdresseCorrespondance()
    {
        return $this->statusAdresseCorrespondance;
    }

    /**
     * Set statusClient
     *
     * @param integer $statusClient
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
     * @return integer
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
        $this->nomDirigeant = $this->normalizeName($nomDirigeant);

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
        $this->prenomDirigeant = $this->normalizeName($prenomDirigeant);

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
     * Get idCompany
     *
     * @return integer
     */
    public function getIdCompany()
    {
        return $this->idCompany;
    }

    /**
     * Set idParentCompany
     *
     * @param Companies $idParentCompany
     *
     * @return Companies
     */
    public function setIdParentCompany(Companies $idParentCompany = null)
    {
        $this->idParentCompany = $idParentCompany;

        return $this;
    }

    /**
     * Get idParentCompany
     *
     * @return Companies
     */
    public function getIdParentCompany()
    {
        return $this->idParentCompany;
    }

    /**
     * @return string|null
     */
    public function getLegalFormCode()
    {
        return $this->legalFormCode;
    }

    /**
     * @param string $legalFormCode
     */
    public function setLegalFormCode($legalFormCode = null)
    {
        $this->legalFormCode = $legalFormCode;
    }

    /**
     * @return null|CompanyStatus
     */
    public function getIdStatus()
    {
        return $this->idStatus;
    }

    /**
     * @param CompanyStatus $idStatus
     *
     * @return Companies
     */
    public function setIdStatus(CompanyStatus $idStatus)
    {
        $this->idStatus = $idStatus;

        return $this;
    }

    /**
     * @param $name
     *
     * @return string
     */
    private function normalizeName($name)
    {
        $name = strtolower($name);

        $pos = strrpos($name, '-');
        if ($pos === false) {
            return ucwords($name);
        } else {
            $tabName = explode('-', $name);
            $newName = '';
            $i       = 0;
            foreach ($tabName as $name) {
                $newName .= ($i == 0 ? '' : '-') . ucwords($name);
                $i++;
            }

            return $newName;
        }
    }

    /**
     * @param string $number
     *
     * @return string
     */
    private function cleanPhoneNumber($number)
    {
        return str_replace([' ', '.'], '', $number);
    }

    private function cleanCapital($capital)
    {
        return str_replace([' ', '.'], '', $capital);
    }

    /**
     * @ORM\PreFlush
     */
    public function setSectorAccordingToNaf()
    {
        if ($this->codeNaf == self::NAF_CODE_NO_ACTIVITY) {
            return;
        }

        if (in_array(substr($this->codeNaf, 0, 2), ['01', '02', '03'])) {
            $this->sector = 1;
        }

        if (in_array(substr($this->codeNaf, 0, 2), ['10', '11'])) {
            $this->sector = 2;
        }

        if (in_array(substr($this->codeNaf, 0, 2), ['41', '42', '43', '71'])) {
            $this->sector = 3;
        }

        if (in_array(substr($this->codeNaf, 0, 2), ['45', '46', '47', '95'])) {
            $this->sector = 4;
        }

        if (in_array(substr($this->codeNaf, 0, 2), ['59', '60', '90', '91'])) {
            $this->sector = 6;
        }

        if (in_array(substr($this->codeNaf, 0, 2), ['55'])) {
            $this->sector = 7;
        }

        if (in_array(substr($this->codeNaf, 0, 2), ['16', '17', '18', '19', '20', '22', '23', '24', '25', '26', '27', '28', '29', '30', '31', '32', '33', '35', '36'])) {
            $this->sector = 8;
        }

        if (in_array(substr($this->codeNaf, 0, 2), ['61', '62', '63'])) {
            $this->sector = 9;
        }

        if (in_array(substr($this->codeNaf, 0, 2), ['21', '75', '86'])) {
            $this->sector = 10;
        }

        if (in_array(substr($this->codeNaf, 0, 2), ['56'])) {
            $this->sector = 11;
        }

        if (in_array(substr($this->codeNaf, 0, 2), ['58', '65', '66', '68', '69', '70', '73', '74', '77', '78', '79', '80', '81', '82', '96', '97'])) {
            $this->sector = 12;
        }

        if (in_array(substr($this->codeNaf, 0, 2), ['13', '14', '15'])) {
            $this->sector = 13;
        }

        if (in_array(substr($this->codeNaf, 0, 2), ['49', '50', '51', '52', '53'])) {
            $this->sector = 14;
        }

        if (in_array(substr($this->codeNaf, 0, 2), ['05', '06', '07', '08', '09', '12', '37', '38', '39', '64', '72', '84', '85', '87', '88', '92', '93', '94', '98', '99'])) {
            $this->sector = 15;
        }
    }

    /**
     * @ORM\PreFlush
     */
    public function checkCompanyNameCreation()
    {
        if (is_numeric($this->name) || 0 === strcasecmp($this->name, 'Monsieur') || 0 === strcasecmp($this->name, 'Madame')) {
            trigger_error('An invalid company name "' . $this->name . '" detected for siren : ' . $this->siren . '- trace : ' . serialize(debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, 5)), E_USER_WARNING);
        }
    }

    /**
     * @return CompanyAddress|null
     */
    public function getIdAddress(): ?CompanyAddress
    {
        if (null !== $this->idAddress && empty($this->idAddress->getId())) {
            $this->idAddress = null;
        }

        return $this->idAddress;
    }

    /**
     * @param null|CompanyAddress $idAddress
     *
     * @return Companies
     */
    public function setIdAddress(?CompanyAddress $idAddress): Companies
    {
        $this->idAddress = $idAddress;

        return $this;
    }

    /**
     * @return CompanyAddress|null
     */
    public function getIdPostalAddress(): ?CompanyAddress
    {
        if (null !== $this->idPostalAddress && empty($this->idPostalAddress->getId())) {
            $this->idPostalAddress = null;
        }

        return $this->idPostalAddress;
    }

    /**
     * @param null|CompanyAddress $idPostalAddress
     *
     * @return Companies
     */
    public function setIdPostalAddress(?CompanyAddress $idPostalAddress): Companies
    {
        $this->idPostalAddress = $idPostalAddress;

        return $this;
    }

    /**
     * @param Clients|null $client
     *
     * @return Staff[]|Collection
     */
    public function getStaff(?Clients $client = null): iterable
    {
        $criteria = new Criteria();

        if ($client) {
            $criteria->where(Criteria::expr()->eq('client', $client));
        }

        return $this->staff->matching($criteria);
    }

    /**
     * @param Clients $client
     * @param string  $role
     *
     * @return Staff
     */
    public function addStaff(Clients $client, string $role): Staff
    {
        $staff = $this->getStaff($client);

        if ($staff->count()) {
            $theStaff = $staff->first();
        } else {
            $theStaff = (new Staff())->setClient($client)->setCompany($this);
        }

        $theStaff->addRoles([$role]);
        $this->staff->add($theStaff);

        return $theStaff;
    }

    /**
     * @param Staff $staff
     *
     * @return Companies
     */
    public function removeStaff(Staff $staff): Companies
    {
        $this->staff->removeElement($staff);

        return $this;
    }

    /**
     * @param Projects|null $project
     *
     * @return ArrayCollection|ProjectParticipant[]
     */
    public function getProjectParticipants(?Projects $project = null): iterable
    {
        $criteria = new Criteria();
        if ($project) {
            $criteria->where(Criteria::expr()->eq('project', $project));
        }

        return $this->projectParticipants->matching($criteria);
    }

    /**
     * @param Projects $project
     *
     * @return bool
     */
    public function isArranger(Projects $project)
    {
        $projectParticipant = $this->getProjectParticipants($project)->first();
        if ($projectParticipant instanceof ProjectParticipant) {
            return $projectParticipant->isArranger();
        }

        return false;
    }
}
