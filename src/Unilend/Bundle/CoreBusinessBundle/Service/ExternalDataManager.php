<?php

namespace Unilend\Bundle\CoreBusinessBundle\Service;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\OptimisticLockException;
use Psr\Log\LoggerInterface;
use Unilend\Bundle\CoreBusinessBundle\Entity\{
    AddressType, CompanyRating, CompanyRatingHistory, InfolegaleExecutivePersonalChange, PaysV2
};
use Unilend\Bundle\WSClientBundle\Entity\Altares\{
    BalanceSheetListDetail, CompanyBalanceSheet, CompanyIdentityDetail, CompanyRatingDetail, FinancialSummaryListDetail
};
use Unilend\Bundle\WSClientBundle\Entity\Codinf\IncidentList;
use Unilend\Bundle\WSClientBundle\Entity\Ellisphere\Report as EllisphereReport;
use Unilend\Bundle\WSClientBundle\Entity\Euler\CompanyRating as EulerCompanyRating;
use Unilend\Bundle\WSClientBundle\Entity\Infogreffe\CompanyIndebtedness;
use Unilend\Bundle\WSClientBundle\Entity\Infolegale\{
    AnnouncementDetails, DirectorAnnouncement, Executive, Mandate, ScoreDetails
};
use Unilend\Bundle\WSClientBundle\Service\{
    AltaresManager, CodinfManager, EllisphereManager, EulerHermesManager, InfogreffeManager, InfolegaleManager
};

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
    /** @var AddressManager */
    private $addressManager;
    /** @var LoggerInterface */
    private $logger;

    /**
     * @param EntityManager              $entityManager
     * @param AltaresManager             $altaresManager
     * @param EulerHermesManager         $eulerHermesManager
     * @param CodinfManager              $codinfManager
     * @param InfolegaleManager          $infolegaleManager
     * @param InfogreffeManager          $infogreffeManager
     * @param EllisphereManager          $ellisphereManager
     * @param CompanyBalanceSheetManager $companyBalanceSheetManager
     * @param AddressManager             $addressManager
     * @param LoggerInterface            $logger
     */
    public function __construct(
        EntityManager $entityManager,
        AltaresManager $altaresManager,
        EulerHermesManager $eulerHermesManager,
        CodinfManager $codinfManager,
        InfolegaleManager $infolegaleManager,
        InfogreffeManager $infogreffeManager,
        EllisphereManager $ellisphereManager,
        CompanyBalanceSheetManager $companyBalanceSheetManager,
        AddressManager $addressManager,
        LoggerInterface $logger
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
        $this->addressManager             = $addressManager;
        $this->logger                     = $logger;
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
     *
     * @throws \Exception
     */
    public function getCompanyIdentity($siren)
    {
        try {
            $identity = $this->altaresManager->getCompanyIdentity($siren);

            if (null !== $identity && $this->companyRatingHistory instanceof CompanyRatingHistory) {
                $company = $this->companyRatingHistory->getIdCompany();

                if ($company->getSiren() === $siren) {
                    $company->setName($company->getName() ? : $identity->getCorporateName());
                    $company->setLegalFormCode($company->getLegalFormCode() ? : $identity->getLegalFormCode());
                    $company->setForme($company->getForme() ? : $identity->getCompanyForm());
                    $company->setCapital($company->getCapital() ? : $identity->getCapital());
                    $company->setCodeNaf($company->getCodeNaf() ? : $identity->getNAFCode());
                    $company->setSiret($company->getSiret() ? : $identity->getSiret());
                    $company->setDateCreation($company->getDateCreation() ? : $identity->getCreationDate());
                    $company->setRcs($company->getRcs() ? : $identity->getRcs());
                    $company->setTribunalCom($company->getTribunalCom() ? : $identity->getCommercialCourt());

                    $this->entityManager->flush($company);

                    if (null !== $identity->getAddress() && null !== $identity->getPostCode() && null !== $identity->getCity()) {
                        $this->addressManager->saveCompanyAddress(
                            $identity->getAddress(),
                            $identity->getPostCode(),
                            $identity->getCity(),
                            PaysV2::COUNTRY_FRANCE,
                            $company,
                            AddressType::TYPE_MAIN_ADDRESS
                        );
                    }
                }
            }

            return $identity;
        } catch (\Exception $exception) {
            throw $exception;
        }
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
            $this->setRating(CompanyRating::TYPE_ALTARES_SECTORAL_SCORE_20, $score->getSectoralScore20());
            $this->setRating(CompanyRating::TYPE_ALTARES_VALUE_DATE, $score->getScoreDate()->format('Y-m-d'));
            $this->setRating(CompanyRating::TYPE_XERFI_RISK_SCORE, $xerfiScore);
            $this->setRating(CompanyRating::TYPE_UNILEND_XERFI_RISK, $xerfiUnilend);
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
     * @throws \Exception
     */
    public function getEulerHermesGrade(string $siren): ?EulerCompanyRating
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
     * @return EllisphereReport|null
     */
    public function getEllisphereReport($siren)
    {
        return $this->ellisphereManager->getReport($siren);
    }

    /**
     * @param string $siren
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
            && isset($indebtedness['code'])
            && in_array($indebtedness['code'], [InfogreffeManager::RETURN_CODE_UNKNOWN_SIREN, InfogreffeManager::RETURN_CODE_UNAVAILABLE_INDEBTEDNESS, InfogreffeManager::RETURN_CODE_NO_DEBTOR])
        ) {
            $this->setRating(CompanyRating::TYPE_INFOGREFFE_RETURN_CODE, $indebtedness['code']);
        }

        return $indebtedness;
    }

    /**
     * @param string $siren
     *
     * @return Executive[]|array
     */
    public function getExecutives($siren)
    {
        $executives = $this->infolegaleManager->getExecutives($siren);
        if (null !== $executives) {
            return $executives->getExecutives();
        }

        return [];
    }

    /**
     * @param int $executiveId
     *
     * @return Mandate[]|array
     */
    public function getExecutiveMandates($executiveId)
    {
        $mandates = $this->infolegaleManager->getMandates($executiveId);
        if (null !== $mandates) {
            return $mandates->getMandates();
        }

        return [];
    }

    /**
     * @param int $executiveId
     *
     * @return DirectorAnnouncement[]|array
     */
    public function getExecutiveAnnouncements($executiveId)
    {
        $executiveAnnouncements = $this->infolegaleManager->getDirectorAnnouncements($executiveId);
        if (null !== $executiveAnnouncements) {
            return $executiveAnnouncements->getAnnouncements();
        }

        return [];
    }

    /**
     * @param string $siren
     *
     * @throws \Exception
     */
    public function refreshExecutiveChanges($siren)
    {
        $executives         = $this->infolegaleManager->getExecutives($siren)->getExecutives();
        $refreshedExecutive = [];
        foreach ($executives as $executive) {
            if (in_array($executive->getExecutiveId(), $refreshedExecutive)) {
                continue;
            }
            $refreshedExecutive[] = $executive->getExecutiveId();

            $mandateCollection = $this->infolegaleManager->getMandates($executive->getExecutiveId());
            if (null === $mandateCollection) {
                continue;
            }
            $mandates                 = $mandateCollection->getMandates();
            $personalChangeRepository = $this->entityManager->getRepository('UnilendCoreBusinessBundle:InfolegaleExecutivePersonalChange');
            $refreshedCompanyPosition = [];

            foreach ($mandates as $mandate) {
                if (isset($refreshedCompanyPosition[$mandate->getSiren()][$mandate->getPosition()->getCode()])) {
                    continue;
                }
                $refreshedCompanyPosition[$mandate->getSiren()][$mandate->getPosition()->getCode()] = $mandate->getPosition()->getCode();

                $change = $personalChangeRepository->findOneBy([
                    'idExecutive'  => $executive->getExecutiveId(),
                    'siren'        => $mandate->getSiren(),
                    'codePosition' => $mandate->getPosition()->getCode()
                ]);

                if (null === $change) {
                    try {
                        $change = new InfolegaleExecutivePersonalChange();
                        $change->setIdExecutive($executive->getExecutiveId())
                            ->setFirstName($executive->getFirstName())
                            ->setLastName($executive->getName())
                            ->setSiren($mandate->getSiren())
                            ->setPosition($mandate->getPosition()->getLabel())
                            ->setCodePosition($mandate->getPosition()->getCode());
                        $this->entityManager->persist($change);
                        $this->entityManager->flush($change);
                    } catch (\Exception $exception) {
                        $this->logger->error(
                            'Could not save the Infolegal personal change into DB using id_executive: ' .
                            $executive->getExecutiveId() . ', siren: ' . $mandate->getSiren() . ', position: ' . $mandate->getPosition()->getCode() . '. Error: ' . $exception->getMessage(),
                            ['method' => __METHOD__, 'file' => $exception->getFile(), 'line' => $exception->getLine()]
                        );
                        continue;
                    }
                }

                if (null === $change->getNominated()) {
                    $change->setNominated($this->getExecutiveNominated($mandate->getSiren(), $executive->getExecutiveId(), $mandate->getPosition()->getCode()));
                }
                if (null === $change->getEnded()) {
                    $change->setEnded($this->getExecutiveEnded($mandate->getSiren(), $executive->getExecutiveId(), $mandate->getPosition()->getCode()));
                }
                $this->entityManager->flush($change);
            }
        }
    }

    /**
     * @param           $siren
     * @param \DateTime $sinceDate
     *
     * @return array
     */
    public function getAllMandatesExceptGivenSirenOnActiveExecutives($siren, \DateTime $sinceDate) : array
    {
        return $this->entityManager->getRepository('UnilendCoreBusinessBundle:InfolegaleExecutivePersonalChange')
            ->getAllMandatesExceptGivenSirenOnActiveExecutives($siren, $sinceDate);
    }

    /**
     * @param $siren
     * @param $sinceDate
     *
     * @return InfolegaleExecutivePersonalChange[]
     */
    public function getAllPreviousExecutivesMandatesSince($siren, $sinceDate) : array
    {
        $previousExecutives = $this->entityManager->getRepository('UnilendCoreBusinessBundle:InfolegaleExecutivePersonalChange')
            ->getPreviousExecutivesLeftAfter($siren, $sinceDate);

        return $this->entityManager->getRepository('UnilendCoreBusinessBundle:InfolegaleExecutivePersonalChange')
            ->findMandatesByExecutivesSince($previousExecutives, $sinceDate);
    }

    /**
     * @param int    $executiveId
     * @param string $siren
     * @param int    $extended
     *
     * @return array
     */
    public function getPeriodForExecutiveInACompany(int $executiveId, string $siren, int $extended) : array
    {
        $now     = new \DateTime();
        $started = $now;
        $ended   = $now;
        $changes = $this->entityManager->getRepository('UnilendCoreBusinessBundle:InfolegaleExecutivePersonalChange')->findBy([
            'idExecutive' => $executiveId,
            'siren'       => $siren
        ]);
        foreach ($changes as $change) {
            if (null !== $change->getEnded()) {
                $ended = $change->getEnded();
            }

            if (null === $change->getNominated()) {
                $mockedNominatedDate = new \DateTime('5 years ago');
                if ($change->getEnded() > $mockedNominatedDate) {
                    $started = $mockedNominatedDate;
                } else {
                    $started = $change->getEnded();
                }
            } else {
                $started = $change->getNominated();
            }
        }

        return ['started' => $started, 'ended' => $ended->modify('+' . $extended . ' year')];
    }

    /**
     * @param string $siren
     * @param int    $executiveId
     * @param string $positionCode
     *
     * @return \DateTime|null
     */
    public function getExecutiveNominated($siren, $executiveId, $positionCode)
    {
        $nominated         = null;
        $mandateCollection = $this->infolegaleManager->getMandates($executiveId);
        if ($mandateCollection) {
            $mandates = $mandateCollection->getMandates();
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
        }

        return $nominated;
    }

    /**
     * @param string $siren
     * @param int    $executiveId
     * @param string $positionCode
     *
     * @return \DateTime|null
     */
    public function getExecutiveEnded($siren, $executiveId, $positionCode)
    {
        $ended             = null;
        $mandateCollection = $this->infolegaleManager->getMandates($executiveId);

        if ($mandateCollection) {
            $mandates = $mandateCollection->getMandates();

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
        }

        return $ended;
    }

    /**
     * @param string   $siren
     * @param int|null $publishedSinceYears Number of years since announcement was published
     *
     * @return AnnouncementDetails[]
     */
    public function getAnnouncements($siren, $publishedSinceYears = null)
    {
        $id                      = [];
        $announcementDetails     = [];

        $announcementsCollection = $this->infolegaleManager->getAnnouncements($siren);
        if (null === $announcementsCollection) {
            return [];
        }
        $announcements = $announcementsCollection->getAnnouncements();

        if (null !== $publishedSinceYears) {
            $dateLimit = (new \DateTime())->sub(new \DateInterval('P' . $publishedSinceYears . 'Y'))->setTime(0, 0, 0);
        }

        foreach ($announcements as $announcement) {
            if (null === $publishedSinceYears || isset($dateLimit) && $announcement->getPublishedDate() >= $dateLimit) {
                $id[] = $announcement->getId();
            }
        }

        if (empty($id)) {
            return [];
        }
        /** The WS getAnnouncementsDetails accepts a maximum of 100 exec IDs in the parameter list */
        foreach (array_chunk($id, 100) as $execIds) {
            /** @var ArrayCollection $announcementPage */
            $announcementPage = $this->infolegaleManager->getAnnouncementsDetails($execIds)->getAnnouncementDetails();
            $this->logger->info('Execs IDs: ' . \GuzzleHttp\json_encode($execIds), ['siren' => $siren, 'method' => __METHOD__, 'line' => __LINE__]);

            if ($announcementPage->count()) {
                $this->logger->info('Number of annoucements details found: ' . $announcementPage->count(), ['siren' => $siren, 'method' => __METHOD__, 'line' => __LINE__]);
                $announcementDetails = array_merge($announcementDetails, $announcementPage->toArray());
            }
        }

        return $announcementDetails;
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
    private function setRating(string $type, string $value)
    {
        $companyRating = new CompanyRating();
        $companyRating->setIdCompanyRatingHistory($this->companyRatingHistory);
        $companyRating->setType($type);
        $companyRating->setValue($value);

        $this->entityManager->persist($companyRating);
        try {
            $this->entityManager->flush($companyRating);
        } catch (OptimisticLockException $exception) {
            $this->logger->error(
                'Could not save the company rating type ' . $type . ' with value ' . $value . ' Using company rating history ID ' . $this->companyRatingHistory->getIdCompanyRatingHistory() . ' Error: ' . $exception->getMessage(),
                ['method' => __METHOD__, 'id_company' => $this->companyRatingHistory->getIdCompany()->getIdCompany(), 'file' => $exception->getFile(), 'line' => $exception->getLine()]
            );
        }
    }
}
