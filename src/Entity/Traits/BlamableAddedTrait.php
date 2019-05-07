<?php

declare(strict_types=1);

namespace Unilend\Entity\Traits;

use Doctrine\ORM\Mapping as ORM;
use Unilend\Entity\Clients;

trait BlamableAddedTrait
{
    /**
     * @var Clients
     *
     * @ORM\ManyToOne(targetEntity="Unilend\Entity\Clients")
     * @ORM\JoinColumn(name="added_by", referencedColumnName="id_client", nullable=false)
     */
    private $addedBy;

    /**
     * @return Clients
     */
    public function getAddedBy(): Clients
    {
        return $this->addedBy;
    }

    /**
     * @param Clients $addedBy
     *
     * @return self
     */
    public function setAddedBy(Clients $addedBy): self
    {
        $this->addedBy = $addedBy;

        return $this;
    }
}
