<?php

declare(strict_types=1);

namespace Unilend\Core\Entity;

use DateTimeImmutable;
use Doctrine\ORM\Mapping as ORM;
use Exception;
use Unilend\Core\Entity\MarketSegment;
use Unilend\Core\Entity\Staff;
use Unilend\Core\Entity\Traits\BlamableAddedTrait;

/**
 * @ORM\Entity
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
     * @var array
     *
     * @ORM\Column(type="json")
     */
    private $previousRoles;

    /**
     * @var array
     *
     * @ORM\Column(type="json")
     */
    private $previousMarketSegment;

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

        $this->setPreviousRoles($staff->getRoles());
        $this->setPreviousMarketSegment($staff->getMarketSegments());
    }

    /**
     * @param array $previousRoles
     *
     * @return StaffLog
     */
    private function setPreviousRoles(array $previousRoles): StaffLog
    {
        $this->previousRoles = $previousRoles;

        return $this;
    }

    /**
     * @param array $previousMarketSegment
     *
     * @return StaffLog
     */
    private function setPreviousMarketSegment(iterable $previousMarketSegment): StaffLog
    {
        $this->previousMarketSegment = [];

        foreach ($previousMarketSegment as $marketSegment) {
            if ($marketSegment instanceof MarketSegment) {
                $marketSegment = $marketSegment->getLabel();
            }
            $this->previousMarketSegment[] = $marketSegment;
        }

        return $this;
    }
}
