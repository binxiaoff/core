<?php

declare(strict_types=1);

namespace Unilend\Core\Message\CompanyModule;

use Unilend\Core\Entity\CompanyModule;
use Unilend\Core\Message\AsyncMessageInterface;

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
