<?php

namespace Unilend\Bundle\CoreBusinessBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * Clients
 *
 * @ORM\Table(name="clients", indexes={@ORM\Index(name="hash", columns={"hash"}), @ORM\Index(name="email", columns={"email"}), @ORM\Index(name="idx_client_nom", columns={"nom"}), @ORM\Index(name="idx_client_spe", columns={"status_pre_emp"})})
 * @ORM\Entity
 */
class Clients
{
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
    private $idLangue;

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
     * @ORM\Column(name="id_facebook", type="string", length=45, nullable=false)
     */
    private $idFacebook;

    /**
     * @var string
     *
     * @ORM\Column(name="id_linkedin", type="string", length=45, nullable=false)
     */
    private $idLinkedin;

    /**
     * @var string
     *
     * @ORM\Column(name="id_viadeo", type="string", length=45, nullable=false)
     */
    private $idViadeo;

    /**
     * @var string
     *
     * @ORM\Column(name="id_twitter", type="string", length=45, nullable=false)
     */
    private $idTwitter;

    /**
     * @var string
     *
     * @ORM\Column(name="civilite", type="string", nullable=false)
     */
    private $civilite;

    /**
     * @var string
     *
     * @ORM\Column(name="nom", type="string", length=191, nullable=false)
     */
    private $nom;

    /**
     * @var string
     *
     * @ORM\Column(name="nom_usage", type="string", length=191, nullable=false)
     */
    private $nomUsage;

    /**
     * @var string
     *
     * @ORM\Column(name="prenom", type="string", length=191, nullable=false)
     */
    private $prenom;

    /**
     * @var string
     *
     * @ORM\Column(name="slug", type="string", length=191, nullable=false)
     */
    private $slug;

    /**
     * @var string
     *
     * @ORM\Column(name="fonction", type="string", length=191, nullable=false)
     */
    private $fonction;

    /**
     * @var \DateTime
     *
     * @ORM\Column(name="naissance", type="date", nullable=false)
     */
    private $naissance;

    /**
     * @var integer
     *
     * @ORM\Column(name="id_pays_naissance", type="integer", nullable=false)
     */
    private $idPaysNaissance;

    /**
     * @var string
     *
     * @ORM\Column(name="ville_naissance", type="string", length=191, nullable=false)
     */
    private $villeNaissance;

    /**
     * @var string
     *
     * @ORM\Column(name="insee_birth", type="string", length=16, nullable=false)
     */
    private $inseeBirth;

    /**
     * @var integer
     *
     * @ORM\Column(name="id_nationalite", type="integer", nullable=false)
     */
    private $idNationalite;

    /**
     * @var string
     *
     * @ORM\Column(name="telephone", type="string", length=191, nullable=false)
     */
    private $telephone;

    /**
     * @var string
     *
     * @ORM\Column(name="mobile", type="string", length=191, nullable=false)
     */
    private $mobile;

    /**
     * @var string
     *
     * @ORM\Column(name="email", type="string", length=191, nullable=false)
     */
    private $email;

    /**
     * @var string
     *
     * @ORM\Column(name="password", type="string", length=191, nullable=false)
     */
    private $password;

    /**
     * @var string
     *
     * @ORM\Column(name="secrete_question", type="string", length=191, nullable=false)
     */
    private $secreteQuestion;

    /**
     * @var string
     *
     * @ORM\Column(name="secrete_reponse", type="string", length=191, nullable=false)
     */
    private $secreteReponse;

    /**
     * @var boolean
     *
     * @ORM\Column(name="type", type="boolean", nullable=false)
     */
    private $type;

    /**
     * @var int
     *
     * @ORM\Column(name="funds_origin", type="int", nullable=true)
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
     * @ORM\Column(name="etape_inscription_preteur", type="integer", nullable=false)
     */
    private $etapeInscriptionPreteur;

    /**
     * @var boolean
     *
     * @ORM\Column(name="status_inscription_preteur", type="boolean", nullable=false)
     */
    private $statusInscriptionPreteur;

    /**
     * @var boolean
     *
     * @ORM\Column(name="status_pre_emp", type="boolean", nullable=false)
     */
    private $statusPreEmp;

    /**
     * @var string
     *
     * @ORM\Column(name="cni_passeport", type="string", length=191, nullable=false)
     */
    private $cniPasseport;

    /**
     * @var string
     *
     * @ORM\Column(name="signature", type="string", length=191, nullable=false)
     */
    private $signature;

    /**
     * @var string
     *
     * @ORM\Column(name="source", type="string", length=191, nullable=false)
     */
    private $source;

    /**
     * @var string
     *
     * @ORM\Column(name="source2", type="string", length=191, nullable=false)
     */
    private $source2;

    /**
     * @var string
     *
     * @ORM\Column(name="source3", type="string", length=191, nullable=false)
     */
    private $source3;

    /**
     * @var string
     *
     * @ORM\Column(name="slug_origine", type="string", length=191, nullable=false)
     */
    private $slugOrigine;

    /**
     * @var boolean
     *
     * @ORM\Column(name="origine", type="boolean", nullable=false)
     */
    private $origine;

    /**
     * @var boolean
     *
     * @ORM\Column(name="optin1", type="boolean", nullable=false)
     */
    private $optin1;

    /**
     * @var boolean
     *
     * @ORM\Column(name="optin2", type="boolean", nullable=false)
     */
    private $optin2;

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
     * @var \DateTime
     *
     * @ORM\Column(name="lastlogin", type="datetime", nullable=false)
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
     * Set idPartenaire
     *
     * @param integer $idPartenaire
     *
     * @return Clients
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
     * @return Clients
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
     * Set idFacebook
     *
     * @param string $idFacebook
     *
     * @return Clients
     */
    public function setIdFacebook($idFacebook)
    {
        $this->idFacebook = $idFacebook;

        return $this;
    }

    /**
     * Get idFacebook
     *
     * @return string
     */
    public function getIdFacebook()
    {
        return $this->idFacebook;
    }

    /**
     * Set idLinkedin
     *
     * @param string $idLinkedin
     *
     * @return Clients
     */
    public function setIdLinkedin($idLinkedin)
    {
        $this->idLinkedin = $idLinkedin;

        return $this;
    }

    /**
     * Get idLinkedin
     *
     * @return string
     */
    public function getIdLinkedin()
    {
        return $this->idLinkedin;
    }

    /**
     * Set idViadeo
     *
     * @param string $idViadeo
     *
     * @return Clients
     */
    public function setIdViadeo($idViadeo)
    {
        $this->idViadeo = $idViadeo;

        return $this;
    }

    /**
     * Get idViadeo
     *
     * @return string
     */
    public function getIdViadeo()
    {
        return $this->idViadeo;
    }

    /**
     * Set idTwitter
     *
     * @param string $idTwitter
     *
     * @return Clients
     */
    public function setIdTwitter($idTwitter)
    {
        $this->idTwitter = $idTwitter;

        return $this;
    }

    /**
     * Get idTwitter
     *
     * @return string
     */
    public function getIdTwitter()
    {
        return $this->idTwitter;
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
     * Set nomUsage
     *
     * @param string $nomUsage
     *
     * @return Clients
     */
    public function setNomUsage($nomUsage)
    {
        $this->nomUsage = $nomUsage;

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
     * @return Clients
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
        $this->secreteReponse = $secreteReponse;

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
     * @param boolean $type
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
     * @return boolean
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
     * @param boolean $statusInscriptionPreteur
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
     * @return boolean
     */
    public function getStatusInscriptionPreteur()
    {
        return $this->statusInscriptionPreteur;
    }

    /**
     * Set statusPreEmp
     *
     * @param boolean $statusPreEmp
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
     * @return boolean
     */
    public function getStatusPreEmp()
    {
        return $this->statusPreEmp;
    }

    /**
     * Set cniPasseport
     *
     * @param string $cniPasseport
     *
     * @return Clients
     */
    public function setCniPasseport($cniPasseport)
    {
        $this->cniPasseport = $cniPasseport;

        return $this;
    }

    /**
     * Get cniPasseport
     *
     * @return string
     */
    public function getCniPasseport()
    {
        return $this->cniPasseport;
    }

    /**
     * Set signature
     *
     * @param string $signature
     *
     * @return Clients
     */
    public function setSignature($signature)
    {
        $this->signature = $signature;

        return $this;
    }

    /**
     * Get signature
     *
     * @return string
     */
    public function getSignature()
    {
        return $this->signature;
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
     * @param boolean $origine
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
     * @return boolean
     */
    public function getOrigine()
    {
        return $this->origine;
    }

    /**
     * Set optin1
     *
     * @param boolean $optin1
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
     * @return boolean
     */
    public function getOptin1()
    {
        return $this->optin1;
    }

    /**
     * Set optin2
     *
     * @param boolean $optin2
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
     * @return boolean
     */
    public function getOptin2()
    {
        return $this->optin2;
    }

    /**
     * Set status
     *
     * @param boolean $status
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
     * Get fundsOrigineDetail
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
}
