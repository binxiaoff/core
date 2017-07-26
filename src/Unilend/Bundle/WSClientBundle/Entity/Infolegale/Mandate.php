<?php

namespace Unilend\Bundle\WSClientBundle\Entity\Infolegale;

class Mandate extends ExecutiveDetails
{
    const CHANGE_NOMINATION   = 'Nomination';
    const CHANGE_MODIFICATION = 'Modification';
    const CHANGE_CONFIRMATION = 'Confirmation';
    const CHANGE_REVOCATION   = 'Revocation';
    const CHANGE_RESIGN       = 'Démission';
    const CHANGE_DEAD         = 'Décès';
    const CHANGE_LEFT         = 'Départ';
    const CHANGE_SUPPRESSION  = 'Suppression';
    const CHANGE_UNSPECIFIED  = 'Sans précision';
}
