<?php

namespace Unilend\Entity;

use Doctrine\Common\Collections\{ArrayCollection, Collection, Criteria};
use Doctrine\ORM\Mapping as ORM;
use Ramsey\Uuid\Exception\UnsatisfiedDependencyException;
use Ramsey\Uuid\Uuid;
use Unilend\Entity\Traits\Timestampable;

/**
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

    public const AUTO_REPAYMENT_ON  = 0;
    public const AUTO_REPAYMENT_OFF = 1;

    public const DISPLAY_YES = 0;
    public const DISPLAY_NO  = 1;

    // project rating mapping (letter to start)
    public const RISK_A = 5;
    public const RISK_B = 4.5;
    public const RISK_C = 4;
    public const RISK_D = 3.5;
    public const RISK_E = 3;
    public const RISK_F = 2.5;
    public const RISK_G = 2;
    public const RISK_H = 1.5;
    public const RISK_I = 1;
    public const RISK_J = 0;

    public const DEFAULT_COMMISSION_RATE_FUNDS     = 4;
    public const DEFAULT_COMMISSION_RATE_REPAYMENT = 1;

    public const PROJECT_PHOTO_PATH = 'public/default/images/dyn/projets/source/';

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
     * @var Companies
     *
     * @ORM\ManyToOne(targetEntity="Unilend\Entity\Companies")
     * @ORM\JoinColumns({
     *     @ORM\JoinColumn(name="id_company", referencedColumnName="id_company", nullable=false)
     * })
     */
    private $idCompany;

    /**
     * @var Companies
     *
     * @ORM\ManyToOne(targetEntity="Unilend\Entity\Companies")
     * @ORM\JoinColumns({
     *     @ORM\JoinColumn(name="id_target_company", referencedColumnName="id_company")
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
     * @var Users
     *
     * @ORM\ManyToOne(targetEntity="Unilend\Entity\Users")
     * @ORM\JoinColumns({
     *     @ORM\JoinColumn(name="id_analyste", referencedColumnName="id_user")
     * })
     */
    private $idAnalyste;

    /**
     * @var Users
     *
     * @ORM\ManyToOne(targetEntity="Unilend\Entity\Users")
     * @ORM\JoinColumns({
     *     @ORM\JoinColumn(name="id_commercial", referencedColumnName="id_user")
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
     * @var Partner
     *
     * @ORM\ManyToOne(targetEntity="Unilend\Entity\Partner")
     * @ORM\JoinColumns({
     *     @ORM\JoinColumn(name="id_partner", referencedColumnName="id", nullable=false)
     * })
     */
    private $idPartner;

    /**
     * @var Companies
     *
     * @ORM\ManyToOne(targetEntity="Unilend\Entity\Companies")
     * @ORM\JoinColumns({
     *     @ORM\JoinColumn(name="id_company_submitter", referencedColumnName="id_company")
     * })
     */
    private $idCompanySubmitter;

    /**
     * @var Clients
     *
     * @ORM\ManyToOne(targetEntity="Unilend\Entity\Clients")
     * @ORM\JoinColumns({
     *     @ORM\JoinColumn(name="id_client_submitter", referencedColumnName="id_client")
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
     *     @ORM\JoinColumn(name="id_project", referencedColumnName="id_project")
     * })
     */
    private $mandates;

    /**
     * @deprecated
     *
     * @var ProjectsComments[]
     *
     * @ORM\OneToMany(targetEntity="Unilend\Entity\ProjectsComments", mappedBy="idProject")
     * @ORM\OrderBy({"added": "DESC"})
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
     *     @ORM\JoinColumn(name="id_project", referencedColumnName="id_project")
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
     * @ORM\OrderBy({"added": "DESC"})
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
     * @ORM\OrderBy({"added": "DESC"})
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
     * @return string
     */
    public function getHash()
    {
        return $this->hash;
    }

    /**
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
     * @return string
     */
    public function getSlug()
    {
        return $this->slug;
    }

    /**
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
     * @return Companies
     */
    public function getIdCompany()
    {
        return $this->idCompany;
    }

    /**
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
     * @return Companies
     */
    public function getIdTargetCompany()
    {
        return $this->idTargetCompany;
    }

    /**
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
     * @return float
     */
    public function getAmount()
    {
        return $this->amount;
    }

    /**
     * @param int $period
     *
     * @return Projects
     */
    public function setPeriod($period)
    {
        $this->period = $period;

        return $this;
    }

    /**
     * @return int
     */
    public function getPeriod()
    {
        return $this->period;
    }

    /**
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
     * @return string
     */
    public function getTitle()
    {
        return $this->title;
    }

    /**
     * @param int $idBorrowingMotive
     *
     * @return Projects
     */
    public function setIdBorrowingMotive($idBorrowingMotive)
    {
        $this->idBorrowingMotive = $idBorrowingMotive;

        return $this;
    }

    /**
     * @return int
     */
    public function getIdBorrowingMotive()
    {
        return $this->idBorrowingMotive;
    }

    /**
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
     * @return string
     */
    public function getPhotoProjet()
    {
        return $this->photoProjet;
    }

    /**
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
     * @return string
     */
    public function getDescription()
    {
        return $this->description;
    }

    /**
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
     * @return string
     */
    public function getNatureProject()
    {
        return $this->natureProject;
    }

    /**
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
     * @return string
     */
    public function getObjectifLoan()
    {
        return $this->objectifLoan;
    }

    /**
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
     * @return string
     */
    public function getPresentationCompany()
    {
        return $this->presentationCompany;
    }

    /**
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
     * @return string
     */
    public function getMeansRepayment()
    {
        return $this->meansRepayment;
    }

    /**
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
     * @return Users
     */
    public function getIdAnalyste()
    {
        return $this->idAnalyste;
    }

    /**
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
     * @return Users
     */
    public function getIdCommercial()
    {
        return $this->idCommercial;
    }

    /**
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
     * @return \DateTime
     */
    public function getDatePublication()
    {
        return $this->datePublication;
    }

    /**
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
     * @return \DateTime
     */
    public function getDateFunded()
    {
        return $this->dateFunded;
    }

    /**
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
     * @return \DateTime
     */
    public function getDateRetrait()
    {
        return $this->dateRetrait;
    }

    /**
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
     * @return \DateTime
     */
    public function getDateFin()
    {
        return $this->dateFin;
    }

    /**
     * @param int $idDernierBilan
     *
     * @return Projects
     */
    public function setIdDernierBilan($idDernierBilan)
    {
        $this->idDernierBilan = $idDernierBilan;

        return $this;
    }

    /**
     * @return int
     */
    public function getIdDernierBilan()
    {
        return $this->idDernierBilan;
    }

    /**
     * @param int $balanceCount
     *
     * @return Projects
     */
    public function setBalanceCount($balanceCount)
    {
        $this->balanceCount = $balanceCount;

        return $this;
    }

    /**
     * @return int
     */
    public function getBalanceCount()
    {
        return $this->balanceCount;
    }

    /**
     * @param int $idCompanyRatingHistory
     *
     * @return Projects
     */
    public function setIdCompanyRatingHistory($idCompanyRatingHistory)
    {
        $this->idCompanyRatingHistory = $idCompanyRatingHistory;

        return $this;
    }

    /**
     * @return int
     */
    public function getIdCompanyRatingHistory()
    {
        return $this->idCompanyRatingHistory;
    }

    /**
     * @param int $idProjectNeed
     *
     * @return Projects
     */
    public function setIdProjectNeed($idProjectNeed)
    {
        $this->idProjectNeed = $idProjectNeed;

        return $this;
    }

    /**
     * @return int
     */
    public function getIdProjectNeed()
    {
        return $this->idProjectNeed;
    }

    /**
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
     * @return bool
     */
    public function isCreateBo()
    {
        return $this->createBo;
    }

    /**
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
     * @return string
     */
    public function getRisk()
    {
        return $this->risk;
    }

    /**
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
     * @return float
     */
    public function getFondsPropresDeclaraClient()
    {
        return $this->fondsPropresDeclaraClient;
    }

    /**
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
     * @return float
     */
    public function getResultatExploitationDeclaraClient()
    {
        return $this->resultatExploitationDeclaraClient;
    }

    /**
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
     * @return float
     */
    public function getCaDeclaraClient()
    {
        return $this->caDeclaraClient;
    }

    /**
     * @param int $rembAuto
     *
     * @return Projects
     */
    public function setRembAuto($rembAuto)
    {
        $this->rembAuto = $rembAuto;

        return $this;
    }

    /**
     * @return int
     */
    public function getRembAuto()
    {
        return $this->rembAuto;
    }

    /**
     * @param int $display
     *
     * @return Projects
     */
    public function setDisplay($display)
    {
        $this->display = $display;

        return $this;
    }

    /**
     * @return int
     */
    public function getDisplay()
    {
        return $this->display;
    }

    /**
     * @param int $idRate
     *
     * @return Projects
     */
    public function setIdRate($idRate)
    {
        $this->idRate = $idRate;

        return $this;
    }

    /**
     * @return int
     */
    public function getIdRate()
    {
        return $this->idRate;
    }

    /**
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
     * @return Partner
     */
    public function getIdPartner()
    {
        return $this->idPartner;
    }

    /**
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
     * @return Companies|null
     */
    public function getIdCompanySubmitter(): ?Companies
    {
        return $this->idCompanySubmitter;
    }

    /**
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
     * @return Clients|null
     */
    public function getIdClientSubmitter(): ?Clients
    {
        return $this->idClientSubmitter;
    }

    /**
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
     * @return string
     */
    public function getCommissionRateFunds()
    {
        return $this->commissionRateFunds;
    }

    /**
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
     * @return string
     */
    public function getCommissionRateRepayment()
    {
        return $this->commissionRateRepayment;
    }

    /**
     * @param int $idProduct
     *
     * @return Projects
     */
    public function setIdProduct($idProduct)
    {
        $this->idProduct = $idProduct;

        return $this;
    }

    /**
     * @return int
     */
    public function getIdProduct()
    {
        return $this->idProduct;
    }

    /**
     * @return int
     */
    public function getIdProject()
    {
        return $this->idProject;
    }

    /**
     * @param int $status
     *
     * @return Projects
     */
    public function setStatus($status)
    {
        $this->status = $status;

        return $this;
    }

    /**
     * @return int
     */
    public function getStatus()
    {
        return $this->status;
    }

    /**
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
     * @return float|null
     */
    public function getInterestRate(): ?float
    {
        return $this->interestRate;
    }

    /**
     * @return ProjectAttachment[]
     */
    public function getAttachments()
    {
        return $this->attachments;
    }

    /**
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
     *
     * @return ArrayCollection|ProjectsComments[]
     */
    public function getPublicMemos(): ArrayCollection
    {
        $criteria = Criteria::create()
            ->where(Criteria::expr()->eq('public', true))
            ->orderBy(['added' => Criteria::DESC])
        ;

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
        // @todo to be removed when projects is fully under doctrine
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
                ->where(Criteria::expr()->isNull('archived'))
            ;
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
            ->where(Criteria::expr()->eq('type', DebtCollectionMission::TYPE_AMICABLE))
        ;

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
            ->where(Criteria::expr()->eq('type', DebtCollectionMission::TYPE_LITIGATION))
        ;

        if (false === $includeArchived) {
            $criteria->andWhere(Criteria::expr()->isNull('archived'));
        }

        return $this->debtCollectionMissions->matching($criteria);
    }

    /**
     * @ORM\PrePersist
     */
    public function setHashValue()
    {
        if (null === $this->hash) {
            try {
                $this->hash = $this->generateHash();
            } catch (UnsatisfiedDependencyException $exception) {
                $this->hash = md5(uniqid());
            }
        }
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
     * @return \DateTime|null
     */
    public function getEndDate(): ?\DateTime
    {
        return $this->getDateFin() ?? $this->getDateRetrait();
    }

    /**
     * @throws \Exception
     *
     * @return bool
     */
    public function isOnline(): bool
    {
        return ProjectsStatus::STATUS_PUBLISHED === $this->getStatus() && (null === $this->getEndDate() || new \DateTime() < $this->getEndDate());
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

    /**
     * @throws \Exception
     *
     * @return string
     */
    private function generateHash()
    {
        $uuid4 = Uuid::uuid4();

        return $uuid4->toString();
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
        return in_array($role, [ProjectParticipant::COMPANY_ROLE_ARRANGER, ProjectParticipant::COMPANY_ROLE_RUN]);
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
}
