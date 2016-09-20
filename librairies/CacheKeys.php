<?php

namespace Unilend\librairies;

/**
 * Class Cache
 * @package Unilend\librairies
 */
class CacheKeys
{
    const SHORT_TIME  = 300;
    const MEDIUM_TIME = 1800;
    const LONG_TIME   = 3600;
    const DAY         = 86400;

    /**
     * constant for list and count projects
     */
    const LIST_PROJECTS = 'List_Counter_Projects';
    const AVG_RATE_PROJECTS = 'projects_getAvgRate';
    const BID_ACCEPTATION_POSSIBILITY = 'bids_getAcceptationPossibilityRounded';

    /**
     * constants for statistics
     */
    const LENDERS_IN_COMMUNITY                = 'stats_numberOfLendersInCommunity';
    const ACTIVE_LENDERS                      = 'stats_numberOfActiveLenders';
    const FINANCED_PROJECTS                   = 'stats_numberOfFinancedProjects';
    const AMOUNT_BORRWED                      = 'stats_amountBorrowed';
    const UNILEND_IRR                         = 'stats_unilendIRR';
    const PERCENT_FULLY_FINANCED_PROJECTS     = 'stats_percentageSuccessfullyFinancedProjects';
    const AVG_FUNDING_TIME                    = 'stats_averageFundingTime';
    const AVG_INTEREST_RATE_LENDERS           = 'stats_averageInterestRateForLenders';
    const AVG_LENDER_ON_PROJECT               = 'stats_averageNumberOfLenders';
    const AVG_PROJECT_AMOUNT                  = 'stats_averageProjectAmount';
    const AVG_LOAN_AMOUNT                     = 'stats_averageLoanAmount';
    const NUMBER_PROJECT_REQUESTS             = 'stats_numberOfProjectRequests';
    const PERCENT_ACCEPTED_PROJECTS           = 'stats_percentageOfAcceptedProjects';
    const AVG_LENDER_IRR                      = 'stats_averageLenderIRR';
    const LENDERS_BY_TYPE                     = 'stats_lendersByType';
    const LENDERS_BY_REGION                   = 'stats_lendersByRegion';
    const PROJECTS_BY_REGION                  = 'stats_projectsByRegion';
    const TOTAL_REPAID_CAPITAL                = 'stats_totalRepaidCapital';
    const TOTAL_REPAID_INTERST                = 'stats_totalRepaidInterests';
    const PROJECTS_BY_CATEGORY                = 'stats_projectCountByCategory';
    const PROJECTS_FUNDED_IN_24_HOURS         = 'stats_numberOfProjectsFundedIn24Hours';
    const PERCENT_PROJECTS_FUNDED_IN_24_HOURS = 'stats_percentageOfProjectsFundedIn24Hours';
    const BID_EVERY_X_SECOND                  = 'stats_secondsForBid';
    const AMOUNT_FINANCED_HIGHEST_FASTEST     = 'stats_highestAmountObtainedFastest';

    public static function getStatisticsCacheKeys()
    {
        return [
            self::LENDERS_IN_COMMUNITY,
            self::ACTIVE_LENDERS,
            self::FINANCED_PROJECTS,
            self::AMOUNT_BORRWED,
            self::UNILEND_IRR,
            self::PERCENT_FULLY_FINANCED_PROJECTS,
            self::AVG_FUNDING_TIME,
            self::AVG_INTEREST_RATE_LENDERS,
            self::AVG_LENDER_ON_PROJECT,
            self::AVG_PROJECT_AMOUNT,
            self::AVG_LOAN_AMOUNT,
            self::NUMBER_PROJECT_REQUESTS,
            self::PERCENT_ACCEPTED_PROJECTS,
            self::AVG_LENDER_IRR,
            self::LENDERS_BY_TYPE,
            self::LENDERS_BY_REGION,
            self::PROJECTS_BY_REGION,
            self::TOTAL_REPAID_CAPITAL,
            self::TOTAL_REPAID_INTERST,
            self::PROJECTS_BY_CATEGORY,
            self::PROJECTS_FUNDED_IN_24_HOURS,
            self::PERCENT_PROJECTS_FUNDED_IN_24_HOURS,
            self::BID_EVERY_X_SECOND,
            self::AMOUNT_FINANCED_HIGHEST_FASTEST
        ];
    }

}
