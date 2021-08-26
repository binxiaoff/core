<?php

declare(strict_types=1);

namespace KLS\Test\Syndication\Agency\Unit\Traits;

use DateTimeImmutable;
use KLS\Core\Entity\Staff;
use KLS\Syndication\Agency\Entity\Covenant;
use KLS\Syndication\Agency\Entity\Term;

trait TermTrait
{
    use AgencyProjectTrait;

    private function createTerm(Staff $staff): Term
    {
        return new Term(
            new Covenant(
                $this->createAgencyProject($staff),
                'Covenant',
                Covenant::NATURE_CONTROL,
                new DateTimeImmutable('- 2 years'),
                40,
                new DateTimeImmutable('+ 3 years')
            ),
            new DateTimeImmutable('- 1 years'),
            new DateTimeImmutable('- 2 years')
        );
    }
}
