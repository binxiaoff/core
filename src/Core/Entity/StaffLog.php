<?php

declare(strict_types=1);

namespace Unilend\Core\Entity;

use DateTimeImmutable;
use Doctrine\ORM\Mapping as ORM;
use Exception;
use Unilend\Core\Entity\Traits\BlamableAddedTrait;

/**
 * @ORM\Entity
 *
 * @ORM\Table(name="core_staff_log")
 */
class StaffLog
{
    use BlamableAddedTrait;

    /**
     * @var int
     *
     * @ORM\Column(type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="IDENTITY")
     */
    private $id;

    /**
     * @ORM\Column(type="integer")
     */
    private $staffId;

    /**
     * @var DateTimeImmutable
     *
     * @ORM\Column(type="datetime_immutable")
     */
    private $added;

    /**
     * @param Staff $staff
     * @param Staff $addedBy
     *
     * @throws Exception
     */
    public function __construct(Staff $staff, Staff $addedBy)
    {
        $this->staffId = $staff->getId();
        $this->added   = new DateTimeImmutable();
        $this->addedBy = $addedBy;
    }
}
