<?php

declare(strict_types=1);

namespace Unilend\Entity\Traits;

use Doctrine\ORM\Mapping as ORM;
use Unilend\Entity\Clients;
use Unilend\Service\User\RealUserFinder;

trait BlamableUpdatedTrait
{
    /**
     * @var Clients|null
     *
     * @ORM\ManyToOne(targetEntity="Unilend\Entity\Clients")
     * @ORM\JoinColumn(name="updated_by", referencedColumnName="id_client")
     */
    private $updatedBy;

    /**
     * @return Clients|null
     */
    public function getUpdatedBy(): ?Clients
    {
        return $this->updatedBy;
    }

    /**
     * @param callable|RealUserFinder $realUserFinder
     *
     * @return self
     */
    public function setUpdatedByValue(callable $realUserFinder): self
    {
        return $this->setUpdatedBy($realUserFinder());
    }

    /**
     * @param Clients|null $updatedBy
     *
     * @return self
     */
    public function setUpdatedBy(?Clients $updatedBy): self
    {
        $this->updatedBy = $updatedBy;

        return $this;
    }
}
