<?php

declare(strict_types=1);

namespace Unilend\Core\Entity;

use DateTimeImmutable;
use Doctrine\ORM\Mapping as ORM;
use Exception;
use Unilend\Core\Entity\CompanyModule;
use Unilend\Core\Entity\Traits\{BlamableAddedTrait, TimestampableAddedOnlyTrait};

/**
 * @ORM\Entity
 */
class CompanyModuleLog
{
    use TimestampableAddedOnlyTrait;
    use BlamableAddedTrait;

    /**
     * @var CompanyModule
     *
     * @ORM\ManyToOne(targetEntity="Unilend\Core\Entity\CompanyModule", inversedBy="logs")
     * @ORM\JoinColumn(nullable=false, name="id_module")
     */
    private CompanyModule $companyModule;

    /**
     * @var bool
     *
     * @ORM\Column(type="boolean")
     */
    private bool $activated;

    /**
     * @var int
     *
     * @ORM\Id
     * @ORM\GeneratedValue
     * @ORM\Column(type="integer")
     */
    private int $id;

    /**
     * @param CompanyModule $module
     *
     * @throws Exception
     */
    public function __construct(CompanyModule $module)
    {
        $this->companyModule = $module;
        $this->addedBy       = $module->getUpdatedBy();
        $this->activated     = $module->isActivated();
        $this->added         = new DateTimeImmutable();
    }

    /**
     * @return int
     */
    public function getId(): int
    {
        return $this->id;
    }

    /**
     * @return bool
     */
    public function isActivated(): bool
    {
        return $this->activated;
    }

    /**
     * @return CompanyModule
     */
    public function getCompanyModule(): CompanyModule
    {
        return $this->companyModule;
    }
}
