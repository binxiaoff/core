<?php

declare(strict_types=1);

namespace KLS\Core\Entity;

use DateTimeImmutable;
use Doctrine\ORM\Mapping as ORM;
use Exception;
use KLS\Core\Entity\Traits\BlamableAddedTrait;
use KLS\Core\Entity\Traits\TimestampableAddedOnlyTrait;

/**
 * @ORM\Entity
 * @ORM\Table(name="core_company_module_log")
 */
class CompanyModuleLog
{
    use TimestampableAddedOnlyTrait;
    use BlamableAddedTrait;

    /**
     * @ORM\ManyToOne(targetEntity="KLS\Core\Entity\CompanyModule", inversedBy="logs")
     * @ORM\JoinColumn(nullable=false, name="id_module")
     */
    private CompanyModule $companyModule;

    /**
     * @ORM\Column(type="boolean")
     */
    private bool $activated;

    /**
     * @ORM\Id
     * @ORM\GeneratedValue
     * @ORM\Column(type="integer")
     */
    private int $id;

    /**
     * @throws Exception
     */
    public function __construct(CompanyModule $module)
    {
        $this->companyModule = $module;
        $this->addedBy       = $module->getUpdatedBy();
        $this->activated     = $module->isActivated();
        $this->added         = new DateTimeImmutable();
    }

    public function getId(): int
    {
        return $this->id;
    }

    public function isActivated(): bool
    {
        return $this->activated;
    }

    public function getCompanyModule(): CompanyModule
    {
        return $this->companyModule;
    }
}
