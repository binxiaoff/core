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
    const LIST_PROJECTS               = 'List_Counter_Projects';
    const AVG_RATE_PROJECTS           = 'projects_getAvgRate';
    const BID_ACCEPTATION_POSSIBILITY = 'bids_getAcceptationPossibilityRounded';

    /**
     * constant for product
     */
    const PRODUCT_ATTRIBUTE_BY_TYPE          = 'product_attribute_by_type';
    const PRODUCT_CONTRACT_ATTRIBUTE_BY_TYPE = 'product_contract_attribute_by_type';

    /**
     * constant for underlying contract
     */
    const CONTRACT_ATTRIBUTE_BY_TYPE = 'contract_attribute_by_type';

    /**
     * constants for statistics calculated on the go after expiry time
     */
    const LENDERS_IN_COMMUNITY                = 'stats_numberOfLendersInCommunity';
    const ACTIVE_LENDERS                      = 'stats_numberOfActiveLenders';
    const FINANCED_PROJECTS                   = 'stats_numberOfFinancedProjects';
    const UNILEND_IRR                         = 'stats_unilendIRR';
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
    const PROJECTS_BY_CATEGORY                = 'stats_projectCountByCategory';
    const PROJECTS_FUNDED_IN_24_HOURS         = 'stats_numberOfProjectsFundedIn24Hours';
    const PERCENT_PROJECTS_FUNDED_IN_24_HOURS = 'stats_percentageOfProjectsFundedIn24Hours';
    const BID_EVERY_X_SECOND                  = 'stats_secondsForBid';
    const AMOUNT_FINANCED_HIGHEST_FASTEST     = 'stats_highestAmountObtainedFastest';

    /** constants for statistics calculated by cron */
    const REGULATORY_TABLE   = 'stats_regulatoryData';
    const INCIDENCE_RATE_IFP = 'stats_incidence_rate_IFP';

    public static function getStatisticsCacheKeys()
    {
        return [
            self::LENDERS_IN_COMMUNITY,
            self::ACTIVE_LENDERS,
            self::FINANCED_PROJECTS,
            self::UNILEND_IRR,
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
            self::PROJECTS_BY_CATEGORY,
            self::PROJECTS_FUNDED_IN_24_HOURS,
            self::PERCENT_PROJECTS_FUNDED_IN_24_HOURS,
            self::BID_EVERY_X_SECOND,
            self::AMOUNT_FINANCED_HIGHEST_FASTEST
        ];
    }

}
