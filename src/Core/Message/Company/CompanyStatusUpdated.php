<?php

declare(strict_types=1);

namespace KLS\Core\Message\Company;

use KLS\Core\Entity\Company;
use KLS\Core\Entity\CompanyStatus;
use KLS\Core\Message\AsyncMessageInterface;

class CompanyStatusUpdated implements AsyncMessageInterface
{
    private Company $company;
    private CompanyStatus $previousStatus;
    private CompanyStatus $nextStatus;

    public function __construct(Company $company, CompanyStatus $previousStatus, CompanyStatus $nextStatus)
    {
        $this->company        = $company;
        $this->previousStatus = $previousStatus;
        $this->nextStatus     = $nextStatus;
    }

    public function getCompanyId(): int
    {
        return $this->company->getId();
    }

    public function getPreviousStatus(): int
    {
        return $this->previousStatus->getStatus();
    }

    public function getNextStatus(): int
    {
        return $this->nextStatus->getStatus();
    }
}
