<?php

namespace Unilend\librairies;

/**
 * Class Cache
 * @package Unilend\librairies
 */
class CacheKeys
{
    const VERY_SHORT_TIME = 60;
    const SHORT_TIME      = 300;
    const MEDIUM_TIME     = 1800;
    const LONG_TIME       = 3600;
    const HALF_DAY        = 43200;
    const DAY             = 86400;

    /**
     * constant for list and count projects
     */
    const LIST_PROJECTS               = 'List_Counter_Projects';
    const AVG_RATE_PROJECTS           = 'projects_getAvgRate';
    const BID_ACCEPTATION_POSSIBILITY = 'bids_getAcceptationPossibilityRounded';

    /**
     * constant for product
     */
    const PRODUCT_ATTRIBUTE_BY_TYPE = 'product_attribute_by_type';

    /**
     * constant for underlying contract
     */
    const CONTRACT_ATTRIBUTE_BY_TYPE = 'contract_attribute_by_type';

    /**
     * constant for CMS elements
     */
    const FOOTER_MENU = 'footer_menu';

    /**
     * constant for project rate range
     */
    const PROJECT_RATE_RANGE = 'project_rate_range';

    /**
     * constant for statistics
     */
    const UNILEND_STATISTICS            = 'unilend_front_statistics';
    const LENDER_STAT_QUEUE_UPDATED     = 'lender_stat_queue_updated';
    const UNILEND_PERFORMANCE_INDICATOR = 'unilend_fpf_statistic';
    const UNILEND_INCIDENCE_RATE        = 'unilend_incidence_rate';

    /**
     * constant for IFU
     */
    const IFU_WALLETS = 'ifu_wallets';

    /**
     * const for API keys
     */
    const EULER_HERMES_MONITORING_API_KEY = 'euler_hermes_monitoring_api_key';

}
