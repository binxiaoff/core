<?php

declare(strict_types=1);

namespace Unilend\Entity;

use DateTimeImmutable;
use Doctrine\ORM\Mapping as ORM;
use Exception;
use Unilend\Entity\Traits\{BlamableAddedTrait, TimestampableAddedOnlyTrait};

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
     * @ORM\ManyToOne(targetEntity="Unilend\Entity\CompanyModule")
     * @ORM\JoinColumn(nullable=false, name="id_module")
     */
    private $companyModule;

    /**
     * @var bool
     *
     * @ORM\Column(type="boolean")
     */
    private $activated;

    /**
     * @var int
     *
     * @ORM\Id
     * @ORM\GeneratedValue
     * @ORM\Column(type="integer")
     */
    private $id;

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
}
