<?php

namespace Unilend\librairies;

/**
 * Class Cache
 * @package Unilend\librairies
 */
class CacheKeys extends \Memcache
{
    const SHORT_TIME  = 300;
    const MEDIUM_TIME = 1800;
    const LONG_TIME   = 3600;

    /**
     * constant for list and count projects
     */
    const LIST_PROJECTS = 'List_Counter_Projects';
    const AVG_RATE_PROJECTS = 'projects_getAvgRate';
    const BID_ACCEPTATION_POSSIBILITY = 'bids_getAcceptationPossibilityRounded';
}
