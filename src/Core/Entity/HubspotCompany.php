<?php

declare(strict_types=1);

namespace KLS\Core\Entity;

use DateTimeImmutable;
use Doctrine\ORM\Mapping as ORM;
use KLS\Core\Entity\Traits\TimestampableAddedOnlyTrait;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * @ORM\Entity
 *
 * @ORM\Table(name="core_hubspot_company")
 */
class HubspotCompany
{
    use TimestampableAddedOnlyTrait;

    /**
     * @ORM\Column(type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="IDENTITY")
     */
    private int $id;

    /**
     * @ORM\OneToOne(targetEntity=Company::class)
     * @ORM\JoinColumn(name="id_company")
     *
     * @Assert\NotBlank
     */
    private Company $company;

    /**
     * @ORM\Column(type="string", nullable=false, name="hubspot_company_id")
     *
     * @Assert\Type("string")
     */
    private string $hubspotCompanyId;

    /**
     * @ORM\Column(type="datetime_immutable", nullable=true)
     */
    private \DateTimeImmutable $synchronized;

    public function __construct(Company $company, string $companyId)
    {
        $this->company          = $company;
        $this->hubspotCompanyId = $companyId;
        $this->added            = new DateTimeImmutable();
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

    public function getHubspotCompanyId(): string
    {
        return $this->hubspotCompanyId;
    }

    public function setHubspotCompanyId(string $hubspotCompanyId): HubspotCompany
    {
        $this->hubspotCompanyId = $hubspotCompanyId;

        return $this;
    }

    public function getSynchronized(): DateTimeImmutable
    {
        return $this->synchronized;
    }

    public function synchronize(): void
    {
        $this->synchronized = new DateTimeImmutable();
    }
}
