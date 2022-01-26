<?php

declare(strict_types=1);

namespace KLS\Test\CreditGuaranty\FEI\Unit\Traits;

use Exception;
use KLS\Core\Entity\CompanyGroup;
use KLS\Core\Entity\CompanyGroupTag;
use KLS\Core\Entity\Embeddable\Money;
use KLS\CreditGuaranty\FEI\Entity\Program;
use KLS\Test\Core\Unit\Traits\UserStaffTrait;

trait ProgramTrait
{
    use UserStaffTrait;

    /**
     * @throws Exception
     */
    protected function createProgram(): Program
    {
        return new Program(
            'Program',
            new CompanyGroupTag(new CompanyGroup('Company Group'), 'code'),
            new Money('EUR', '42'),
            $this->createStaff()
        );
    }
}
