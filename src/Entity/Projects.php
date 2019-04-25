<?php

namespace Unilend\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\Common\Collections\Criteria;
use Doctrine\ORM\Mapping as ORM;
use Ramsey\Uuid\Exception\UnsatisfiedDependencyException;
use Ramsey\Uuid\Uuid;
use Unilend\Entity\Traits\Timestampable;

/**
 * Projects
 *
 * @ORM\Table(name="projects", indexes={
 *     @ORM\Index(name="id_company", columns={"id_company"}),
 *     @ORM\Index(name="slug", columns={"slug"}),
 *     @ORM\Index(name="display", columns={"display"}),
 *     @ORM\Index(name="hash", columns={"hash"}),
 *     @ORM\Index(name="id_commercial", columns={"id_commercial"}),
 *     @ORM\Index(name="id_dernier_bilan", columns={"id_dernier_bilan"}),
 *     @ORM\Index(name="fk_projects_id_company_submitter", columns={"id_company_submitter"}),
 *     @ORM\Index(name="fk_projects_id_client_submitter", columns={"id_client_submitter"}),
 *     @ORM\Index(name="fk_projects_status", columns={"status"})
 * })
 * @ORM\Entity(repositoryClass="Unilend\Repository\ProjectsRepository")
 * @ORM\HasLifecycleCallbacks
 */
class Projects
{
    use Timestampable;

    const AUTO_REPAYMENT_ON  = 0;
    const AUTO_REPAYMENT_OFF = 1;

    const DISPLAY_YES = 0;
    const DISPLAY_NO  = 1;

    // project rating mapping (letter to start)
    const RISK_A = 5;
    const RISK_B = 4.5;
    const RISK_C = 4;
    const RISK_D = 3.5;
    const RISK_E = 3;
    const RISK_F = 2.5;
    const RISK_G = 2;
    const RISK_H = 1.5;
    const RISK_I = 1;
    const RISK_J = 0;

    const DEFAULT_COMMISSION_RATE_FUNDS     = 4;
    const DEFAULT_COMMISSION_RATE_REPAYMENT = 1;

    const PROJECT_PHOTO_PATH = 'public/default/images/dyn/projets/source/';

    /**
     * @var string
     *
     * @ORM\Column(name="hash", type="string", length=191)
     */
    private $hash;

    /**
     * @var string
     *
     * @ORM\Column(name="slug", type="string", length=191, nullable=true)
     */
    private $slug;

    /**
     * @var \Unilend\Entity\Companies
     *
     * @ORM\ManyToOne(targetEntity="Unilend\Entity\Companies")
     * @ORM\JoinColumns({
     *   @ORM\JoinColumn(name="id_company", referencedColumnName="id_company", nullable=false)
     * })
     */
    private $idCompany;

    /**
     * @var \Unilend\Entity\Companies
     *
     * @ORM\ManyToOne(targetEntity="Unilend\Entity\Companies")
     * @ORM\JoinColumns({
     *   @ORM\JoinColumn(name="id_target_company", referencedColumnName="id_company")
     * })
     */
    private $idTargetCompany;

    /**
     * @var float
     *
     * @ORM\Column(name="amount", type="integer", nullable=true)
     */
    private $amount;

    /**
     * @var int
     *
     * @ORM\Column(name="period", type="smallint", nullable=true)
     */
    private $period;

    /**
     * @var string
     *
     * @ORM\Column(name="title", type="string", length=191, nullable=true)
     */
    private $title;

    /**
     * @var int
     *
     * @ORM\Column(name="id_borrowing_motive", type="smallint", nullable=true)
     */
    private $idBorrowingMotive;

    /**
     * @var string
     *
     * @ORM\Column(name="photo_projet", type="string", length=191, nullable=true)
     */
    private $photoProjet;

    /**
     * @var string
     *
     * @ORM\Column(name="description", type="text", length=16777215, nullable=true)
     */
    private $description;

    /**
     * @var string
     *
     * @ORM\Column(name="nature_project", type="text", length=16777215, nullable=true)
     */
    private $natureProject;

    /**
     * @var string
     *
     * @ORM\Column(name="objectif_loan", type="text", length=16777215, nullable=true)
     */
    private $objectifLoan;

    /**
     * @var string
     *
     * @ORM\Column(name="presentation_company", type="text", length=16777215, nullable=true)
     */
    private $presentationCompany;

    /**
     * @var string
     *
     * @ORM\Column(name="means_repayment", type="text", length=16777215, nullable=true)
     */
    private $meansRepayment;

    /**
     * @var \Unilend\Entity\Users
     *
     * @ORM\ManyToOne(targetEntity="Unilend\Entity\Users")
     * @ORM\JoinColumns({
     *   @ORM\JoinColumn(name="id_analyste", referencedColumnName="id_user")
     * })
     */
    private $idAnalyste;

    /**
     * @var \Unilend\Entity\Users
     *
     * @ORM\ManyToOne(targetEntity="Unilend\Entity\Users")
     * @ORM\JoinColumns({
     *   @ORM\JoinColumn(name="id_commercial", referencedColumnName="id_user")
     * })
     */
    private $idCommercial;

    /**
     * @var \DateTime
     *
     * @ORM\Column(name="date_publication", type="datetime", nullable=true)
     */
    private $datePublication;

    /**
     * @var \DateTime
     *
     * @ORM\Column(name="date_funded", type="datetime", nullable=true)
     */
    private $dateFunded;

    /**
     * @var \DateTime
     *
     * @ORM\Column(name="date_retrait", type="datetime", nullable=true)
     */
    private $dateRetrait;

    /**
     * @var \DateTime
     *
     * @ORM\Column(name="date_fin", type="datetime", nullable=true)
     */
    private $dateFin;

    /**
     * @var int
     *
     * @ORM\Column(name="id_dernier_bilan", type="integer", nullable=true)
     */
    private $idDernierBilan;

    /**
     * @var int
     *
     * @ORM\Column(name="balance_count", type="integer", nullable=true)
     */
    private $balanceCount;

    /**
     * @var int
     *
     * @ORM\Column(name="id_company_rating_history", type="integer", nullable=true)
     */
    private $idCompanyRatingHistory;

    /**
     * @var int
     *
     * @ORM\Column(name="id_project_need", type="integer", nullable=true)
     */
    private $idProjectNeed;

    /**
     * @var bool
     *
     * @ORM\Column(name="create_bo", type="boolean")
     */
    private $createBo;

    /**
     * @var string
     *
     * @ORM\Column(name="risk", type="string", length=2, nullable=true)
     */
    private $risk;

    /**
     * @var float
     *
     * @ORM\Column(name="fonds_propres_declara_client", type="float", precision=10, scale=0, nullable=true)
     */
    private $fondsPropresDeclaraClient;

    /**
     * @var float
     *
     * @ORM\Column(name="resultat_exploitation_declara_client", type="float", precision=10, scale=0, nullable=true)
     */
    private $resultatExploitationDeclaraClient;

    /**
     * @var float
     *
     * @ORM\Column(name="ca_declara_client", type="float", precision=10, scale=0, nullable=true)
     */
    private $caDeclaraClient;

    /**
     * @var int
     *
     * @ORM\Column(name="remb_auto", type="smallint", nullable=true)
     */
    private $rembAuto;

    /**
     * @var int
     *
     * @ORM\Column(name="display", type="smallint", nullable=true)
     */
    private $display;

    /**
     * @var int
     *
     * @ORM\Column(name="id_rate", type="integer", nullable=true)
     */
    private $idRate;

    /**
     * @var \Unilend\Entity\Partner
     *
     * @ORM\ManyToOne(targetEntity="Unilend\Entity\Partner")
     * @ORM\JoinColumns({
     *   @ORM\JoinColumn(name="id_partner", referencedColumnName="id", nullable=false)
     * })
     */
    private $idPartner;

    /**
     * @var \Unilend\Entity\Companies
     *
     * @ORM\ManyToOne(targetEntity="Unilend\Entity\Companies")
     * @ORM\JoinColumns({
     *   @ORM\JoinColumn(name="id_company_submitter", referencedColumnName="id_company")
     * })
     */
    private $idCompanySubmitter;

    /**
     * @var \Unilend\Entity\Clients
     *
     * @ORM\ManyToOne(targetEntity="Unilend\Entity\Clients")
     * @ORM\JoinColumns({
     *   @ORM\JoinColumn(name="id_client_submitter", referencedColumnName="id_client")
     * })
     */
    private $idClientSubmitter;

    /**
     * @var string
     *
     * @ORM\Column(name="commission_rate_funds", type="decimal", precision=4, scale=2, nullable=true)
     */
    private $commissionRateFunds;

    /**
     * @var string
     *
     * @ORM\Column(name="commission_rate_repayment", type="decimal", precision=4, scale=2, nullable=true)
     */
    private $commissionRateRepayment;

    /**
     * @var int
     *
     * @ORM\Column(name="id_product", type="integer", nullable=true)
     */
    private $idProduct;

    /**
     * @var int
     *
     * @ORM\Column(name="id_project", type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="IDENTITY")
     */
    private $idProject;

    /**
     * @var int
     *
     * @ORM\Column(name="status", type="smallint")
     */
    private $status;

    /**
     * @var float
     *
     * @ORM\Column(name="interest_rate", type="decimal", precision=4, scale=2, nullable=true)
     */
    private $interestRate;

    /**
     * @var ProjectAttachment[]
     *
     * @ORM\OneToMany(targetEntity="Unilend\Entity\ProjectAttachment", mappedBy="idProject")
     */
    private $attachments;

    /**
     * @var ClientsMandats[]
     *
     * @ORM\OneToMany(targetEntity="Unilend\Entity\ClientsMandats", mappedBy="idProject")
     * @ORM\JoinColumns({
     *   @ORM\JoinColumn(name="id_project", referencedColumnName="id_project")
     * })
     */
    private $mandates;

    /**
     * @deprecated
     * @var ProjectsComments[]
     *
     * @ORM\OneToMany(targetEntity="Unilend\Entity\ProjectsComments", mappedBy="idProject")
     * @ORM\OrderBy({"added" = "DESC"})
     */
    private $memos;

    /**
     * @var Virements[]
     *
     * @ORM\OneToMany(targetEntity="Unilend\Entity\Virements", mappedBy="idProject")
     */
    private $wireTransferOuts;

    /**
     * @var Factures[]
     *
     * @ORM\OneToMany(targetEntity="Unilend\Entity\Factures", mappedBy="idProject")
     * @ORM\JoinColumns({
     *   @ORM\JoinColumn(name="id_project", referencedColumnName="id_project")
     * })
     */
    private $invoices;

    /**
     * @var \DateTime
     *
     * @ORM\Column(name="close_out_netting_date", type="date", nullable=true)
     */
    private $closeOutNettingDate;

    /**
     * @var DebtCollectionMission[]
     *
     * @ORM\OneToMany(targetEntity="Unilend\Entity\DebtCollectionMission", mappedBy="idProject")
     */
    private $debtCollectionMissions;

    /**
     * @var ProjectParticipant[]
     *
     * @ORM\OneToMany(targetEntity="Unilend\Entity\ProjectParticipant", mappedBy="project", cascade={"persist"}, orphanRemoval=true)
     */
    private $projectParticipants;

    /**
     * @var Bids[]|ArrayCollection
     *
     * @ORM\OneToMany(targetEntity="Unilend\Entity\Bids", mappedBy="project")
     * @ORM\OrderBy({"added" = "DESC"})
     */
    private $bids;

    /**
     * @var Loans[]|ArrayCollection
     *
     * @ORM\OneToMany(targetEntity="Unilend\Entity\Loans", mappedBy="project")
     */
    private $loans;

    /**
     * @var ProjectPercentFee[]|ArrayCollection
     *
     * @ORM\OneToMany(targetEntity="Unilend\Entity\ProjectPercentFee", mappedBy="project", cascade={"persist"}, orphanRemoval=true)
     */
    private $projectPercentFees;

    /**
     * @var ProjectComment[]|ArrayCollection
     *
     * @ORM\OneToMany(targetEntity="Unilend\Entity\ProjectComment", mappedBy="project")
     * @ORM\OrderBy({"added" = "DESC"})
     */
    private $comments;

    /**
     * Projects constructor.
     */
    public function __construct()
    {
        $this->attachments            = new ArrayCollection();
        $this->mandates               = new ArrayCollection();
        $this->memos                  = new ArrayCollection();
        $this->wireTransferOuts       = new ArrayCollection();
        $this->invoices               = new ArrayCollection();
        $this->debtCollectionMissions = new ArrayCollection();
        $this->projectParticipants    = new ArrayCollection();
        $this->projectPercentFees     = new ArrayCollection();
        $this->comments               = new ArrayCollection();
    }

    /**
     * Set hash
     *
     * @param string $hash
     *
     * @return Projects
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
     * Set slug
     *
     * @param string $slug
     *
     * @return Projects
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
     * Set idCompany
     *
     * @param Companies $idCompany
     *
     * @return Projects
     */
    public function setIdCompany(Companies $idCompany)
    {
        $this->idCompany = $idCompany;

        return $this;
    }

    /**
     * Get idCompany
     *
     * @return Companies
     */
    public function getIdCompany()
    {
        return $this->idCompany;
    }

    /**
     * Set idTargetCompany
     *
     * @param Companies $idTargetCompany
     *
     * @return Projects
     */
    public function setIdTargetCompany(Companies $idTargetCompany)
    {
        $this->idTargetCompany = $idTargetCompany;

        return $this;
    }

    /**
     * Get idTargetCompany
     *
     * @return Companies
     */
    public function getIdTargetCompany()
    {
        return $this->idTargetCompany;
    }

    /**
     * Set amount
     *
     * @param float $amount
     *
     * @return Projects
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
     * Set period
     *
     * @param integer $period
     *
     * @return Projects
     */
    public function setPeriod($period)
    {
        $this->period = $period;

        return $this;
    }

    /**
     * Get period
     *
     * @return integer
     */
    public function getPeriod()
    {
        return $this->period;
    }

    /**
     * Set title
     *
     * @param string $title
     *
     * @return Projects
     */
    public function setTitle($title)
    {
        $this->title = $title;

        return $this;
    }

    /**
     * Get title
     *
     * @return string
     */
    public function getTitle()
    {
        return $this->title;
    }

    /**
     * Set idBorrowingMotive
     *
     * @param integer $idBorrowingMotive
     *
     * @return Projects
     */
    public function setIdBorrowingMotive($idBorrowingMotive)
    {
        $this->idBorrowingMotive = $idBorrowingMotive;

        return $this;
    }

    /**
     * Get idBorrowingMotive
     *
     * @return integer
     */
    public function getIdBorrowingMotive()
    {
        return $this->idBorrowingMotive;
    }

    /**
     * Set photoProjet
     *
     * @param string $photoProjet
     *
     * @return Projects
     */
    public function setPhotoProjet($photoProjet)
    {
        $this->photoProjet = $photoProjet;

        return $this;
    }

    /**
     * Get photoProjet
     *
     * @return string
     */
    public function getPhotoProjet()
    {
        return $this->photoProjet;
    }

    /**
     * Set description
     *
     * @param string $description
     *
     * @return Projects
     */
    public function setDescription($description)
    {
        $this->description = $description;

        return $this;
    }

    /**
     * Get description
     *
     * @return string
     */
    public function getDescription()
    {
        return $this->description;
    }

    /**
     * Set natureProject
     *
     * @param string $natureProject
     *
     * @return Projects
     */
    public function setNatureProject($natureProject)
    {
        $this->natureProject = $natureProject;

        return $this;
    }

    /**
     * Get natureProject
     *
     * @return string
     */
    public function getNatureProject()
    {
        return $this->natureProject;
    }

    /**
     * Set objectifLoan
     *
     * @param string $objectifLoan
     *
     * @return Projects
     */
    public function setObjectifLoan($objectifLoan)
    {
        $this->objectifLoan = $objectifLoan;

        return $this;
    }

    /**
     * Get objectifLoan
     *
     * @return string
     */
    public function getObjectifLoan()
    {
        return $this->objectifLoan;
    }

    /**
     * Set presentationCompany
     *
     * @param string $presentationCompany
     *
     * @return Projects
     */
    public function setPresentationCompany($presentationCompany)
    {
        $this->presentationCompany = $presentationCompany;

        return $this;
    }

    /**
     * Get presentationCompany
     *
     * @return string
     */
    public function getPresentationCompany()
    {
        return $this->presentationCompany;
    }

    /**
     * Set meansRepayment
     *
     * @param string $meansRepayment
     *
     * @return Projects
     */
    public function setMeansRepayment($meansRepayment)
    {
        $this->meansRepayment = $meansRepayment;

        return $this;
    }

    /**
     * Get meansRepayment
     *
     * @return string
     */
    public function getMeansRepayment()
    {
        return $this->meansRepayment;
    }

    /**
     * Set idAnalyste
     *
     * @param Users $idAnalyste
     *
     * @return Projects
     */
    public function setIdAnalyste(Users $idAnalyste)
    {
        $this->idAnalyste = $idAnalyste;

        return $this;
    }

    /**
     * Get idAnalyste
     *
     * @return Users
     */
    public function getIdAnalyste()
    {
        return $this->idAnalyste;
    }

    /**
     * Set idCommercial
     *
     * @param Users $idCommercial
     *
     * @return Projects
     */
    public function setIdCommercial(Users $idCommercial)
    {
        $this->idCommercial = $idCommercial;

        return $this;
    }

    /**
     * Get idCommercial
     *
     * @return Users
     */
    public function getIdCommercial()
    {
        return $this->idCommercial;
    }

    /**
     * Set datePublication
     *
     * @param \DateTime $datePublication
     *
     * @return Projects
     */
    public function setDatePublication($datePublication)
    {
        $this->datePublication = $datePublication;

        return $this;
    }

    /**
     * Get datePublication
     *
     * @return \DateTime
     */
    public function getDatePublication()
    {
        return $this->datePublication;
    }

    /**
     * Set dateFunded
     *
     * @param \DateTime $dateFunded
     *
     * @return Projects
     */
    public function setDateFunded($dateFunded)
    {
        $this->dateFunded = $dateFunded;

        return $this;
    }

    /**
     * Get dateFunded
     *
     * @return \DateTime
     */
    public function getDateFunded()
    {
        return $this->dateFunded;
    }

    /**
     * Set dateRetrait
     *
     * @param \DateTime $dateRetrait
     *
     * @return Projects
     */
    public function setDateRetrait($dateRetrait)
    {
        $this->dateRetrait = $dateRetrait;

        return $this;
    }

    /**
     * Get dateRetrait
     *
     * @return \DateTime
     */
    public function getDateRetrait()
    {
        return $this->dateRetrait;
    }

    /**
     * Set dateFin
     *
     * @param \DateTime $dateFin
     *
     * @return Projects
     */
    public function setDateFin($dateFin)
    {
        $this->dateFin = $dateFin;

        return $this;
    }

    /**
     * Get dateFin
     *
     * @return \DateTime
     */
    public function getDateFin()
    {
        return $this->dateFin;
    }

    /**
     * Set idDernierBilan
     *
     * @param integer $idDernierBilan
     *
     * @return Projects
     */
    public function setIdDernierBilan($idDernierBilan)
    {
        $this->idDernierBilan = $idDernierBilan;

        return $this;
    }

    /**
     * Get idDernierBilan
     *
     * @return integer
     */
    public function getIdDernierBilan()
    {
        return $this->idDernierBilan;
    }

    /**
     * Set balanceCount
     *
     * @param integer $balanceCount
     *
     * @return Projects
     */
    public function setBalanceCount($balanceCount)
    {
        $this->balanceCount = $balanceCount;

        return $this;
    }

    /**
     * Get balanceCount
     *
     * @return integer
     */
    public function getBalanceCount()
    {
        return $this->balanceCount;
    }

    /**
     * Set idCompanyRatingHistory
     *
     * @param integer $idCompanyRatingHistory
     *
     * @return Projects
     */
    public function setIdCompanyRatingHistory($idCompanyRatingHistory)
    {
        $this->idCompanyRatingHistory = $idCompanyRatingHistory;

        return $this;
    }

    /**
     * Get idCompanyRatingHistory
     *
     * @return integer
     */
    public function getIdCompanyRatingHistory()
    {
        return $this->idCompanyRatingHistory;
    }

    /**
     * Set idProjectNeed
     *
     * @param integer $idProjectNeed
     *
     * @return Projects
     */
    public function setIdProjectNeed($idProjectNeed)
    {
        $this->idProjectNeed = $idProjectNeed;

        return $this;
    }

    /**
     * Get idProjectNeed
     *
     * @return integer
     */
    public function getIdProjectNeed()
    {
        return $this->idProjectNeed;
    }

    /**
     * Set createBo
     *
     * @param bool $createBo
     *
     * @return Projects
     */
    public function setCreateBo($createBo)
    {
        $this->createBo = $createBo;

        return $this;
    }

    /**
     * Get createBo
     *
     * @return bool
     */
    public function getCreateBo()
    {
        return $this->createBo;
    }

    /**
     * Set risk
     *
     * @param string $risk
     *
     * @return Projects
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
     * Set fondsPropresDeclaraClient
     *
     * @param float $fondsPropresDeclaraClient
     *
     * @return Projects
     */
    public function setFondsPropresDeclaraClient($fondsPropresDeclaraClient)
    {
        $this->fondsPropresDeclaraClient = $fondsPropresDeclaraClient;

        return $this;
    }

    /**
     * Get fondsPropresDeclaraClient
     *
     * @return float
     */
    public function getFondsPropresDeclaraClient()
    {
        return $this->fondsPropresDeclaraClient;
    }

    /**
     * Set resultatExploitationDeclaraClient
     *
     * @param float $resultatExploitationDeclaraClient
     *
     * @return Projects
     */
    public function setResultatExploitationDeclaraClient($resultatExploitationDeclaraClient)
    {
        $this->resultatExploitationDeclaraClient = $resultatExploitationDeclaraClient;

        return $this;
    }

    /**
     * Get resultatExploitationDeclaraClient
     *
     * @return float
     */
    public function getResultatExploitationDeclaraClient()
    {
        return $this->resultatExploitationDeclaraClient;
    }

    /**
     * Set caDeclaraClient
     *
     * @param float $caDeclaraClient
     *
     * @return Projects
     */
    public function setCaDeclaraClient($caDeclaraClient)
    {
        $this->caDeclaraClient = $caDeclaraClient;

        return $this;
    }

    /**
     * Get caDeclaraClient
     *
     * @return float
     */
    public function getCaDeclaraClient()
    {
        return $this->caDeclaraClient;
    }

    /**
     * Set rembAuto
     *
     * @param integer $rembAuto
     *
     * @return Projects
     */
    public function setRembAuto($rembAuto)
    {
        $this->rembAuto = $rembAuto;

        return $this;
    }

    /**
     * Get rembAuto
     *
     * @return integer
     */
    public function getRembAuto()
    {
        return $this->rembAuto;
    }

    /**
     * Set display
     *
     * @param integer $display
     *
     * @return Projects
     */
    public function setDisplay($display)
    {
        $this->display = $display;

        return $this;
    }

    /**
     * Get display
     *
     * @return integer
     */
    public function getDisplay()
    {
        return $this->display;
    }

    /**
     * Set idRate
     *
     * @param integer $idRate
     *
     * @return Projects
     */
    public function setIdRate($idRate)
    {
        $this->idRate = $idRate;

        return $this;
    }

    /**
     * Get idRate
     *
     * @return integer
     */
    public function getIdRate()
    {
        return $this->idRate;
    }

    /**
     * Set idPartner
     *
     * @param Partner $idPartner
     *
     * @return Projects
     */
    public function setIdPartner($idPartner)
    {
        $this->idPartner = $idPartner;

        return $this;
    }

    /**
     * Get idPartner
     *
     * @return Partner
     */
    public function getIdPartner()
    {
        return $this->idPartner;
    }

    /**
     * Set idCompanySubmitter
     *
     * @param Companies|null $idCompanySubmitter
     *
     * @return Projects
     */
    public function setIdCompanySubmitter(?Companies $idCompanySubmitter): Projects
    {
        $this->idCompanySubmitter = $idCompanySubmitter;

        return $this;
    }

    /**
     * Get idCompanySubmitter
     *
     * @return Companies|null
     */
    public function getIdCompanySubmitter(): ?Companies
    {
        return $this->idCompanySubmitter;
    }

    /**
     * Set idClientSubmitter
     *
     * @param Clients|null $idClientSubmitter
     *
     * @return Projects
     */
    public function setIdClientSubmitter(?Clients $idClientSubmitter): Projects
    {
        $this->idClientSubmitter = $idClientSubmitter;

        return $this;
    }

    /**
     * Get idClientSubmitter
     *
     * @return Clients|null
     */
    public function getIdClientSubmitter(): ?Clients
    {
        return $this->idClientSubmitter;
    }

    /**
     * Set commissionRateFunds
     *
     * @param string $commissionRateFunds
     *
     * @return Projects
     */
    public function setCommissionRateFunds($commissionRateFunds)
    {
        $this->commissionRateFunds = $commissionRateFunds;

        return $this;
    }

    /**
     * Get commissionRateFunds
     *
     * @return string
     */
    public function getCommissionRateFunds()
    {
        return $this->commissionRateFunds;
    }

    /**
     * Set commissionRateRepayment
     *
     * @param string $commissionRateRepayment
     *
     * @return Projects
     */
    public function setCommissionRateRepayment($commissionRateRepayment)
    {
        $this->commissionRateRepayment = $commissionRateRepayment;

        return $this;
    }

    /**
     * Get commissionRateRepayment
     *
     * @return string
     */
    public function getCommissionRateRepayment()
    {
        return $this->commissionRateRepayment;
    }

    /**
     * Set idProduct
     *
     * @param integer $idProduct
     *
     * @return Projects
     */
    public function setIdProduct($idProduct)
    {
        $this->idProduct = $idProduct;

        return $this;
    }

    /**
     * Get idProduct
     *
     * @return integer
     */
    public function getIdProduct()
    {
        return $this->idProduct;
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
     * Set status
     *
     * @param integer $status
     *
     * @return Projects
     */
    public function setStatus($status)
    {
        $this->status = $status;

        return $this;
    }

    /**
     * Get status
     *
     * @return int
     */
    public function getStatus()
    {
        return $this->status;
    }

    /**
     * Set interest rate
     *
     * @param float $interestRate
     *
     * @return Projects
     */
    public function setInterestRate($interestRate)
    {
        $this->interestRate = $interestRate;

        return $this;
    }

    /**
     * Get interestRate
     *
     * @return float|null
     */
    public function getInterestRate(): ?float
    {
        return $this->interestRate;
    }

    /**
     * Get project attachments
     *
     * @return ProjectAttachment[]
     */
    public function getAttachments()
    {
        return $this->attachments;
    }

    /**
     * Get project mandates
     *
     * @return ClientsMandats[]
     */
    public function getMandates()
    {
        $criteria = Criteria::create();
        $criteria->orderBy(['updated' => Criteria::DESC]);

        return $this->mandates->matching($criteria);
    }

    /**
     * @deprecated
     * Get project memos
     *
     * @return ArrayCollection|ProjectsComments[]
     */
    public function getMemos(): ArrayCollection
    {
        $criteria = Criteria::create();
        $criteria->orderBy(['added' => Criteria::DESC]);

        return $this->memos->matching($criteria);
    }

    /**
     * @deprecated
     * Get project public memos
     *
     * @return ArrayCollection|ProjectsComments[]
     */
    public function getPublicMemos(): ArrayCollection
    {
        $criteria = Criteria::create()
            ->where(Criteria::expr()->eq('public', true))
            ->orderBy(['added' => Criteria::DESC]);

        return $this->memos->matching($criteria);
    }

    /**
     * @return Virements[]
     */
    public function getWireTransferOuts()
    {
        return $this->wireTransferOuts;
    }

    /**
     * @return ArrayCollection|Factures[]
     */
    public function getInvoices()
    {
        return $this->invoices;
    }

    /**
     * @return \DateTime
     */
    public function getCloseOutNettingDate()
    {
        /** @todo to be removed when projects is fully under doctrine */
        if (null !== $this->closeOutNettingDate && $this->closeOutNettingDate->getTimestamp() < 0) {
            $this->closeOutNettingDate = null;
        }

        return $this->closeOutNettingDate;
    }

    /**
     * @param \DateTime $closeOutNettingDate
     *
     * @return Projects
     */
    public function setCloseOutNettingDate(\DateTime $closeOutNettingDate = null)
    {
        $this->closeOutNettingDate = $closeOutNettingDate;

        return $this;
    }

    /**
     * @param bool  $includeArchived
     * @param array $sort
     *
     * @return ArrayCollection|DebtCollectionMission[]
     */
    public function getDebtCollectionMissions($includeArchived = false, $sort = [])
    {
        $criteria = Criteria::create();

        if (false === $includeArchived) {
            $criteria = Criteria::create()
                ->where(Criteria::expr()->isNull('archived'));
        }

        if ($sort) {
            $criteria->orderBy($sort);
        }

        return $this->debtCollectionMissions->matching($criteria);
    }

    /**
     * @param bool $includeArchived
     *
     * @return ArrayCollection|DebtCollectionMission[]
     */
    public function getAmicableDebtCollectionMissions($includeArchived = false)
    {
        $criteria = Criteria::create()
            ->where(Criteria::expr()->eq('type', DebtCollectionMission::TYPE_AMICABLE));

        if (false === $includeArchived) {
            $criteria->andWhere(Criteria::expr()->isNull('archived'));
        }

        return $this->debtCollectionMissions->matching($criteria);
    }

    /**
     * @param bool $includeArchived
     *
     * @return ArrayCollection|DebtCollectionMission[]
     */
    public function getLitigationDebtCollectionMissions($includeArchived = false)
    {
        $criteria = Criteria::create()
            ->where(Criteria::expr()->eq('type', DebtCollectionMission::TYPE_LITIGATION));

        if (false === $includeArchived) {
            $criteria->andWhere(Criteria::expr()->isNull('archived'));
        }

        return $this->debtCollectionMissions->matching($criteria);
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
     * @param string $role
     *
     * @return bool
     */
    private function hasRole(string $role): bool
    {
        foreach ($this->getProjectParticipants() as $projectParticipant) {
            if ($projectParticipant->hasRole($role)) {
                return true;
            }
        }

        return false;
    }

    /**
     * @param Companies $company
     * @param string    $role
     */
    private function addProjectParticipant(Companies $company, string $role): void
    {
        if (false === $this->isUniqueRole($role) || false === $this->hasRole($role)) {
            $projectParticipants = $this->getProjectParticipants($company);

            if ($projectParticipants->count()) {
                $projectParticipant = $projectParticipants->first();
            } else {
                $projectParticipant = (new ProjectParticipant())->setCompany($company)->setProject($this);
            }

            $projectParticipant->addRoles([$role]);
            $this->projectParticipants->add($projectParticipant);
        }
    }

    /**
     * @param string $role
     *
     * @return bool
     */
    private function isUniqueRole(string $role): bool
    {
        return in_array($role, [ProjectParticipant::COMPANY_ROLE_ARRANGER, ProjectParticipant::COMPANY_ROLE_AGENT, ProjectParticipant::COMPANY_ROLE_RUN]);
    }

    /**
     * @param Companies $company
     *
     * @return Projects
     */
    public function addArranger(Companies $company): Projects
    {
        $this->addProjectParticipant($company, ProjectParticipant::COMPANY_ROLE_ARRANGER);

        return $this;
    }

    /**
     * @param Companies $company
     *
     * @return Projects
     */
    public function addAgent(Companies $company): Projects
    {
        $this->addProjectParticipant($company, ProjectParticipant::COMPANY_ROLE_AGENT);

        return $this;
    }

    /**
     * @param Companies $company
     *
     * @return Projects
     */
    public function addRun(Companies $company): Projects
    {
        $this->addProjectParticipant($company, ProjectParticipant::COMPANY_ROLE_RUN);

        return $this;
    }

    /**
     * @param Companies[] $companies
     *
     * @return Projects
     */
    public function addLenders(array $companies): Projects
    {
        foreach ($companies as $company) {
            $this->addProjectParticipant($company, ProjectParticipant::COMPANY_ROLE_LENDER);
        }

        return $this;
    }

    /**
     * @param ProjectParticipant $projectParticipant
     *
     * @return Projects
     */
    public function removeProjectParticipants(ProjectParticipant $projectParticipant): Projects
    {
        $this->projectParticipants->removeElement($projectParticipant);

        return $this;
    }

    /**
     * @param Companies|null $companies
     *
     * @return ProjectParticipant[]|Collection
     */
    public function getProjectParticipants(?Companies $companies = null): iterable
    {
        $criteria = new Criteria();

        if ($companies) {
            $criteria->where(Criteria::expr()->eq('company', $companies));
        }

        return $this->projectParticipants->matching($criteria);
    }

    /**
     * @return ProjectParticipant|null
     */
    public function getArrangerParticipant(): ?ProjectParticipant
    {
        return $this->getParticipant(ProjectParticipant::COMPANY_ROLE_ARRANGER);
    }

    /**
     * @return ProjectParticipant|null
     */
    public function getAgentParticipant(): ?ProjectParticipant
    {
        return $this->getParticipant(ProjectParticipant::COMPANY_ROLE_AGENT);
    }

    /**
     * @return ProjectParticipant|null
     */
    public function getRunParticipant(): ?ProjectParticipant
    {
        return $this->getParticipant(ProjectParticipant::COMPANY_ROLE_RUN);
    }

    /**
     * @return Companies[]
     */
    public function getLenders(): array
    {
        $lenders = [];

        foreach ($this->getProjectParticipants() as $projectParticipant) {
            if ($projectParticipant->hasRole(ProjectParticipant::COMPANY_ROLE_LENDER)) {
                $lenders[] = $projectParticipant->getCompany();
            }
        }

        return $lenders;
    }

    /**
     * @param string $role
     *
     * @return ProjectParticipant|null
     */
    private function getParticipant(string $role): ?ProjectParticipant
    {
        foreach ($this->getProjectParticipants() as $projectParticipant) {
            if ($projectParticipant->hasRole($role)) {
                return $projectParticipant;
            }
        }

        return null;
    }

    /**
     * @return \DateTime|null
     */
    public function getEndDate(): ?\DateTime
    {
        return $this->getDateFin() ?? $this->getDateRetrait();
    }

    /**
     * @return bool
     * @throws \Exception
     */
    public function isOnline(): bool
    {
        return $this->getStatus() === ProjectsStatus::STATUS_PUBLISHED && (null === $this->getEndDate() || new \DateTime() < $this->getEndDate());
    }

    /**
     * @param int|null $status
     *
     * @return Bids[]|ArrayCollection
     */
    public function getBids(?int $status = null): iterable
    {
        $criteria = new Criteria();

        if (null !== $status) {
            $criteria->where(Criteria::expr()->eq('status', $status));
        }

        return $this->bids->matching($criteria);
    }

    /**
     * @return Loans[]|ArrayCollection
     */
    public function getLoans(): iterable
    {
        return $this->loans;
    }

    /**
     * @param ProjectPercentFee $projectPercentFee
     *
     * @return Projects
     */
    public function addProjectPercentFee(ProjectPercentFee $projectPercentFee): Projects
    {
        $projectPercentFee->setProject($this);

        if (false === $this->projectPercentFees->contains($projectPercentFee)) {
            $this->projectPercentFees->add($projectPercentFee);
        }

        return $this;
    }

    /**
     * @param ProjectPercentFee $projectPercentFee
     *
     * @return Projects
     */
    public function removeProjectPercentFee(ProjectPercentFee $projectPercentFee): Projects
    {
        if ($this->projectPercentFees->contains($projectPercentFee)) {
            $this->projectPercentFees->removeElement($projectPercentFee);
        }

        return $this;
    }

    /**
     * @return iterable|ProjectPercentFee[]
     */
    public function getProjectPercentFees(): iterable
    {
        return $this->projectPercentFees;
    }

    /**
     * @return ProjectComment[]|ArrayCollection
     */
    public function getComments(): iterable
    {
        return $this->comments;
    }
}
