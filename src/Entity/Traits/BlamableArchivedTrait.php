<?php

declare(strict_types=1);

namespace Unilend\Entity\Traits;

use Doctrine\ORM\Mapping as ORM;
use Unilend\Entity\Clients;

trait BlamableArchivedTrait
{
    /**
     * @var Clients|null
     *
     * @ORM\ManyToOne(targetEntity="Unilend\Entity\Clients")
     * @ORM\JoinColumn(name="archived_by", referencedColumnName="id_client")
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
     * @param Clients|null $archivedBy
     *
     * @return self
     */
    public function setArchivedBy(?Clients $archivedBy): self
    {
        $this->archivedBy = $archivedBy;

        return $this;
    }
}
