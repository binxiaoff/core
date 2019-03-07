<?php

namespace Unilend\Bundle\CoreBusinessBundle\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\Mapping as ORM;
use Hashids\Hashids;
use Ramsey\Uuid\Exception\UnsatisfiedDependencyException;
use Ramsey\Uuid\Uuid;
use Symfony\Component\Security\Core\Encoder\EncoderAwareInterface;
use Symfony\Component\Security\Core\User\{EquatableInterface, UserInterface};

/**
 * Clients
 *
 * @ORM\Table(name="clients", indexes={
 *     @ORM\Index(name="hash", columns={"hash"}),
 *     @ORM\Index(name="email", columns={"email"}),
 *     @ORM\Index(name="idx_client_nom", columns={"nom"}),
 *     @ORM\Index(name="idx_clients_id_client_status_history", columns={"id_client_status_history"})})
 * @ORM\Entity(repositoryClass="Unilend\Bundle\CoreBusinessBundle\Repository\ClientsRepository")
 * @ORM\HasLifecycleCallbacks
 */
class Clients implements UserInterface, EquatableInterface, EncoderAwareInterface
{
    const TYPE_PERSON                 = 1;
    const TYPE_LEGAL_ENTITY           = 2;
    const TYPE_PERSON_FOREIGNER       = 3;
    const TYPE_LEGAL_ENTITY_FOREIGNER = 4;

    const SUBSCRIPTION_STEP_PERSONAL_INFORMATION = 1;
    const SUBSCRIPTION_STEP_DOCUMENTS            = 2;
    const SUBSCRIPTION_STEP_MONEY_DEPOSIT        = 3;

    const TITLE_MISS      = 'Mme';
    const TITLE_MISTER    = 'M.';
    const TITLE_UNDEFINED = '';

    const NEWSLETTER_OPT_IN_ENROLLED     = 1;
    const NEWSLETTER_OPT_IN_NOT_ENROLLED = 2;

    /** Legacy welcome offer before separating them and adding types */
    const ORIGIN_WELCOME_OFFER      = 1;
    const ORIGIN_WELCOME_OFFER_HOME = 2;
    const ORIGIN_WELCOME_OFFER_LP   = 3;

    const ROLE_USER           = 'ROLE_USER';
    const ROLE_LENDER         = 'ROLE_LENDER';
    const ROLE_BORROWER       = 'ROLE_BORROWER';
    const ROLE_PARTNER        = 'ROLE_PARTNER';
    const ROLE_PARTNER_ADMIN  = 'ROLE_PARTNER_ADMIN';
    const ROLE_PARTNER_USER   = 'ROLE_PARTNER_USER';
    const ROLE_DEBT_COLLECTOR = 'ROLE_DEBT_COLLECTOR';

    const ROLES = [
        self::ROLE_USER,
        self::ROLE_LENDER,
        self::ROLE_BORROWER,
        self::ROLE_PARTNER,
        self::ROLE_DEBT_COLLECTOR
    ];

    const PASSWORD_ENCODER_MD5 = 'md5';

    /**
     * @var string
     *
     * @ORM\Column(name="hash", type="string", length=191)
     */
    private $hash;

    /**
     * @var string
     *
     * @ORM\Column(name="id_langue", type="string", length=5)
     */
    private $idLangue = 'fr';

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
     * @ORM\Column(name="nom_usage", type="string", length=191, nullable=true)
     */
    private $nomUsage;

    /**
     * @var string
     *
     * @ORM\Column(name="prenom", type="string", length=191, nullable=true)
     */
    private $prenom;

    /**
     * @var string
     *
     * @ORM\Column(name="slug", type="string", length=191, nullable=true)
     */
    private $slug;

    /**
     * @var string
     *
     * @ORM\Column(name="fonction", type="string", length=191, nullable=true)
     */
    private $fonction;

    /**
     * @var \DateTime
     *
     * @ORM\Column(name="naissance", type="date", nullable=true)
     */
    private $naissance;

    /**
     * @var int
     *
     * @ORM\Column(name="id_pays_naissance", type="integer", nullable=true)
     */
    private $idPaysNaissance;

    /**
     * @var string
     *
     * @ORM\Column(name="ville_naissance", type="string", length=191, nullable=true)
     */
    private $villeNaissance;

    /**
     * @var string
     *
     * @ORM\Column(name="insee_birth", type="string", length=16, nullable=true)
     */
    private $inseeBirth;

    /**
     * @var int
     *
     * @ORM\Column(name="id_nationalite", type="integer", nullable=true)
     */
    private $idNationalite;

    /**
     * @var bool
     *
     * @ORM\Column(name="us_person", type="boolean", nullable=true)
     */
    private $usPerson;

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
     * @ORM\Column(name="email", type="string", length=191, nullable=true)
     */
    private $email;

    /**
     * @var string
     *
     * @ORM\Column(name="password", type="string", length=191, nullable=true)
     */
    private $password;

    /**
     * @var string
     *
     * @ORM\Column(name="secrete_question", type="string", length=191, nullable=true)
     */
    private $secreteQuestion;

    /**
     * @var string
     *
     * @ORM\Column(name="secrete_reponse", type="string", length=191, nullable=true)
     */
    private $secreteReponse;

    /**
     * @var int
     *
     * @ORM\Column(name="type", type="smallint", nullable=true)
     */
    private $type;

    /**
     * @var int
     *
     * @ORM\Column(name="funds_origin", type="smallint", nullable=true)
     */
    private $fundsOrigin;

    /**
     * @var string
     *
     * @ORM\Column(name="funds_origin_detail", type="string", nullable=true)
     */
    private $fundsOriginDetail;

    /**
     * @var int
     *
     * @ORM\Column(name="etape_inscription_preteur", type="smallint", nullable=true)
     */
    private $etapeInscriptionPreteur;

    /**
     * @var int
     *
     * @ORM\Column(name="status_inscription_preteur", type="smallint", nullable=true)
     */
    private $statusInscriptionPreteur;

    /**
     * @var string
     *
     * @ORM\Column(name="source", type="string", length=191, nullable=true)
     */
    private $source;

    /**
     * @var string
     *
     * @ORM\Column(name="source2", type="string", length=191, nullable=true)
     */
    private $source2;

    /**
     * @var string
     *
     * @ORM\Column(name="source3", type="string", length=191, nullable=true)
     */
    private $source3;

    /**
     * @var string
     *
     * @ORM\Column(name="slug_origine", type="string", length=191, nullable=true)
     */
    private $slugOrigine;

    /**
     * @var int
     *
     * @ORM\Column(name="origine", type="smallint", nullable=true)
     */
    private $origine;

    /**
     * @var int
     *
     * @ORM\Column(name="optin1", type="smallint", nullable=true)
     */
    private $optin1;

    /**
     * @var int
     *
     * @ORM\Column(name="optin2", type="smallint", nullable=true)
     */
    private $optin2;

    /**
     * @var ClientsStatusHistory
     *
     * @ORM\ManyToOne(targetEntity="Unilend\Bundle\CoreBusinessBundle\Entity\ClientsStatusHistory")
     * @ORM\JoinColumns({
     *   @ORM\JoinColumn(name="id_client_status_history", referencedColumnName="id")
     * })
     */
    private $idClientStatusHistory;

    /**
     * @var \DateTime
     * @ORM\Column(name="personal_data_updated", type="datetime", nullable=true)
     */
    private $personalDataUpdated;

    /**
     * @var \DateTime
     *
     * @ORM\Column(name="added", type="datetime")
     */
    private $added;

    /**
     * @var \DateTime
     *
     * @ORM\Column(name="updated", type="datetime", nullable=true)
     */
    private $updated;

    /**
     * @var \DateTime
     *
     * @ORM\Column(name="lastlogin", type="datetime", nullable=true)
     */
    private $lastlogin;

    /**
     * @var int
     *
     * @ORM\Column(name="id_client", type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="IDENTITY")
     */
    private $idClient;

    /**
     * @var Attachment[]
     *
     * @ORM\OneToMany(targetEntity="Unilend\Bundle\CoreBusinessBundle\Entity\Attachment", mappedBy="idClient")
     */
    private $attachments;

    /**
     * @var Wallet[]
     *
     * @ORM\OneToMany(targetEntity="Unilend\Bundle\CoreBusinessBundle\Entity\Wallet", mappedBy="idClient")
     */
    private $wallets;

    /**
     * @var string
     *
     * @ORM\Column(name="sponsor_code", type="string", length=50, nullable=true)
     */
    private $sponsorCode;

    /**
     * @var ClientsAdresses[];
     *
     * @ORM\OneToMany(targetEntity="Unilend\Bundle\CoreBusinessBundle\Entity\ClientsAdresses", mappedBy="idClient")
     */
    private $clientsAddresses;

    /**
     * @var ClientAddress
     *
     * @ORM\OneToOne(targetEntity="Unilend\Bundle\CoreBusinessBundle\Entity\ClientAddress")
     * @ORM\JoinColumn(name="id_address", referencedColumnName="id")
     */
    private $idAddress;

    /**
     * @var ClientAddress
     *
     * @ORM\OneToOne(targetEntity="Unilend\Bundle\CoreBusinessBundle\Entity\ClientAddress")
     * @ORM\JoinColumn(name="id_postal_address", referencedColumnName="id")
     */
    private $idPostalAddress;

    /**
     * @var CompanyClient|null
     *
     * @ORM\OneToOne(targetEntity="Unilend\Bundle\CoreBusinessBundle\Entity\CompanyClient", mappedBy="idClient")
     */
    private $companyClient;

    /**
     * @var bool
     */
    private $userOnlyDefaultEncoder;

    /**
     * @var array
     *
     * @ORM\Column(type="json")
     */
    private $roles = [];

    /**
     * Clients constructor.
     */
    public function __construct()
    {
        $this->attachments      = new ArrayCollection();
        $this->wallets          = new ArrayCollection();
        $this->clientsAddresses = new ArrayCollection();
    }

    /**
     * Set hash
     *
     * @param string $hash
     *
     * @return Clients
     */
    public function setHash($hash)
    {
        $this->hash = $hash;

        return $this;
    }

    /**
     * Get hash
     *
     * @return string
     */
    public function getHash()
    {
        return $this->hash;
    }

    /**
     * Set idLangue
     *
     * @param string $idLangue
     *
     * @return Clients
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
     * Set civilite
     *
     * @param string $civilite
     *
     * @return Clients
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
     * @return Clients
     */
    public function setNom($nom)
    {
        $this->nom = $this->normalizeName($nom);

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
     * Set nomUsage
     *
     * @param string $nomUsage
     *
     * @return Clients
     */
    public function setNomUsage($nomUsage)
    {
        if (empty($nomUsage)) {
            $this->nomUsage = '';
        } else {
            $this->nomUsage = $this->normalizeName($nomUsage);
        }

        return $this;
    }

    /**
     * Get nomUsage
     *
     * @return string
     */
    public function getNomUsage()
    {
        return $this->nomUsage;
    }

    /**
     * Set prenom
     *
     * @param string $prenom
     *
     * @return Clients
     */
    public function setPrenom($prenom)
    {
        $this->prenom = $this->normalizeName($prenom);

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
     * Set slug
     *
     * @param string $slug
     *
     * @return Clients
     */
    public function setSlug($slug)
    {
        $this->slug = \URLify::filter($slug);

        return $this;
    }

    /**
     * Get slug
     *
     * @return string
     */
    public function getSlug()
    {
        return $this->slug;
    }

    /**
     * Set fonction
     *
     * @param string $fonction
     *
     * @return Clients
     */
    public function setFonction($fonction)
    {
        $this->fonction = $fonction;

        return $this;
    }

    /**
     * Get fonction
     *
     * @return string
     */
    public function getFonction()
    {
        return $this->fonction;
    }

    /**
     * Set naissance
     *
     * @param \DateTime $naissance
     *
     * @return Clients
     */
    public function setNaissance($naissance)
    {
        $this->naissance = $naissance;

        return $this;
    }

    /**
     * Get naissance
     *
     * @return \DateTime
     */
    public function getNaissance()
    {
        return $this->naissance;
    }

    /**
     * Set idPaysNaissance
     *
     * @param integer $idPaysNaissance
     *
     * @return Clients
     */
    public function setIdPaysNaissance($idPaysNaissance)
    {
        $this->idPaysNaissance = $idPaysNaissance;

        return $this;
    }

    /**
     * Get idPaysNaissance
     *
     * @return integer
     */
    public function getIdPaysNaissance()
    {
        return $this->idPaysNaissance;
    }

    /**
     * Set villeNaissance
     *
     * @param string $villeNaissance
     *
     * @return Clients
     */
    public function setVilleNaissance($villeNaissance)
    {
        $this->villeNaissance = $villeNaissance;

        return $this;
    }

    /**
     * Get villeNaissance
     *
     * @return string
     */
    public function getVilleNaissance()
    {
        return $this->villeNaissance;
    }

    /**
     * Set inseeBirth
     *
     * @param string $inseeBirth
     *
     * @return Clients
     */
    public function setInseeBirth($inseeBirth)
    {
        $this->inseeBirth = $inseeBirth;

        return $this;
    }

    /**
     * Get inseeBirth
     *
     * @return string
     */
    public function getInseeBirth()
    {
        return $this->inseeBirth;
    }

    /**
     * Set idNationalite
     *
     * @param integer $idNationalite
     *
     * @return Clients
     */
    public function setIdNationalite($idNationalite)
    {
        $this->idNationalite = $idNationalite;

        return $this;
    }

    /**
     * Get idNationalite
     *
     * @return integer
     */
    public function getIdNationalite()
    {
        return $this->idNationalite;
    }

    /**
     * @return null|bool
     */
    public function getUsPerson(): ?bool
    {
        return $this->usPerson;
    }

    /**
     * @param bool $usPerson
     *
     * @return Clients
     */
    public function setUsPerson(bool $usPerson): Clients
    {
        $this->usPerson = $usPerson;

        return $this;
    }

    /**
     * Set telephone
     *
     * @param string $telephone
     *
     * @return Clients
     */
    public function setTelephone($telephone)
    {
        $this->telephone = $this->cleanPhoneNumber($telephone);

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
     * @return Clients
     */
    public function setMobile($mobile)
    {
        $this->mobile = $this->cleanPhoneNumber($mobile);

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
     * Set email
     *
     * @param string $email
     *
     * @return Clients
     */
    public function setEmail($email)
    {
        $this->email = $email;

        return $this;
    }

    /**
     * Get email
     *
     * @return string
     */
    public function getEmail()
    {
        return $this->email;
    }

    /**
     * Set password
     *
     * @param string $password
     *
     * @return Clients
     */
    public function setPassword($password)
    {
        $this->password = $password;

        return $this;
    }

    /**
     * Get password
     *
     * @return string
     */
    public function getPassword()
    {
        return $this->password;
    }

    /**
     * Set secreteQuestion
     *
     * @param string $secreteQuestion
     *
     * @return Clients
     */
    public function setSecreteQuestion($secreteQuestion)
    {
        $this->secreteQuestion = $secreteQuestion;

        return $this;
    }

    /**
     * Get secreteQuestion
     *
     * @return string
     */
    public function getSecreteQuestion()
    {
        return $this->secreteQuestion;
    }

    /**
     * Set secreteReponse
     *
     * @param string $secreteReponse
     *
     * @return Clients
     */
    public function setSecreteReponse($secreteReponse)
    {
        $this->secreteReponse = md5($secreteReponse);

        return $this;
    }

    /**
     * Get secreteReponse
     *
     * @return string
     */
    public function getSecreteReponse()
    {
        return $this->secreteReponse;
    }

    /**
     * Set type
     *
     * @param integer $type
     *
     * @return Clients
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
     * Set etapeInscriptionPreteur
     *
     * @param integer $etapeInscriptionPreteur
     *
     * @return Clients
     */
    public function setEtapeInscriptionPreteur($etapeInscriptionPreteur)
    {
        $this->etapeInscriptionPreteur = $etapeInscriptionPreteur;

        return $this;
    }

    /**
     * Get etapeInscriptionPreteur
     *
     * @return integer
     */
    public function getEtapeInscriptionPreteur()
    {
        return $this->etapeInscriptionPreteur;
    }

    /**
     * Set statusInscriptionPreteur
     *
     * @param integer $statusInscriptionPreteur
     *
     * @return Clients
     */
    public function setStatusInscriptionPreteur($statusInscriptionPreteur)
    {
        $this->statusInscriptionPreteur = $statusInscriptionPreteur;

        return $this;
    }

    /**
     * Get statusInscriptionPreteur
     *
     * @return integer
     */
    public function getStatusInscriptionPreteur()
    {
        return $this->statusInscriptionPreteur;
    }

    /**
     * Set source
     *
     * @param string $source
     *
     * @return Clients
     */
    public function setSource($source)
    {
        $this->source = $source;

        return $this;
    }

    /**
     * Get source
     *
     * @return string
     */
    public function getSource()
    {
        return $this->source;
    }

    /**
     * Set source2
     *
     * @param string $source2
     *
     * @return Clients
     */
    public function setSource2($source2)
    {
        $this->source2 = $source2;

        return $this;
    }

    /**
     * Get source2
     *
     * @return string
     */
    public function getSource2()
    {
        return $this->source2;
    }

    /**
     * Set source3
     *
     * @param string $source3
     *
     * @return Clients
     */
    public function setSource3($source3)
    {
        $this->source3 = $source3;

        return $this;
    }

    /**
     * Get source3
     *
     * @return string
     */
    public function getSource3()
    {
        return $this->source3;
    }

    /**
     * Set slugOrigine
     *
     * @param string $slugOrigine
     *
     * @return Clients
     */
    public function setSlugOrigine($slugOrigine)
    {
        $this->slugOrigine = $slugOrigine;

        return $this;
    }

    /**
     * Get slugOrigine
     *
     * @return string
     */
    public function getSlugOrigine()
    {
        return $this->slugOrigine;
    }

    /**
     * Set origine
     *
     * @param integer $origine
     *
     * @return Clients
     */
    public function setOrigine($origine)
    {
        $this->origine = $origine;

        return $this;
    }

    /**
     * Get origine
     *
     * @return integer
     */
    public function getOrigine()
    {
        return $this->origine;
    }

    /**
     * Set optin1
     *
     * @param integer $optin1
     *
     * @return Clients
     */
    public function setOptin1($optin1)
    {
        $this->optin1 = $optin1;

        return $this;
    }

    /**
     * Get optin1
     *
     * @return integer
     */
    public function getOptin1()
    {
        return $this->optin1;
    }

    /**
     * Set optin2
     *
     * @param integer $optin2
     *
     * @return Clients
     */
    public function setOptin2($optin2)
    {
        $this->optin2 = $optin2;

        return $this;
    }

    /**
     * Get optin2
     *
     * @return integer
     */
    public function getOptin2()
    {
        return $this->optin2;
    }

    /**
     * Set idClientsStatusHistory
     *
     * @param ClientsStatusHistory $idClientStatusHistory
     *
     * @return Clients
     */
    public function setIdClientStatusHistory(?ClientsStatusHistory $idClientStatusHistory): Clients
    {
        $this->idClientStatusHistory = $idClientStatusHistory;

        return $this;
    }

    /**
     * Get idClientsStatusHistory
     *
     * @return ClientsStatusHistory
     */
    public function getIdClientStatusHistory(): ?ClientsStatusHistory
    {
        return $this->idClientStatusHistory;
    }

    /**
     * Set added
     *
     * @param \DateTime $added
     *
     * @return Clients
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
     * @return Clients
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
     * Set lastlogin
     *
     * @param \DateTime $lastlogin
     *
     * @return Clients
     */
    public function setLastlogin($lastlogin)
    {
        $this->lastlogin = $lastlogin;

        return $this;
    }

    /**
     * Get lastlogin
     *
     * @return \DateTime
     */
    public function getLastlogin()
    {
        return $this->lastlogin;
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
     * @ORM\PrePersist
     */
    public function setAddedValue()
    {
        if (! $this->added instanceof \DateTime || 1 > $this->getAdded()->getTimestamp()) {
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

    /**
     * @ORM\PrePersist
     */
    public function setHashValue()
    {
        if (is_null($this->hash)) {
            try {
                $this->hash = $this->generateHash();
            } catch (UnsatisfiedDependencyException $exception) {
                $this->hash = md5(uniqid());
            }
        }
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

    /**
     * Get fundsOrigin
     *
     * @return integer
     */
    public function getFundsOrigin()
    {
        return $this->fundsOrigin;
    }

    /**
     * Set fundsOrigin
     *
     * @param int $fundsOrigin
     *
     * @return Clients
     */
    public function setFundsOrigin($fundsOrigin)
    {
        $this->fundsOrigin = $fundsOrigin;

        return $this;
    }

    /**
     * Get fundsOriginDetail
     *
     * @return string
     */
    public function getFundsOriginDetail()
    {
        return $this->fundsOriginDetail;
    }

    /**
     * Set fundsOriginDetail
     *
     * @param string $fundsOriginDetail
     *
     * @return Clients
     */
    public function setFundsOriginDetail($fundsOriginDetail)
    {
        $this->fundsOriginDetail = $fundsOriginDetail;

        return $this;
    }

    /**
     * @return string
     */
    private function generateHash()
    {
        $uuid4 = Uuid::uuid4();
        return $uuid4->toString();
    }

    /**
     * Get client attachments
     *
     * @param boolean $includeArchived
     *
     * @return Attachment[]
     */
    public function getAttachments($includeArchived = false)
    {
        if (false === $includeArchived) {
            $attachments = [];
            foreach ($this->attachments as $attachment) {
                if (null === $attachment->getArchived()) {
                    $attachments[] = $attachment;
                }
            }

            return $attachments;
        }

        return $this->attachments;
    }

    /**
     * Get wallets
     *
     * @return Wallet[]
     */
    public function getWallets()
    {
        return $this->wallets;
    }

    /**
     * Check whether client has a borrower wallet or not
     *
     * @return bool
     */
    public function isBorrower(): bool
    {
        return $this->hasRole(self::ROLE_BORROWER);
    }

    /**
     * Check whether client has a lender wallet or not
     *
     * @return bool
     */
    public function isLender(): bool
    {
        return $this->hasRole(self::ROLE_LENDER);
    }

    /**
     * Check whether client has a partner wallet or not
     *
     * @return bool
     */
    public function isPartner(): bool
    {
        return $this->hasRole(self::ROLE_PARTNER);
    }

    /**
     * Check whether client has a debt collector wallet or not
     *
     * @return bool
     */
    public function isDebtCollector(): bool
    {
        return $this->hasRole(self::ROLE_DEBT_COLLECTOR);
    }

    /**
     * @param string $walletType
     *
     * @return Wallet|null
     */
    public function getWalletByType(string $walletType): ?Wallet
    {
        foreach ($this->wallets as $wallet) {
            if ($walletType === $wallet->getIdType()->getLabel()) {
                return $wallet;
            }
        }

        return null;
    }

    /**
     * @return bool
     */
    public function isNaturalPerson()
    {
        return in_array($this->type, [self::TYPE_PERSON, self::TYPE_PERSON_FOREIGNER]);
    }

    /**
     * @return string
     */
    public function getSponsorCode()
    {
        return $this->sponsorCode;
    }

    /**
     * @param string $sponsorCode
     *
     * @return Clients
     */
    public function setSponsorCode($sponsorCode = null)
    {
        $this->sponsorCode = $sponsorCode;

        return $this;
    }

    /**
     * @ORM\PreUpdate()
     */
    public function setSponsorCodeValue()
    {
        if (
            empty($this->sponsorCode)
            && in_array($this->type, [self::TYPE_PERSON, self::TYPE_PERSON_FOREIGNER, self::TYPE_LEGAL_ENTITY, self::TYPE_LEGAL_ENTITY_FOREIGNER])
        ) {
            $this->sponsorCode = $this->createSponsorCode();
        }
    }

    //@todo once sponsor codes are repaired this method should be private

    /**
     * @return null|string
     */
    public function createSponsorCode()
    {
        if (false === empty($this->nom)) {
            $lastName = \URLify::filter($this->nom);
            $lastName = str_replace('-', '', $lastName);
            $hashId   = new Hashids('', 6);

            return $hashId->encode($this->idClient) . ucfirst(strtolower($lastName));
        }

        return null;
    }

    /**
     * @return ClientsAdresses[]
     */
    public function getClientsAddresses()
    {
        return $this->clientsAddresses;
    }

    /**
     * @param ClientsAdresses[] $clientsAddresses
     *
     * @return Clients
     */
    public function setClientsAddresses($clientsAddresses)
    {
        $this->clientsAddresses = $clientsAddresses;

        return $this;
    }

    /**
     * @return ClientAddress|null
     */
    public function getIdAddress(): ?ClientAddress
    {
        if (null !== $this->idAddress && empty($this->idAddress->getId())) {
            $this->idAddress = null;
        }

        return $this->idAddress;
    }

    /**
     * @param null|ClientAddress $idAddress
     *
     * @return Clients
     */
    public function setIdAddress(?ClientAddress $idAddress): Clients
    {
        $this->idAddress = $idAddress;

        return $this;
    }

    /**
     * @return ClientAddress|null
     */
    public function getIdPostalAddress(): ?ClientAddress
    {
        if (null !== $this->idPostalAddress && empty($this->idPostalAddress->getId())) {
            $this->idPostalAddress = null;
        }

        return $this->idPostalAddress;
    }

    /**
     * @param null|ClientAddress $idPostalAddress
     *
     * @return Clients
     */
    public function setIdPostalAddress(?ClientAddress $idPostalAddress): Clients
    {
        $this->idPostalAddress = $idPostalAddress;

        return $this;
    }

    /**
     * @return \DateTime|null
     */
    public function getPersonalDataUpdated(): ?\DateTime
    {
        return $this->personalDataUpdated;
    }

    /**
     * @param \DateTime|null $personalDataUpdated
     *
     * @return Clients
     */
    public function setPersonalDataUpdated(?\DateTime $personalDataUpdated = null): Clients
    {
        if (null === $personalDataUpdated) {
            $personalDataUpdated = new \DateTime();
        }

        $this->personalDataUpdated = $personalDataUpdated;

        return $this;
    }

    /**
     * @return CompanyClient|null
     */
    public function getCompanyClient(): ?CompanyClient
    {
        return $this->companyClient;
    }

    /**
     * @return string
     */
    public function getInitials(): string
    {
        return mb_substr($this->getPrenom(), 0, 1) . mb_substr($this->getNom(), 0, 1);
    }

    /**
     * @return bool
     */
    public function isGrantedLogin(): bool
    {
        return in_array($this->getIdClientStatusHistory()->getIdStatus()->getId(), ClientsStatus::GRANTED_LOGIN);
    }

    /**
     * @return bool
     */
    public function isGrantedLenderRead(): bool
    {
        return in_array($this->getIdClientStatusHistory()->getIdStatus()->getId(), ClientsStatus::GRANTED_LENDER_ACCOUNT_READ);
    }

    /**
     * @return bool
     *
     */
    public function isGrantedLenderDeposit(): bool
    {
        return in_array($this->getIdClientStatusHistory()->getIdStatus()->getId(), ClientsStatus::GRANTED_LENDER_DEPOSIT);
    }

    /**
     * @return bool
     */
    public function isGrantedLenderWithdraw(): bool
    {
        return in_array($this->getIdClientStatusHistory()->getIdStatus()->getId(), ClientsStatus::GRANTED_LENDER_WITHDRAW);
    }

    /**
     * @return bool
     */
    public function isGreantedLenderSponsorship(): bool
    {
        return in_array($this->getIdClientStatusHistory()->getIdStatus()->getId(), ClientsStatus::GRANTED_LENDER_SPONSORSHIP);
    }

    /**
     * @return bool
     */
    public function isValidated(): bool
    {
        return $this->getIdClientStatusHistory()->getIdStatus()->getId() === ClientsStatus::STATUS_VALIDATED;
    }

    /**
     * @return bool
     */
    public function isInCompleteness(): bool
    {
        return in_array($this->getIdClientStatusHistory()->getIdStatus()->getId(), [ClientsStatus::STATUS_COMPLETENESS, ClientsStatus::STATUS_COMPLETENESS_REMINDER]);
    }

    /**
     * @return bool
     */
    public function isInSubscription(): bool
    {
        return $this->getIdClientStatusHistory()->getIdStatus()->getId() === ClientsStatus::STATUS_CREATION;
    }

    /**
     * @return bool
     */
    public function isSuspended(): bool
    {
        return $this->getIdClientStatusHistory()->getIdStatus()->getId() === ClientsStatus::STATUS_SUSPENDED;
    }

    /**
     * @inheritDoc
     */
    public function getEncoderName(): ?string
    {
        if (true !== $this->userOnlyDefaultEncoder && 1 === preg_match('/^[0-9a-f]{32}$/', $this->password)) {
            return self::PASSWORD_ENCODER_MD5;
        }

        // For other users, use the default encoder
        return null;
    }

    /**
     * @inheritDoc
     */
    public function isEqualTo(UserInterface $user): bool
    {
        if (false === $user instanceof Clients) {
            return false;
        }

        if ($this->getHash() !== $user->getHash()) {
            return false;
        }

        if ($this->getUsername() !== $user->getUsername() && $this->getPassword() !== $user->getPassword()) {
            return false;
        }

        if (false === $user->isGrantedLogin()) {
            return false; // The client has been changed to a critical status. He/she is no longer the client that we known as he/she was.
        }

        return true;
    }

    /**
     * @inheritDoc
     */
    public function getRoles(): array
    {
        $roles = $this->roles;
        // guarantee every user at least has ROLE_USER
        $roles[] = 'ROLE_USER';

        return array_unique($roles);
    }

    /**
     * @param array $roles
     *
     * @return Clients
     */
    public function setRoles(array $roles): Clients
    {
        $this->roles = $this->filterRoles($roles);

        return $this;
    }

    /**
     * @param array $roles
     *
     * @return array
     */
    private function filterRoles(array $roles): array
    {
        foreach ($roles as $index => $role) {
            if (false === in_array($role, self::ROLES)) {
                unset($roles[$index]);
            }
        }

        return $roles;
    }

    /**
     * @param string $role
     *
     * @return bool
     */
    public function hasRole(string $role): bool
    {
        return in_array($role, $this->getRoles());
    }

    public function resetRoles(): void
    {
        $this->roles = [];
    }

    /**
     * @inheritDoc
     */
    public function getSalt(): string
    {
        return ''; // Since we use the BCrypt password encoder, the salt will be ignored. The auto-generated one is always the best.
    }

    /**
     * @inheritDoc
     */
    public function getUsername(): string
    {
        return $this->getEmail();
    }

    /**
     * @inheritDoc
     */
    public function eraseCredentials(): void
    {
        // Not yet Implemented
    }

    /**
     * For backwards compatibility (for the user who has already MD5 encoded password), we force the user to use the default encoder (which is BCrypt), even though his/her encoder is MD5
     */
    public function useDefaultEncoder(): void
    {
        $this->userOnlyDefaultEncoder = true;
    }
}
