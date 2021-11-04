<?php

declare(strict_types=1);

namespace KLS\Core\Message\Company;

use KLS\Core\Entity\Company;
use KLS\Core\Entity\CompanyStatus;
use KLS\Core\Message\AsyncMessageInterface;

class CompanyStatusUpdated implements AsyncMessageInterface
{
    private int $companyId;
    private int $previousStatus;
    private int $nextStatus;

    public function __construct(Company $company, CompanyStatus $previousStatus, CompanyStatus $nextStatus)
    {
        $this->companyId      = $company->getId();
        $this->previousStatus = $previousStatus->getStatus();
        $this->nextStatus     = $nextStatus->getStatus();
    }

    public function getCompanyId(): int
    {
        return $this->companyId;
    }

    public function getPreviousStatus(): int
    {
        return $this->previousStatus;
    }

    public function getNextStatus(): int
    {
        return $this->nextStatus;
    }
}
