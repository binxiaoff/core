<?php

declare(strict_types=1);

namespace Unilend\Entity\Traits;

use Doctrine\ORM\Mapping as ORM;
use Unilend\Entity\Clients;
use Unilend\Service\User\RealUserFinder;

trait BlamableArchivedTrait
{
    /**
     * @var Clients|null
     *
     * @ORM\ManyToOne(targetEntity="Unilend\Entity\Clients")
     * @ORM\JoinColumn(name="archived_by", referencedColumnName="id")
     */
    private $archivedBy;

    /**
     * @return Clients|null
     */
    public function getArchivedBy(): ?Clients
    {
        return $this->archivedBy;
    }

    /**
     * @param callable|RealUserFinder $realUserFinder
     *
     * @return self
     */
    public function setArchivedByValue(callable $realUserFinder): self
    {
        return $this->setArchivedBy($realUserFinder());
    }

    /**
     * @param Clients|null $archivedBy
     *
     * @return self
     */
    private function setArchivedBy(?Clients $archivedBy): self
    {
        $this->archivedBy = $archivedBy;

        return $this;
    }
}
