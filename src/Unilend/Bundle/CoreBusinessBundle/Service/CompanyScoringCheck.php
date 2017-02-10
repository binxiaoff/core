<?php

namespace Unilend\Bundle\CoreBusinessBundle\Service;

use Psr\Cache\CacheItemPoolInterface;
use Psr\Log\LoggerInterface;
use Unilend\Bundle\CoreBusinessBundle\Service\Simulator\EntityManager;
use Unilend\Bundle\WSClientBundle\Entity\Altares\CompanyRating;
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
    public function __construct(EntityManager $entityManager, CompanyBalanceSheetManager $companyBalanceSheetManager, ProjectManager $projectManager, CacheItemPoolInterface $cacheItemPool, LoggerInterface $logger, AltaresManager $wsAltares, InfolegaleManager $wsInfolegale, EulerHermesManager $wsEuler)
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
     * @return null|CompanyRating
     */
    public function getAltaresScore($siren)
    {
        try {
            return $this->wsAltares->getScore($siren);
        } catch (\Exception $exception) {
            $this->logger->error('Could not get Altares score: AltaresManager::getScore(' . $siren . '). Message: ' . $exception->getMessage(),
                ['class' => __CLASS__, 'function' => __FUNCTION__, 'siren', $siren]);
        }

        return null;
    }

    /**
     * @param CompanyRating           $altaresScore
     * @param \company_rating_history $companyRatingHistory
     * @param \company_rating         $companyRating
     * @param string                  $rejectionReason
     * @return bool
     */
    public function isAltaresScoreLow(CompanyRating $altaresScore, \company_rating_history $companyRatingHistory, \company_rating $companyRating, &$rejectionReason)
    {
        if (null !== $altaresScore) {
            $this->setRatingData($companyRatingHistory, $companyRating, \company_rating::TYPE_ALTARES_SCORE_20, $altaresScore->getScore20());
            $this->setRatingData($companyRatingHistory, $companyRating, \company_rating::TYPE_ALTARES_SECTORAL_SCORE_100, $altaresScore->getSectoralScore100());
            $this->setRatingData($companyRatingHistory, $companyRating, \company_rating::TYPE_ALTARES_VALUE_DATE, $altaresScore->getScoreDate()->format('Y-m-d'));

            if ($altaresScore->getScore20() < 4) {
                $rejectionReason = \projects_status::NON_ELIGIBLE_REASON_LOW_ALTARES_SCORE;

                return true;
            } else {
                return false;
            }
        }
        $rejectionReason = \projects_status::UNEXPECTED_RESPONSE . 'altares_score';

        return true;
    }

    /**
     * @param string                  $codeNaf
     * @param \company_rating_history $companyRatingHistory
     * @param \company_rating         $companyRating
     * @param                         $rejectionReason
     * @return bool
     */
    public function isXerfiUnilendOk($codeNaf, \company_rating_history $companyRatingHistory, \company_rating $companyRating, &$rejectionReason)
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

        $this->setRatingData($companyRatingHistory, $companyRating, \company_rating::TYPE_XERFI_RISK_SCORE, $xerfiScore);
        $this->setRatingData($companyRatingHistory, $companyRating, \company_rating::TYPE_UNILEND_XERFI_RISK, $xerfiUnilend);

        if ('ELIMINATOIRE' === $xerfi->unilend_rating) {
            $rejectionReason = \projects_status::NON_ELIGIBLE_REASON_UNILEND_XERFI_ELIMINATION_SCORE;

            return false;
        }

        return true;
    }

    /**
     * @param null|CompanyRating $altaresScore
     * @param string             $codeNaf
     * @param string             $rejectionReason
     * @return bool
     */
    public function combineAltaresScoreAndUnilendXerfi($altaresScore, $codeNaf, &$rejectionReason)
    {
        /** @var \xerfi $xerfi */
        $xerfi = $this->entityManager->getRepository('xerfi');

        if (in_array($altaresScore->getScore20(), [4, 5]) && $xerfi->get($codeNaf) && $xerfi->score <= 75) {
            $rejectionReason = \projects_status::NON_ELIGIBLE_REASON_UNILEND_XERFI_VS_ALTARES_SCORE;

            return false;
        }

        return true;
    }

    /**
     * @param string                  $siren
     * @param \company_rating_history $companyRatingHistory
     * @param \company_rating         $companyRating
     * @param string                  $rejectionReason
     * @return bool
     */
    public function isInfolegaleScoreLow($siren, \company_rating_history $companyRatingHistory, \company_rating $companyRating, &$rejectionReason)
    {
        try {
            if (null !== ($infolegaleScore = $this->wsInfolegale->getScore($siren))) {
                $this->setRatingData($companyRatingHistory, $companyRating, \company_rating::TYPE_INFOLEGALE_SCORE, $infolegaleScore->getScore());

                if ($infolegaleScore->getScore() < 5) {
                    $rejectionReason = \projects_status::NON_ELIGIBLE_REASON_LOW_INFOLEGALE_SCORE;

                    return true;
                }

                return false;
            }
        } catch (\Exception $exception) {
            $this->logger->error('Could not get infolegale score: InfolegaleManager::getScore(' . $siren . '). Message: ' . $exception->getMessage(),
                ['class' => __CLASS__, 'function' => __FUNCTION__, 'siren', $siren]);
        }
        $rejectionReason = \projects_status::UNEXPECTED_RESPONSE . 'infolegal_score';

        return true;
    }

    /**
     * @param null|CompanyRating      $altaresScore
     * @param \companies              $company
     * @param \company_rating_history $companyRatingHistory
     * @param \company_rating         $companyRating
     * @param string                  $rejectionReason
     * @return bool
     */
    public function combineEulerGradeUnilendXerfiAltaresScore($altaresScore, \companies $company, \company_rating_history $companyRatingHistory, \company_rating $companyRating, &$rejectionReason)
    {
        try {
            /** @var \pays_v2 $country */
            $country = $this->entityManager->getRepository('pays_v2');
            $country->get($company->id_pays);

            if (null !== ($eulerGrade = $this->wsEuler->getGrade($company->siren, (empty($country->iso)) ? 'fr' : $country->iso))) {
                $this->setRatingData($companyRatingHistory, $companyRating, \company_rating::TYPE_EULER_HERMES_GRADE, $eulerGrade->getGrade());
                /** @var \xerfi $xerfi */
                $xerfi = $this->entityManager->getRepository('xerfi');
                $xerfi->get($company->code_naf);

                if ($eulerGrade->getGrade() >= 9 || ($eulerGrade->getGrade() == 8 && $xerfi->score > 75)) {
                    $rejectionReason = \projects_status::NON_ELIGIBLE_REASON_UNILEND_XERFI_VS_EULER_GRADE;

                    return false;
                }

                if (($eulerGrade->getGrade() >= 5 && $altaresScore->getScore20() == 4) || ($eulerGrade->getGrade() >= 7 && $altaresScore->getScore20() == 5)) {
                    $rejectionReason = \projects_status::NON_ELIGIBLE_REASON_EULER_GRADE_VS_ALTARES_SCORE;

                    return false;
                }

                return true;
            }
        } catch (\Exception $exception) {
            $this->logger->error('Could not get Euler grade: EulerHermesManager::getGrade(' . $company->siren . '). Message: ' . $exception->getMessage(),
                ['class' => __CLASS__, 'function' => __FUNCTION__, 'siren', $company->siren]);
        }
        $rejectionReason = \projects_status::UNEXPECTED_RESPONSE . 'euler_grade';

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
        $this->logger->info('Company rating created: id=' . $companyRating->id_company_rating . ' type=' . $companyRating->type . ' value=' . $companyRating->value,
            ['class' => __CLASS__, 'function' => __FUNCTION__, 'company_rating_history_id' => $companyRatingHistory->id_company_rating_history]);
    }
}
