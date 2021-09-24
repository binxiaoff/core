<?php

declare(strict_types=1);

namespace KLS\Test\Syndication\Agency\Unit\Traits;

use KLS\Core\Entity\Embeddable\Money;
use KLS\Core\Entity\Staff;
use KLS\Syndication\Agency\Entity\Project;

trait AgencyProjectTrait
{
    private function createAgencyProject(Staff $staff): Project
    {
        return new Project(
            $staff,
            'Agency Project',
            'risk1',
            new Money('EUR', '42'),
            new \DateTimeImmutable(),
            new \DateTimeImmutable(),
        );
    }
}
