<?php

declare(strict_types=1);

namespace Unilend\Core\Message\CompanyModule;

use Unilend\Core\Entity\CompanyModule;
use Unilend\Core\Message\AsyncMessageInterface;

class CompanyModuleUpdated implements AsyncMessageInterface
{
    /** @var int|null  */
    private ?int $companyModuleId;

    /**
     * @param CompanyModule $companyModule
     */
    public function __construct(CompanyModule $companyModule)
    {
        $this->companyModuleId = $companyModule->getId();
    }

    /**
     * @return int|null
     */
    public function getCompanyModuleId(): ?int
    {
        return $this->companyModuleId;
    }
}
