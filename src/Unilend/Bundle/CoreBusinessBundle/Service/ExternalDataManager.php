<?php

namespace Unilend\Bundle\CoreBusinessBundle\Service;

use Doctrine\ORM\EntityManager;
use Unilend\Bundle\CoreBusinessBundle\Entity\CompanyRating;
use Unilend\Bundle\CoreBusinessBundle\Entity\CompanyRatingHistory;
use Unilend\Bundle\CoreBusinessBundle\Entity\InfolegaleExecutivePersonalChange;
use Unilend\Bundle\WSClientBundle\Entity\Altares\BalanceSheetListDetail;
use Unilend\Bundle\WSClientBundle\Entity\Altares\CompanyBalanceSheet;
use Unilend\Bundle\WSClientBundle\Entity\Altares\CompanyIdentityDetail;
use Unilend\Bundle\WSClientBundle\Entity\Altares\CompanyRatingDetail;
use Unilend\Bundle\WSClientBundle\Entity\Altares\FinancialSummaryListDetail;
use Unilend\Bundle\WSClientBundle\Entity\Codinf\IncidentList;
use Unilend\Bundle\WSClientBundle\Entity\Ellisphere\Report as EllispehereReport;
use Unilend\Bundle\WSClientBundle\Entity\Euler\CompanyRating as EulerCompanyRating;
use Unilend\Bundle\WSClientBundle\Entity\Infogreffe\CompanyIndebtedness;
use Unilend\Bundle\WSClientBundle\Entity\Infolegale\DirectorAnnouncement;
use Unilend\Bundle\WSClientBundle\Entity\Infolegale\Executive;
use Unilend\Bundle\WSClientBundle\Entity\Infolegale\Mandate;
use Unilend\Bundle\WSClientBundle\Entity\Infolegale\ScoreDetails;
use Unilend\Bundle\WSClientBundle\Service\AltaresManager;
use Unilend\Bundle\WSClientBundle\Service\CodinfManager;
use Unilend\Bundle\WSClientBundle\Service\EllisphereManager;
use Unilend\Bundle\WSClientBundle\Service\EulerHermesManager;
use Unilend\Bundle\WSClientBundle\Service\InfogreffeManager;
use Unilend\Bundle\WSClientBundle\Service\InfolegaleManager;

class ExternalDataManager
{
    /** @var EntityManager */
    private $entityManager;
    /** @var AltaresManager */
    private $altaresManager;
    /** @var EulerHermesManager */
    private $eulerHermesManager;
    /** @var CodinfManager */
    private $codinfManager;
    /** @var InfolegaleManager */
    private $infolegaleManager;
    /** @var InfogreffeManager */
    private $infogreffeManager;
    /** @var EllisphereManager */
    private $ellisphereManager;
    /** @var CompanyBalanceSheetManager */
    private $companyBalanceSheetManager;
    /** @var CompanyRatingHistory */
    private $companyRatingHistory;

    /**
     * @param EntityManager              $entityManager
     * @param AltaresManager             $altaresManager
     * @param EulerHermesManager         $eulerHermesManager
     * @param CodinfManager              $codinfManager
     * @param InfolegaleManager          $infolegaleManager
     * @param InfogreffeManager          $infogreffeManager
     * @param EllisphereManager          $ellisphereManager
     * @param CompanyBalanceSheetManager $companyBalanceSheetManager
     */
    public function __construct(
        EntityManager $entityManager,
        AltaresManager $altaresManager,
        EulerHermesManager $eulerHermesManager,
        CodinfManager $codinfManager,
        InfolegaleManager $infolegaleManager,
        InfogreffeManager $infogreffeManager,
        EllisphereManager $ellisphereManager,
        CompanyBalanceSheetManager $companyBalanceSheetManager
    )
    {
        $this->entityManager              = $entityManager;
        $this->altaresManager             = $altaresManager;
        $this->eulerHermesManager         = $eulerHermesManager;
        $this->codinfManager              = $codinfManager;
        $this->infolegaleManager          = $infolegaleManager;
        $this->infogreffeManager          = $infogreffeManager;
        $this->ellisphereManager          = $ellisphereManager;
        $this->companyBalanceSheetManager = $companyBalanceSheetManager;
    }

    /**
     * @param CompanyRatingHistory $companyRatingHistory
     *
     * @return ExternalDataManager
     */
    public function setCompanyRatingHistory(CompanyRatingHistory $companyRatingHistory)
    {
        $this->companyRatingHistory = $companyRatingHistory;

        return $this;
    }

    /**
     * @param string $siren
     *
     * @return CompanyIdentityDetail|null
     */
    public function getCompanyIdentity($siren)
    {
        $identity = $this->altaresManager->getCompanyIdentity($siren);

        if (null !== $identity && $this->companyRatingHistory instanceof CompanyRatingHistory) {
            $company = $this->companyRatingHistory->getIdCompany();

            if ($company->getSiren() === $siren) {
                $company->setName($company->getName() ? : $identity->getCorporateName());
                $company->setForme($company->getForme() ? : $identity->getCompanyForm());
                $company->setCapital($company->getCapital() ? : $identity->getCapital());
                $company->setCodeNaf($company->getCodeNaf() ? : $identity->getNAFCode());
                $company->setAdresse1($company->getAdresse1() ? : $identity->getAddress());
                $company->setCity($company->getCity() ? : $identity->getCity());
                $company->setZip($company->getZip() ? : $identity->getPostCode());
                $company->setSiret($company->getSiret() ? : $identity->getSiret());
                $company->setDateCreation($company->getDateCreation() ? : $identity->getCreationDate());
                $company->setRcs($company->getRcs() ? : $identity->getRcs());
                $company->setTribunalCom($company->getTribunalCom() ? : $identity->getCommercialCourt());

                $this->entityManager->flush($company);
            }
        }

        return $identity;
    }

    /**
     * @param string $siren
     *
     * @return CompanyRatingDetail
     */
    public function getAltaresScore($siren)
    {
        $score = $this->altaresManager->getScore($siren);

        if (
            null !== $score
            && $this->companyRatingHistory instanceof CompanyRatingHistory
            && $this->companyRatingHistory->getIdCompany()->getSiren() === $siren
            && false === $this->hasRating(CompanyRating::TYPE_ALTARES_SCORE_20)
        ) {

            $naf          = $this->companyRatingHistory->getIdCompany()->getCodeNaf();
            $xerfiScore   = 'N/A';
            $xerfiUnilend = 'PAS DE DONNEES';

            if ($xerfi = $this->entityManager->getRepository('UnilendCoreBusinessBundle:Xerfi')->findOneBy(['naf' => $naf])) {
                if (false === empty($xerfi->getScore())) {
                    $xerfiScore = $xerfi->getScore();
                }
                $xerfiUnilend = $xerfi->getUnilendRating();
            }

            $this->setRating(CompanyRating::TYPE_ALTARES_SCORE_20, $score->getScore20());
            $this->setRating(CompanyRating::TYPE_ALTARES_SECTORAL_SCORE_100, $score->getScore100());
            $this->setRating(CompanyRating::TYPE_ALTARES_VALUE_DATE, $score->getScoreDate()->format('Y-m-d'));
            $this->setRating(CompanyRating::TYPE_XERFI_RISK_SCORE, $xerfiScore);
            $this->setRating(CompanyRating::TYPE_UNILEND_XERFI_RISK, $xerfiUnilend);

            $this->entityManager->flush();
        }

        return $score;
    }

    /**
     * @param string $siren
     *
     * @return BalanceSheetListDetail
     */
    public function getBalanceSheets($siren)
    {
        $balanceSheets = $this->altaresManager->getBalanceSheets($siren);

        if (
            null !== $balanceSheets
            && $this->companyRatingHistory instanceof CompanyRatingHistory
            && $this->companyRatingHistory->getIdCompany()->getSiren() === $siren
        ) {
            $this->companyBalanceSheetManager->setCompanyBalance(
                $this->companyRatingHistory->getIdCompany(),
                $this->altaresManager->getBalanceSheets($this->companyRatingHistory->getIdCompany()->getSiren())
            );
        }

        return $balanceSheets;
    }

    /**
     * @param string              $siren
     * @param CompanyBalanceSheet $companyBalanceSheet
     *
     * @return FinancialSummaryListDetail|null
     */
    public function getFinancialSummary($siren, CompanyBalanceSheet $companyBalanceSheet)
    {
        return $this->altaresManager->getFinancialSummary($siren, $companyBalanceSheet->getBalanceSheetId());
    }

    /**
     * @param string              $siren
     * @param CompanyBalanceSheet $companyBalanceSheet
     *
     * @return FinancialSummaryListDetail|null
     */
    public function getBalanceManagementLine($siren, CompanyBalanceSheet $companyBalanceSheet)
    {
        return $this->altaresManager->getBalanceManagementLine($siren, $companyBalanceSheet->getBalanceSheetId());
    }

    /**
     * @param string    $siren
     * @param \DateTime $startDate
     * @param \DateTime $currentDate
     *
     * @return IncidentList|null
     */
    public function getPaymentIncidents($siren, \DateTime $startDate, \DateTime $currentDate)
    {
        return $this->codinfManager->getIncidentList($siren, $startDate, $currentDate);
    }

    /**
     * @param string $siren
     *
     * @return EulerCompanyRating|null
     */
    public function getEulerHermesTrafficLight($siren)
    {
        $trafficLight = $this->eulerHermesManager->getTrafficLight($siren, 'fr');

        if (
            null !== $trafficLight
            && $this->companyRatingHistory instanceof CompanyRatingHistory
            && $this->companyRatingHistory->getIdCompany()->getSiren() === $siren
            && false === $this->hasRating(CompanyRating::TYPE_EULER_HERMES_TRAFFIC_LIGHT)
        ) {
            $this->setRating(CompanyRating::TYPE_EULER_HERMES_TRAFFIC_LIGHT, $trafficLight->getColor());
        }

        return $trafficLight;
    }

    /**
     * @param string $siren
     *
     * @return EulerCompanyRating|null
     */
    public function getEulerHermesGrade($siren)
    {
        $eulerHermesGrade = $this->eulerHermesManager->getGrade($siren, 'fr');

        if (
            null !== $eulerHermesGrade
            && $this->companyRatingHistory instanceof CompanyRatingHistory
            && $this->companyRatingHistory->getIdCompany()->getSiren() === $siren
            && false === $this->hasRating(CompanyRating::TYPE_EULER_HERMES_GRADE)
        ) {
            $this->setRating(CompanyRating::TYPE_EULER_HERMES_GRADE, $eulerHermesGrade->getGrade());
        }

        return $eulerHermesGrade;
    }

    /**
     * @param string $siren
     *
     * @return EllispehereReport|null
     */
    public function getEllispehereReport($siren)
    {
        return $this->ellisphereManager->getReport($siren);
    }

    /**
     * @param $siren
     *
     * @return ScoreDetails|null
     */
    public function getInfolegaleScore($siren)
    {
        $score = $this->infolegaleManager->getScore($siren);

        if (
            null !== $score
            && $this->companyRatingHistory instanceof CompanyRatingHistory
            && $this->companyRatingHistory->getIdCompany()->getSiren() === $siren
            && false === $this->hasRating(CompanyRating::TYPE_INFOLEGALE_SCORE)
        ) {
            $this->setRating(CompanyRating::TYPE_INFOLEGALE_SCORE, $score->getScore());
        }

        return $score;
    }

    /**
     * @param string $siren
     *
     * @return CompanyIndebtedness|array|null
     */
    public function getIndebtedness($siren)
    {
        $indebtedness = $this->infogreffeManager->getIndebtedness($siren);

        if (
            null !== $indebtedness
            && $this->companyRatingHistory instanceof CompanyRatingHistory
            && $this->companyRatingHistory->getIdCompany()->getSiren() === $siren
            && false === $this->hasRating(CompanyRating::TYPE_INFOGREFFE_RETURN_CODE)
            && is_array($indebtedness)
            && isset($privileges['code'])
            && in_array($privileges['code'], [InfogreffeManager::RETURN_CODE_UNKNOWN_SIREN, InfogreffeManager::RETURN_CODE_UNAVAILABLE_INDEBTEDNESS, InfogreffeManager::RETURN_CODE_NO_DEBTOR])
        ) {
            $this->setRating(CompanyRating::TYPE_INFOGREFFE_RETURN_CODE, $privileges['code']);
        }

        return $indebtedness;
    }

    /**
     * @param string $siren
     *
     * @return Executive[]
     */
    public function getExecutives($siren)
    {
        return $this->infolegaleManager->getExecutives($siren)->getExecutives();
    }

    /**
     * @param string $executiveId
     *
     * @return Mandate[]
     */
    public function getExecutiveMandates($executiveId)
    {
        return $this->infolegaleManager->getMandates($executiveId)->getMandates();
    }

    /**
     * @param $executiveId
     *
     * @return DirectorAnnouncement[]
     */
    public function getDirectorAnnouncements($executiveId)
    {
        return $this->infolegaleManager->getDirectorAnnouncements($executiveId)->getAnnouncements();
    }

    /**
     * @param Executive $executive
     */
    public function refreshExecutiveChanges($executive)
    {
        $mandates                 = $this->infolegaleManager->getMandates($executive->getExecutiveId())->getMandates();
        $personalChangeRepository = $this->entityManager->getRepository('UnilendCoreBusinessBundle:InfolegaleExecutivePersonalChange');
        $refreshedCompanyPosition = [];

        foreach ($mandates as $mandate) {
            if (isset($refreshedCompanyPosition[$mandate->getSiren()][$mandate->getPosition()->getCode()])) {
                continue;
            }
            $refreshedCompanyPosition[$mandate->getSiren()] = $mandate->getPosition()->getCode();

            $change = $personalChangeRepository->findOneBy([
                'idExecutive'  => $executive->getExecutiveId(),
                'siren'        => $mandate->getSiren(),
                'codePosition' => $mandate->getPosition()->getCode()
            ]);

            if (null === $change) {
                $change = new InfolegaleExecutivePersonalChange();
                $change->setIdExecutive($executive->getExecutiveId())
                    ->setFirstName($executive->getFirstName())
                    ->setLastName($executive->getName())
                    ->setSiren($mandate->getSiren())
                    ->setPosition($mandate->getPosition()->getLabel())
                    ->setCodePosition($mandate->getPosition()->getCode());
                $this->entityManager->persist($change);
            }

            if (null === $change->getNominated()) {
                $change->setNominated($this->getExecutiveNominated($mandate->getSiren(), $executive->getExecutiveId(), $mandate->getPosition()->getCode()));
            }
            if (null === $change->getEnded()) {
                $change->setEnded($this->getExecutiveEnded($mandates, $mandate->getSiren(), $mandate->getPosition()->getCode()));
            }

            $this->entityManager->flush($change);
        }
    }

    /**
     * @param string $siren
     * @param int    $executiveId
     * @param string $positionCode
     *
     * @return \DateTime|null
     */
    private function getExecutiveNominated($siren, $executiveId, $positionCode)
    {
        $nominated = null;
        $mandates  = $this->infolegaleManager->getMandates($executiveId)->getMandates();
        foreach ($mandates as $mandate) {
            if ($siren !== $mandate->getSiren() || $positionCode !== $mandate->getPosition()->getCode()) {
                continue;
            }
            if (Mandate::CHANGE_NOMINATION === $mandate->getChange()) {
                if ($mandate->getChangeDate()) {
                    $nominated = $mandate->getChangeDate();
                    break;
                }
            } elseif (in_array($mandate->getChange(), [Mandate::CHANGE_MODIFICATION, Mandate::CHANGE_CONFIRMATION, Mandate::CHANGE_UNSPECIFIED])) {
                if ($mandate->getChangeDate()) {
                    if (null === $nominated) {
                        $nominated = $mandate->getChangeDate();
                    } else {
                        $nominated = $nominated > $mandate->getChangeDate() ? $mandate->getChangeDate() : $nominated;
                    }
                }
            }
        }

        return $nominated;
    }

    /**
     * @param Mandate[] $mandates
     * @param string    $siren
     * @param string    $positionCode
     *
     * @return \DateTime|null
     */
    private function getExecutiveEnded($mandates, $siren, $positionCode)
    {
        $ended = null;
        foreach ($mandates as $mandate) {
            if ($siren !== $mandate->getSiren() || $positionCode !== $mandate->getPosition()->getCode()) {
                continue;
            }
            if (in_array($mandate->getChange(), [Mandate::CHANGE_REVOCATION, Mandate::CHANGE_RESIGN, Mandate::CHANGE_DEAD, Mandate::CHANGE_LEFT])) {
                if ($mandate->getChangeDate()) {
                    $ended = $mandate->getChangeDate();
                    break;
                }
            }
        }

        return $ended;
    }

    /**
     * @param string $type
     *
     * @return bool
     */
    private function hasRating($type)
    {
        $companyRatingRespository = $this->entityManager->getRepository('UnilendCoreBusinessBundle:CompanyRating');
        $companyRating            = $companyRatingRespository->findOneBy([
            'idCompanyRatingHistory' => $this->companyRatingHistory->getIdCompanyRatingHistory(),
            'type'                   => $type
        ]);

        return (null !== $companyRating);
    }

    /**
     * @param string $type
     * @param string $value
     */
    private function setRating($type, $value)
    {
        $companyRating = new CompanyRating();
        $companyRating->setIdCompanyRatingHistory($this->companyRatingHistory);
        $companyRating->setType($type);
        $companyRating->setValue($value);

        $this->entityManager->persist($companyRating);
    }
}
