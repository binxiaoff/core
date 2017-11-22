<?php

namespace Unilend\Bundle\CoreBusinessBundle\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\Mapping as ORM;
use Hashids\Hashids;
use Ramsey\Uuid\Exception\UnsatisfiedDependencyException;
use Ramsey\Uuid\Uuid;

/**
 * Clients
 *
 * @ORM\Table(name="clients", indexes={@ORM\Index(name="hash", columns={"hash"}), @ORM\Index(name="email", columns={"email"}), @ORM\Index(name="idx_client_nom", columns={"nom"}), @ORM\Index(name="idx_client_spe", columns={"status_pre_emp"})})
 * @ORM\Entity(repositoryClass="Unilend\Bundle\CoreBusinessBundle\Repository\ClientsRepository")
 * @ORM\HasLifecycleCallbacks
 */
class Clients
{
    const TYPE_PERSON                 = 1;
    const TYPE_LEGAL_ENTITY           = 2;
    const TYPE_PERSON_FOREIGNER       = 3;
    const TYPE_LEGAL_ENTITY_FOREIGNER = 4;

    const STATUS_OFFLINE = 0;
    const STATUS_ONLINE  = 1;

    const SUBSCRIPTION_STEP_PERSONAL_INFORMATION = 1;
    const SUBSCRIPTION_STEP_DOCUMENTS            = 2;
    const SUBSCRIPTION_STEP_MONEY_DEPOSIT        = 3;

    const TITLE_MISS      = 'Mme';
    const TITLE_MISTER    = 'M.';
    const TITLE_UNDEFINED = '';

    /** Legacy welcome offer before separating them and adding types  */
    const ORIGIN_WELCOME_OFFER      = 1;
    const ORIGIN_WELCOME_OFFER_HOME = 2;
    const ORIGIN_WELCOME_OFFER_LP   = 3;

    /**
     * @var string
     *
     * @ORM\Column(name="hash", type="string", length=191, nullable=false)
     */
    private $hash;

    /**
     * @var string
     *
     * @ORM\Column(name="id_langue", type="string", length=5, nullable=false)
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
     * @var integer
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
     * @var integer
     *
     * @ORM\Column(name="id_nationalite", type="integer", nullable=true)
     */
    private $idNationalite;

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
     * @var integer
     *
     * @ORM\Column(name="type", type="integer", nullable=true)
     */
    private $type;

    /**
     * @var integer
     *
     * @ORM\Column(name="funds_origin", type="integer", nullable=true)
     */
    private $fundsOrigin;

    /**
     * @var string
     *
     * @ORM\Column(name="funds_origin_detail", type="string", nullable=true)
     */
    private $fundsOriginDetail;

    /**
     * @var integer
     *
     * @ORM\Column(name="etape_inscription_preteur", type="integer", nullable=true)
     */
    private $etapeInscriptionPreteur;

    /**
     * @var integer
     *
     * @ORM\Column(name="status_inscription_preteur", type="integer", nullable=true)
     */
    private $statusInscriptionPreteur;

    /**
     * @var integer
     *
     * @ORM\Column(name="status_pre_emp", type="integer", nullable=true)
     */
    private $statusPreEmp;

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
     * @var integer
     *
     * @ORM\Column(name="origine", type="integer", nullable=true)
     */
    private $origine;

    /**
     * @var integer
     *
     * @ORM\Column(name="optin1", type="integer", nullable=true)
     */
    private $optin1;

    /**
     * @var integer
     *
     * @ORM\Column(name="optin2", type="integer", nullable=true)
     */
    private $optin2;

    /**
     * @var integer
     *
     * @ORM\Column(name="status", type="integer", nullable=true)
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
     * @var \DateTime
     *
     * @ORM\Column(name="lastlogin", type="datetime", nullable=true)
     */
    private $lastlogin;

    /**
     * @var integer
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
     * @ORM\Column(name="sponsor_code", type="string", nullable=true)
     */
    private $sponsorCode;

    /**
     * @var ClientsAdresses[];
     *
     * @ORM\OneToMany(targetEntity="Unilend\Bundle\CoreBusinessBundle\Entity\ClientsAdresses", mappedBy="idClient")
     */
    private $clientsAddresses;

    /**
     * Clients constructor.
     */
    public function __construct()
    {
        $this->attachments     = new ArrayCollection();
        $this->wallets         = new ArrayCollection();
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
        $this->slug = $slug;

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
     * Set statusPreEmp
     *
     * @param integer $statusPreEmp
     *
     * @return Clients
     */
    public function setStatusPreEmp($statusPreEmp)
    {
        $this->statusPreEmp = $statusPreEmp;

        return $this;
    }

    /**
     * Get statusPreEmp
     *
     * @return integer
     */
    public function getStatusPreEmp()
    {
        return $this->statusPreEmp;
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
     * Set status
     *
     * @param integer $status
     *
     * @return Clients
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
     * Check whether client has a borrower wallet or not. Since a client can have only one wallet today, it works so far.
     *
     * @return bool
     */
    public function isBorrower()
    {
        return false === empty($this->wallets[0]) && $this->wallets[0]->getIdType()->getLabel() === WalletType::BORROWER;
    }

    /**
     * Check whether client has a lender wallet or not. Since a client can have only one wallet today, it works so far.
     *
     * @return bool
     */
    public function isLender()
    {
        return false === empty($this->wallets[0]) && $this->wallets[0]->getIdType()->getLabel() === WalletType::LENDER;
    }

    /**
     * Check whether client has a partner wallet or not. Since a client can have only one wallet today, it works so far.
     *
     * @return bool
     */
    public function isPartner()
    {
        return false === empty($this->wallets[0]) && $this->wallets[0]->getIdType()->getLabel() === WalletType::PARTNER;
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
    public function setClientsAddresses(ClientsAdresses $clientsAddresses)
    {
        $this->clientsAddresses = $clientsAddresses;
        return $this;
    }


}
