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
    const PRODUCT_ATTRIBUTE_BY_TYPE          = 'product_attribute_by_type';
    const PRODUCT_CONTRACT_ATTRIBUTE_BY_TYPE = 'product_contract_attribute_by_type';

    /**
     * constant for underlying contract
     */
    const CONTRACT_ATTRIBUTE_BY_TYPE = 'contract_attribute_by_type';

    /**
     * constant for statistics
     */
    const UNILEND_STATISTICS = 'unilend_front_statistics';

}
