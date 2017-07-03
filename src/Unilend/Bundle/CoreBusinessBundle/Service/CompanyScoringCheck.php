<?php

namespace Unilend\Bundle\CoreBusinessBundle\Service;

use Psr\Cache\CacheItemPoolInterface;
use Psr\Log\LoggerInterface;
use Unilend\Bundle\CoreBusinessBundle\Entity\ProjectsStatus;
use Unilend\Bundle\CoreBusinessBundle\Service\Simulator\EntityManager;
use Unilend\Bundle\WSClientBundle\Entity\Altares\CompanyRatingDetail as AltaresCompanyRating;
use Unilend\Bundle\WSClientBundle\Entity\Euler\CompanyRating as EulerCompanyRating;
use Unilend\Bundle\WSClientBundle\Service\AltaresManager;
use Unilend\Bundle\WSClientBundle\Service\EulerHermesManager;
use Unilend\Bundle\WSClientBundle\Service\InfolegaleManager;

class CompanyScoringCheck
{
    /** @var EntityManager */
    private $entityManager;
    /** @var CompanyBalanceSheetManager */
    private $companyBalanceSheetManager;
    /** @var ProjectManager */
    private $projectManager;
    /** @var CacheItemPoolInterface */
    private $cacheItemPool;
    /** @var LoggerInterface */
    private $logger;
    /** @var AltaresManager */
    private $wsAltares;
    /** @var InfolegaleManager */
    private $wsInfolegale;
    /** @var EulerHermesManager */
    private $wsEuler;

    /**
     * @param EntityManager              $entityManager
     * @param CompanyBalanceSheetManager $companyBalanceSheetManager
     * @param ProjectManager             $projectManager
     * @param CacheItemPoolInterface     $cacheItemPool
     * @param LoggerInterface            $logger
     * @param AltaresManager             $wsAltares
     * @param InfolegaleManager          $wsInfolegale
     * @param EulerHermesManager         $wsEuler
     */
    public function __construct(
        EntityManager $entityManager,
        CompanyBalanceSheetManager $companyBalanceSheetManager,
        ProjectManager $projectManager,
        CacheItemPoolInterface $cacheItemPool,
        LoggerInterface $logger,
        AltaresManager $wsAltares,
        InfolegaleManager $wsInfolegale,
        EulerHermesManager $wsEuler
    )
    {
        $this->entityManager              = $entityManager;
        $this->companyBalanceSheetManager = $companyBalanceSheetManager;
        $this->projectManager             = $projectManager;
        $this->cacheItemPool              = $cacheItemPool;
        $this->logger                     = $logger;
        $this->wsAltares                  = $wsAltares;
        $this->wsInfolegale               = $wsInfolegale;
        $this->wsEuler                    = $wsEuler;
    }

    /**
     * @param string $siren
     *
     * @return null|AltaresCompanyRating
     */
    public function getAltaresScore($siren)
    {
        try {
            return $this->wsAltares->getScore($siren);
        } catch (\Exception $exception) {
            $this->logger->error(
                'Could not get Altares score: AltaresManager::getScore(' . $siren . '). Message: ' . $exception->getMessage(),
                ['class' => __CLASS__, 'function' => __FUNCTION__, 'siren' => $siren]
            );
        }

        return null;
    }

    /**
     * @param null|AltaresCompanyRating    $altaresScore
     * @param string                       $rejectionReason
     * @param null|\company_rating_history $companyRatingHistory
     * @param null|\company_rating         $companyRating
     *
     * @return bool
     */
    public function isAltaresScoreLow($altaresScore, &$rejectionReason, \company_rating_history $companyRatingHistory = null, \company_rating $companyRating = null)
    {
        if (null !== $altaresScore) {
            if (null !== $companyRatingHistory && null !== $companyRating) {
                $this->setRatingData($companyRatingHistory, $companyRating, \company_rating::TYPE_ALTARES_SCORE_20, $altaresScore->getScore20());
                $this->setRatingData($companyRatingHistory, $companyRating, \company_rating::TYPE_ALTARES_SECTORAL_SCORE_100, $altaresScore->getSectoralScore100());
                $this->setRatingData($companyRatingHistory, $companyRating, \company_rating::TYPE_ALTARES_VALUE_DATE, $altaresScore->getScoreDate()->format('Y-m-d'));
            }

            if ($altaresScore->getScore20() < 4) {
                $rejectionReason = ProjectsStatus::NON_ELIGIBLE_REASON_LOW_ALTARES_SCORE;

                return true;
            }

            return false;
        }

        $rejectionReason = ProjectsStatus::UNEXPECTED_RESPONSE . 'altares_score';
        return true;
    }

    /**
     * @param string                       $codeNaf
     * @param string                       $rejectionReason
     * @param null|\company_rating_history $companyRatingHistory
     * @param null|\company_rating         $companyRating
     *
     * @return bool
     */
    public function isXerfiUnilendOk($codeNaf, &$rejectionReason, \company_rating_history $companyRatingHistory = null, \company_rating $companyRating = null)
    {
        /** @var \xerfi $xerfi */
        $xerfi = $this->entityManager->getRepository('xerfi');

        if (false === $xerfi->get($codeNaf)) {
            $xerfiScore   = 'N/A';
            $xerfiUnilend = 'PAS DE DONNEES';
        } elseif ('' === $xerfi->score) {
            $xerfiScore   = 'N/A';
            $xerfiUnilend = $xerfi->unilend_rating;
        } else {
            $xerfiScore   = $xerfi->score;
            $xerfiUnilend = $xerfi->unilend_rating;
        }

        if (null !== $companyRatingHistory && null !== $companyRating) {
            $this->setRatingData($companyRatingHistory, $companyRating, \company_rating::TYPE_XERFI_RISK_SCORE, $xerfiScore);
            $this->setRatingData($companyRatingHistory, $companyRating, \company_rating::TYPE_UNILEND_XERFI_RISK, $xerfiUnilend);
        }

        if ('ELIMINATOIRE' === $xerfi->unilend_rating) {
            $rejectionReason = ProjectsStatus::NON_ELIGIBLE_REASON_UNILEND_XERFI_ELIMINATION_SCORE;

            return false;
        }

        return true;
    }

    /**
     * @param AltaresCompanyRating $altaresScore
     * @param string               $codeNaf
     * @param string               $rejectionReason
     *
     * @return bool
     */
    public function combineAltaresScoreAndUnilendXerfi(AltaresCompanyRating $altaresScore, $codeNaf, &$rejectionReason)
    {
        /** @var \xerfi $xerfi */
        $xerfi = $this->entityManager->getRepository('xerfi');

        if (in_array($altaresScore->getScore20(), [4, 5]) && $xerfi->get($codeNaf) && $xerfi->score <= 75) {
            $rejectionReason = ProjectsStatus::NON_ELIGIBLE_REASON_UNILEND_XERFI_VS_ALTARES_SCORE;

            return false;
        }

        return true;
    }

    /**
     * @param AltaresCompanyRating    $altaresScore
     * @param \companies              $company
     * @param \company_rating_history $companyRatingHistory
     * @param \company_rating         $companyRating
     * @param string                  $rejectionReason
     *
     * @return bool
     */
    public function combineEulerTrafficLightXerfiAltaresScore(AltaresCompanyRating $altaresScore, \companies $company, &$rejectionReason, \company_rating_history $companyRatingHistory = null, \company_rating $companyRating = null)
    {
        try {
            if (null !== ($eulerTrafficLight = $this->wsEuler->getTrafficLight($company->siren, 'fr'))) {
                if (null !== $companyRatingHistory && null !== $companyRating) {
                    $this->setRatingData($companyRatingHistory, $companyRating, \company_rating::TYPE_EULER_HERMES_TRAFFIC_LIGHT, $eulerTrafficLight->getColor());
                }

                if (in_array($eulerTrafficLight->getColor(), [EulerCompanyRating::COLOR_WHITE, EulerCompanyRating::COLOR_GREEN, EulerCompanyRating::COLOR_YELLOW])) {
                    return true;
                }

                if ($eulerTrafficLight->getColor() === EulerCompanyRating::COLOR_BLACK) {
                    $rejectionReason = ProjectsStatus::NON_ELIGIBLE_REASON_EULER_TRAFFIC_LIGHT;
                    return false;
                }

                if ($eulerTrafficLight->getColor() === EulerCompanyRating::COLOR_RED && $altaresScore->getScore20() < 12) {
                    $rejectionReason = ProjectsStatus::NON_ELIGIBLE_REASON_EULER_TRAFFIC_LIGHT_VS_ALTARES_SCORE;
                    return false;
                }

                /** @var \xerfi $xerfi */
                $xerfi = $this->entityManager->getRepository('xerfi');
                $xerfi->get($company->code_naf);

                if ($eulerTrafficLight->getColor() === EulerCompanyRating::COLOR_RED && $xerfi->score > 75) {
                    $rejectionReason = ProjectsStatus::NON_ELIGIBLE_REASON_EULER_TRAFFIC_LIGHT_VS_UNILEND_XERFI;
                    return false;
                }

                return true;
            }
        } catch (\Exception $exception) {
            $this->logger->error(
                'Could not get Euler Traffic Light cross score: ' . $company->siren . '. Message: ' . $exception->getMessage(),
                ['class' => __CLASS__, 'function' => __FUNCTION__, 'siren', $company->siren]
            );
        }

        $rejectionReason = ProjectsStatus::UNEXPECTED_RESPONSE . 'euler_traffic_light_score';
        return false;
    }

    /**
     * @param string $siren
     * @param string $countryCode
     *
     * @return bool
     */
    public function isEulerTrafficLightWhite($siren, $countryCode = 'fr')
    {
        try {
            $trafficLight = $this->wsEuler->getTrafficLight($siren, $countryCode);

            if (null !== $trafficLight && EulerCompanyRating::COLOR_WHITE === $trafficLight->getColor()) {
                return true;
            }
        } catch (\Exception $exception) {
            $this->logger->error(
                'Could not get Euler traffic light: EulerHermesManager::getTrafficLight(' . $siren . ', ' . $countryCode . '). Message: ' . $exception->getMessage(),
                ['class' => __CLASS__, 'function' => __FUNCTION__, 'siren' => $siren]
            );
        }

        return false;
    }

    /**
     * @param string                       $siren
     * @param string                       $rejectionReason
     * @param null|\company_rating_history $companyRatingHistory
     * @param null|\company_rating         $companyRating
     *
     * @return bool
     */
    public function isInfolegaleScoreLow($siren, &$rejectionReason, \company_rating_history $companyRatingHistory = null, \company_rating $companyRating = null)
    {
        try {
            if (null !== ($infolegaleScore = $this->wsInfolegale->getScore($siren))) {
                if (null !== $companyRatingHistory && null !== $companyRating) {
                    $this->setRatingData($companyRatingHistory, $companyRating, \company_rating::TYPE_INFOLEGALE_SCORE, $infolegaleScore->getScore());
                }

                if ($infolegaleScore->getScore() < 5) {
                    $rejectionReason = ProjectsStatus::NON_ELIGIBLE_REASON_LOW_INFOLEGALE_SCORE;
                    return true;
                }

                return false;
            }
        } catch (\Exception $exception) {
            $this->logger->error(
                'Could not get infolegale score: InfolegaleManager::getScore(' . $siren . '). Message: ' . $exception->getMessage(),
                ['class' => __CLASS__, 'function' => __FUNCTION__, 'siren' => $siren]
            );
        }

        $rejectionReason = ProjectsStatus::UNEXPECTED_RESPONSE . 'infolegale_score';
        return true;
    }

    /**
     * @param AltaresCompanyRating         $altaresScore
     * @param \companies                   $company
     * @param string                       $rejectionReason
     * @param null|\company_rating_history $companyRatingHistory
     * @param null|\company_rating         $companyRating
     *
     * @return bool
     */
    public function combineEulerGradeUnilendXerfiAltaresScore(AltaresCompanyRating $altaresScore, \companies $company, &$rejectionReason, \company_rating_history $companyRatingHistory = null, \company_rating $companyRating = null)
    {
        try {
            if (null !== ($eulerGrade = $this->wsEuler->getGrade($company->siren, 'fr'))) {
                if (null !== $companyRatingHistory && null !== $companyRating) {
                    $this->setRatingData($companyRatingHistory, $companyRating, \company_rating::TYPE_EULER_HERMES_GRADE, $eulerGrade->getGrade());
                }

                /** @var \xerfi $xerfi */
                $xerfi = $this->entityManager->getRepository('xerfi');
                $xerfi->get($company->code_naf);

                if ($eulerGrade->getGrade() >= 9 || ($eulerGrade->getGrade() == 8 && $xerfi->score > 75)) {
                    $rejectionReason = ProjectsStatus::NON_ELIGIBLE_REASON_EULER_GRADE_VS_UNILEND_XERFI;

                    return false;
                }

                if ($eulerGrade->getGrade() >= 5 && $altaresScore->getScore20() == 4 || $eulerGrade->getGrade() >= 7 && $altaresScore->getScore20() == 5) {
                    $rejectionReason = ProjectsStatus::NON_ELIGIBLE_REASON_EULER_GRADE_VS_ALTARES_SCORE;

                    return false;
                }

                return true;
            }
        } catch (\Exception $exception) {
            $this->logger->error(
                'Could not get Euler grade: EulerHermesManager::getGrade(' . $company->siren . '). Message: ' . $exception->getMessage(),
                ['class' => __CLASS__, 'function' => __FUNCTION__, 'siren', $company->siren]
            );
        }

        $rejectionReason = ProjectsStatus::UNEXPECTED_RESPONSE . 'euler_grade';

        return false;
    }

    /**
     * @param \company_rating_history $companyRatingHistory
     * @param \company_rating         $companyRating
     * @param string                  $ratingType
     * @param mixed                   $ratingValue
     */
    private function setRatingData(\company_rating_history $companyRatingHistory, \company_rating $companyRating, $ratingType, $ratingValue)
    {
        $companyRating->id_company_rating_history = $companyRatingHistory->id_company_rating_history;
        $companyRating->type                      = $ratingType;
        $companyRating->value                     = $ratingValue;
        $companyRating->create();
    }
}
