<?php

declare(strict_types=1);

namespace KLS\Core\Entity;

use DateTimeImmutable;
use Doctrine\ORM\Mapping as ORM;
use Exception;
use KLS\Core\Entity\Interfaces\StatusInterface;
use KLS\Core\Entity\Interfaces\TraceableStatusAwareInterface;
use KLS\Core\Entity\Traits\PublicizeIdentityTrait;
use KLS\Core\Entity\Traits\TimestampableAddedOnlyTrait;
use KLS\Core\Traits\ConstantsAwareTrait;
use Symfony\Component\Serializer\Annotation\Groups;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * @ORM\Entity
 * @ORM\Table(name="core_company_status")
 *
 * @Assert\Callback(
 *     callback={"KLS\Core\Validator\Constraints\TraceableStatusValidator", "validate"},
 *     payload={ "path": "status" }
 * )
 */
class CompanyStatus implements StatusInterface
{
    use ConstantsAwareTrait;
    use TimestampableAddedOnlyTrait;
    use PublicizeIdentityTrait;

    public const STATUS_REFUSED  = -10;
    public const STATUS_PROSPECT = 0;
    public const STATUS_SIGNED   = 10;

    /**
     * @ORM\ManyToOne(targetEntity="KLS\Core\Entity\Company", inversedBy="statuses")
     * @ORM\JoinColumn(name="id_company", nullable=false)
     *
     * @Groups({"companyStatus:create"})
     */
    private Company $company;

    /**
     * @ORM\Column(type="smallint")
     *
     * @Groups({"companyStatus:read", "companyStatus:create"})
     */
    private int $status;

    /**
     * @throws Exception
     */
    public function __construct(Company $company, int $status)
    {
        $this->status  = $status;
        $this->company = $company;
        $this->added   = new DateTimeImmutable();
    }

    public function getCompany(): Company
    {
        return $this->company;
    }

    public function getStatus(): int
    {
        return $this->status;
    }

    public static function getPossibleStatuses(): array
    {
        return static::getConstants('STATUS_');
    }

    /**
     * @return Company|TraceableStatusAwareInterface
     */
    public function getAttachedObject()
    {
        return $this->getCompany();
    }
}
