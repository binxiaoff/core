<?php

declare(strict_types=1);

namespace Unilend\Entity;

use DateTimeImmutable;
use Doctrine\ORM\Mapping as ORM;
use Exception;
use InvalidArgumentException;
use Unilend\Entity\Interfaces\StatusInterface;
use Unilend\Entity\Interfaces\TraceableStatusAwareInterface;
use Unilend\Entity\Traits\TimestampableAddedOnlyTrait;
use Unilend\Traits\ConstantsAwareTrait;

/**
 * @ORM\Table(name="company_status")
 * @ORM\Entity
 */
class CompanyStatus implements StatusInterface
{
    use ConstantsAwareTrait;
    use TimestampableAddedOnlyTrait;

    public const STATUS_REFUSED  = -10;
    public const STATUS_PROSPECT = 0;
    public const STATUS_SIGNED   = 10;

    /**
     * @var Company
     *
     * @ORM\ManyToOne(targetEntity="Unilend\Entity\Company", inversedBy="statuses")
     * @ORM\JoinColumn(name="id_company", nullable=false)
     */
    private $company;

    /**
     * @var int
     *
     * @ORM\Column(type="smallint")
     */
    private $status;

    /**
     * @var int
     *
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="IDENTITY")
     * @ORM\Column(type="integer")
     */
    private $id;

    /**
     * @param Company $company
     * @param int     $status
     *
     * @throws Exception
     */
    public function __construct(Company $company, int $status)
    {
        if (!\in_array($status, static::getPossibleStatuses(), true)) {
            throw new InvalidArgumentException(
                sprintf('%s is not a possible status for %s', $status, __CLASS__)
            );
        }
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
     * @return int|null
     */
    public function getId(): ?int
    {
        return $this->id;
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

    /**
     * {@inheritdoc}
     */
    public function getDefinitiveStatuses(): array
    {
        return [];
    }
}
