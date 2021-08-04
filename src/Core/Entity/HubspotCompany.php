<?php

declare(strict_types=1);

namespace Unilend\Core\Entity;

use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;
use Unilend\Core\Entity\Traits\TimestampableTrait;

/**
 * @ORM\Entity
 *
 * @ORM\Table(name="core_hubspot_company")
 */
class HubspotCompany
{
    use TimestampableTrait;

    /**
     * @ORM\Column(type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="IDENTITY")
     */
    private int $id;

    /**
     * @ORM\OneToOne(targetEntity=Company::class)
     *
     * @Assert\NotBlank
     */
    private Company $company;

    /**
     * @ORM\Column(type="integer", nullable=false, name="hubspot_company_id")
     *
     * @Assert\Type("integer")
     * @Assert\Positive
     */
    private int $companyId;

    public function __construct(Company $company, int $companyId)
    {
        $this->company   = $company;
        $this->companyId = $companyId;
    }

    public function getId(): int
    {
        return $this->id;
    }

    public function getCompany(): Company
    {
        return $this->company;
    }

    public function setCompany(Company $company): HubspotCompany
    {
        $this->company = $company;

        return $this;
    }

    public function getCompanyId(): int
    {
        return $this->companyId;
    }

    public function setCompanyId(int $companyId): HubspotCompany
    {
        $this->companyId = $companyId;

        return $this;
    }
}
