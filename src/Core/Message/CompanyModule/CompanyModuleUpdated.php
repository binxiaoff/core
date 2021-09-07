<?php

declare(strict_types=1);

namespace KLS\Core\Message\CompanyModule;

use KLS\Core\Entity\CompanyModule;
use KLS\Core\Message\AsyncMessageInterface;

class CompanyModuleUpdated implements AsyncMessageInterface
{
    private ?int $companyModuleId;

    public function __construct(CompanyModule $companyModule)
    {
        $this->companyModuleId = $companyModule->getId();
    }

    public function getCompanyModuleId(): ?int
    {
        return $this->companyModuleId;
    }
}
