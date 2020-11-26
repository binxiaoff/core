<?php

declare(strict_types=1);

namespace Unilend\Message\CompanyModule;

use Unilend\Core\Entity\CompanyModule;
use Unilend\Message\AsyncMessageInterface;

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
