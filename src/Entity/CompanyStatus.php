<?php

declare(strict_types=1);

namespace Unilend\Entity;

use DateTimeImmutable;
use Doctrine\ORM\Mapping as ORM;
use Exception;
use Symfony\Component\Serializer\Annotation\Groups;
use Symfony\Component\Validator\Constraints as Assert;
use Unilend\Entity\{Interfaces\StatusInterface, Interfaces\TraceableStatusAwareInterface, Traits\PublicizeIdentityTrait, Traits\TimestampableAddedOnlyTrait};
use Unilend\Traits\ConstantsAwareTrait;

/**
 * @ORM\Entity
 *
 * @Assert\Callback(
 *     callback={"Unilend\Validator\Constraints\TraceableStatusValidator", "validate"},
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
     * @var Company
     *
     * @ORM\ManyToOne(targetEntity="Unilend\Entity\Company", inversedBy="statuses")
     * @ORM\JoinColumn(name="id_company", nullable=false)
     *
     * @Groups({"companyStatus:create"})
     */
    private Company $company;

    /**
     * @var int
     *
     * @ORM\Column(type="smallint")
     *
     * @Groups({"companyStatus:read", "companyStatus:create"})
     */
    private int $status;

    /**
     * @param Company $company
     * @param int     $status
     *
     * @throws Exception
     */
    public function __construct(Company $company, int $status)
    {
        $this->status  = $status;
        $this->company = $company;
        $this->added   = new DateTimeImmutable();
    }

    /**
     * @return Company
     */
    public function getCompany(): Company
    {
        return $this->company;
    }

    /**
     * @return int
     */
    public function getStatus(): int
    {
        return $this->status;
    }

    /**
     * @return array
     */
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
