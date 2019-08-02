<?php

namespace Unilend;

/**
 * Class Cache.
 */
class CacheKeys
{
    public const VERY_SHORT_TIME = 60;
    public const SHORT_TIME      = 300;
    public const MEDIUM_TIME     = 1800;
    public const LONG_TIME       = 3600;
    public const HALF_DAY        = 43200;
    public const DAY             = 86400;

    /**
     * constant for list of projects.
     */
    public const LIST_PROJECTS = 'List_Projects';

    /**
     * constant for product.
     */
    public const PRODUCT_ATTRIBUTE_BY_TYPE = 'product_attribute_by_type';

    /**
     * constant for underlying contract.
     */
    public const CONTRACT_ATTRIBUTE_BY_TYPE = 'contract_attribute_by_type';
}
