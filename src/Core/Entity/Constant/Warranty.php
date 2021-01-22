<?php

declare(strict_types=1);

namespace Unilend\Core\Entity\Constant;

class Warranty extends AbstractEnum
{
    public const SOLIDARITY = 'solidarity'; // Caution solidaire
    public const FIRST_REQUEST = 'first_request_collateral'; // Garantie à la première requete
    public const BPI = 'bpi'; // Caution BPI
    public const DAILY = 'daily_cession'; // Cession daily
    public const RENT_DELEGATION = 'rent_delegation'; // Délégation de loyer
    public const INSURANCE_DELEGATION = 'insurance_delegation'; // Délégation d'assurance
    public const MORTGAGE = 'mortgage'; // Hypotèque
    public const TITLE = 'share_pledge'; // Nantissement de titre
    public const MATERIAL = 'material_pledge'; // Nantissement materiel
    public const WARRANTY = 'warranty'; // Garantie
    public const OTHER = 'other'; // Autre
}
