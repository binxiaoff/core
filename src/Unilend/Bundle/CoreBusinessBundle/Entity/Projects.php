<?php

namespace Unilend\Bundle\CoreBusinessBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * Projects
 *
 * @ORM\Table(name="projects", indexes={@ORM\Index(name="id_company", columns={"id_company"}), @ORM\Index(name="slug", columns={"slug"}), @ORM\Index(name="status", columns={"status"}), @ORM\Index(name="display", columns={"display"}), @ORM\Index(name="date_retrait", columns={"date_retrait"}), @ORM\Index(name="stop_relances", columns={"stop_relances"}), @ORM\Index(name="hash", columns={"hash"}), @ORM\Index(name="id_prescripteur", columns={"id_prescripteur"}), @ORM\Index(name="process_fast", columns={"process_fast"}), @ORM\Index(name="id_commercial", columns={"id_commercial"}), @ORM\Index(name="id_dernier_bilan", columns={"id_dernier_bilan"})})
 * @ORM\Entity
 */
class Projects
{
    //todo: move to the debt collection entity
    const DEBT_COLLECTION_CONDITION_CHANGEMENT_DATE = '2016-04-19';
    /**
     * @var string
     *
     * @ORM\Column(name="hash", type="string", length=191, nullable=false)
     */
    private $hash;

    /**
     * @var string
     *
     * @ORM\Column(name="slug", type="string", length=191, nullable=false)
     */
    private $slug;

    /**
     * @var \Unilend\Bundle\CoreBusinessBundle\Entity\Companies
     *
     * @ORM\ManyToOne(targetEntity="Unilend\Bundle\CoreBusinessBundle\Entity\Companies")
     * @ORM\JoinColumns({
     *   @ORM\JoinColumn(name="id_company", referencedColumnName="id_company")
     * })
     */
    private $idCompany;

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
     * @ORM\Column(name="id_prescripteur", type="integer", nullable=false)
     */
    private $idPrescripteur;

    /**
     * @var float
     *
     * @ORM\Column(name="amount", type="float", precision=10, scale=0, nullable=false)
     */
    private $amount;

    /**
     * @var integer
     *
     * @ORM\Column(name="period", type="integer", nullable=false)
     */
    private $period;

    /**
     * @var string
     *
     * @ORM\Column(name="title", type="string", length=191, nullable=false)
     */
    private $title;

    /**
     * @var integer
     *
     * @ORM\Column(name="id_borrowing_motive", type="integer", nullable=true)
     */
    private $idBorrowingMotive;

    /**
     * @var string
     *
     * @ORM\Column(name="title_bo", type="string", length=191, nullable=false)
     */
    private $titleBo;

    /**
     * @var string
     *
     * @ORM\Column(name="photo_projet", type="string", length=191, nullable=false)
     */
    private $photoProjet;

    /**
     * @var string
     *
     * @ORM\Column(name="comments", type="text", length=16777215, nullable=false)
     */
    private $comments;

    /**
     * @var string
     *
     * @ORM\Column(name="nature_project", type="text", length=16777215, nullable=false)
     */
    private $natureProject;

    /**
     * @var string
     *
     * @ORM\Column(name="objectif_loan", type="text", length=16777215, nullable=false)
     */
    private $objectifLoan;

    /**
     * @var string
     *
     * @ORM\Column(name="presentation_company", type="text", length=16777215, nullable=false)
     */
    private $presentationCompany;

    /**
     * @var string
     *
     * @ORM\Column(name="means_repayment", type="text", length=16777215, nullable=false)
     */
    private $meansRepayment;

    /**
     * @var integer
     *
     * @ORM\Column(name="type", type="integer", nullable=false)
     */
    private $type;

    /**
     * @var integer
     *
     * @ORM\Column(name="stand_by", type="integer", nullable=false)
     */
    private $standBy;

    /**
     * @var integer
     *
     * @ORM\Column(name="id_analyste", type="integer", nullable=false)
     */
    private $idAnalyste;

    /**
     * @var integer
     *
     * @ORM\Column(name="id_commercial", type="integer", nullable=false)
     */
    private $idCommercial;

    /**
     * @var \DateTime
     *
     * @ORM\Column(name="date_publication", type="datetime", nullable=false)
     */
    private $datePublication;

    /**
     * @var \DateTime
     *
     * @ORM\Column(name="date_funded", type="datetime", nullable=false)
     */
    private $dateFunded;

    /**
     * @var \DateTime
     *
     * @ORM\Column(name="date_retrait", type="datetime", nullable=false)
     */
    private $dateRetrait;

    /**
     * @var \DateTime
     *
     * @ORM\Column(name="date_fin", type="datetime", nullable=false)
     */
    private $dateFin;

    /**
     * @var integer
     *
     * @ORM\Column(name="id_dernier_bilan", type="integer", nullable=false)
     */
    private $idDernierBilan;

    /**
     * @var integer
     *
     * @ORM\Column(name="balance_count", type="integer", nullable=false)
     */
    private $balanceCount;

    /**
     * @var integer
     *
     * @ORM\Column(name="id_company_rating_history", type="integer", nullable=false)
     */
    private $idCompanyRatingHistory;

    /**
     * @var integer
     *
     * @ORM\Column(name="id_project_need", type="integer", nullable=false)
     */
    private $idProjectNeed;

    /**
     * @var integer
     *
     * @ORM\Column(name="create_bo", type="integer", nullable=false)
     */
    private $createBo;

    /**
     * @var string
     *
     * @ORM\Column(name="risk", type="string", length=2, nullable=false)
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
     * @var integer
     *
     * @ORM\Column(name="process_fast", type="integer", nullable=false)
     */
    private $processFast = '0';

    /**
     * @var integer
     *
     * @ORM\Column(name="remb_auto", type="integer", nullable=false)
     */
    private $rembAuto;

    /**
     * @var integer
     *
     * @ORM\Column(name="stop_relances", type="integer", nullable=false)
     */
    private $stopRelances = '0';

    /**
     * @var integer
     *
     * @ORM\Column(name="display", type="integer", nullable=false)
     */
    private $display;

    /**
     * @var integer
     *
     * @ORM\Column(name="id_rate", type="integer", nullable=false)
     */
    private $idRate;

    /**
     * @var \Unilend\Bundle\CoreBusinessBundle\Entity\Partner
     *
     * @ORM\ManyToOne(targetEntity="Unilend\Bundle\CoreBusinessBundle\Entity\Partner")
     * @ORM\JoinColumns({
     *   @ORM\JoinColumn(name="id_partner", referencedColumnName="id")
     * })
     */
    private $idPartner;

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
     * @var integer
     *
     * @ORM\Column(name="id_product", type="integer", nullable=false)
     */
    private $idProduct;

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
     * @ORM\Column(name="id_project", type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="IDENTITY")
     */
    private $idProject;

    /**
     * @var integer
     *
     * @ORM\Column(name="status", type="integer", nullable=false)
     */
    private $status;



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
     * Set idPartenaire
     *
     * @param integer $idPartenaire
     *
     * @return Projects
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
     * @return Projects
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
     * Set idPrescripteur
     *
     * @param integer $idPrescripteur
     *
     * @return Projects
     */
    public function setIdPrescripteur($idPrescripteur)
    {
        $this->idPrescripteur = $idPrescripteur;

        return $this;
    }

    /**
     * Get idPrescripteur
     *
     * @return integer
     */
    public function getIdPrescripteur()
    {
        return $this->idPrescripteur;
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
     * Set titleBo
     *
     * @param string $titleBo
     *
     * @return Projects
     */
    public function setTitleBo($titleBo)
    {
        $this->titleBo = $titleBo;

        return $this;
    }

    /**
     * Get titleBo
     *
     * @return string
     */
    public function getTitleBo()
    {
        return $this->titleBo;
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
     * Set comments
     *
     * @param string $comments
     *
     * @return Projects
     */
    public function setComments($comments)
    {
        $this->comments = $comments;

        return $this;
    }

    /**
     * Get comments
     *
     * @return string
     */
    public function getComments()
    {
        return $this->comments;
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
     * Set type
     *
     * @param integer $type
     *
     * @return Projects
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
     * Set standBy
     *
     * @param integer $standBy
     *
     * @return Projects
     */
    public function setStandBy($standBy)
    {
        $this->standBy = $standBy;

        return $this;
    }

    /**
     * Get standBy
     *
     * @return integer
     */
    public function getStandBy()
    {
        return $this->standBy;
    }

    /**
     * Set idAnalyste
     *
     * @param integer $idAnalyste
     *
     * @return Projects
     */
    public function setIdAnalyste($idAnalyste)
    {
        $this->idAnalyste = $idAnalyste;

        return $this;
    }

    /**
     * Get idAnalyste
     *
     * @return integer
     */
    public function getIdAnalyste()
    {
        return $this->idAnalyste;
    }

    /**
     * Set idCommercial
     *
     * @param integer $idCommercial
     *
     * @return Projects
     */
    public function setIdCommercial($idCommercial)
    {
        $this->idCommercial = $idCommercial;

        return $this;
    }

    /**
     * Get idCommercial
     *
     * @return integer
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
     * @param integer $createBo
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
     * @return integer
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
     * Set processFast
     *
     * @param integer $processFast
     *
     * @return Projects
     */
    public function setProcessFast($processFast)
    {
        $this->processFast = $processFast;

        return $this;
    }

    /**
     * Get processFast
     *
     * @return integer
     */
    public function getProcessFast()
    {
        return $this->processFast;
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
     * Set stopRelances
     *
     * @param integer $stopRelances
     *
     * @return Projects
     */
    public function setStopRelances($stopRelances)
    {
        $this->stopRelances = $stopRelances;

        return $this;
    }

    /**
     * Get stopRelances
     *
     * @return integer
     */
    public function getStopRelances()
    {
        return $this->stopRelances;
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
    public function setPartner($idPartner)
    {
        $this->idPartner = $idPartner;

        return $this;
    }

    /**
     * Get idPartner
     *
     * @return Partner
     */
    public function getPartner()
    {
        return $this->idPartner;
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
     * Set added
     *
     * @param \DateTime $added
     *
     * @return Projects
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
     * @return Projects
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
}
