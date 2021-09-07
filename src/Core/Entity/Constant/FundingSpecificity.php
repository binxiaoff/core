<?php

declare(strict_types=1);

namespace KLS\Core\Entity\Constant;

final class FundingSpecificity extends AbstractEnum
{
    public const FSA                 = 'FSA';
    public const LBO                 = 'LBO';
    public const PPR                 = 'PPR';
    public const CORPORATE_FINANCING = 'corporate_financing';
    public const PROJECT_FINANCING   = 'project_financing';
    public const PROPERTY_LEASE      = 'property_lease'; // Crédit-bail immobilier
    public const TOP_BALANCE_SHEET   = 'top_balance_sheet'; // Haut de bilan
    public const EQUITY              = 'equity';
}
