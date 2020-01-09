<?php

declare(strict_types=1);

namespace Unilend\Entity\Traits;

use Doctrine\ORM\Mapping as ORM;
use Gedmo\Mapping\Annotation as Gedmo;
use Symfony\Component\Serializer\Annotation\Groups;
use Unilend\Entity\Clients;
use Unilend\Service\User\RealUserFinder;

trait BlamableAddedTrait
{
    /**
     * @var Clients
     *
     * @ORM\ManyToOne(targetEntity="Unilend\Entity\Clients")
     * @ORM\JoinColumn(name="added_by", referencedColumnName="id_client", nullable=false)
     *
     * @Groups({"blameable:read"})
     *
     * @Gedmo\Blameable(on="create")
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
     * @param callable|RealUserFinder $realUserFinder
     *
     * @return self
     */
    public function setAddedByValue(callable $realUserFinder): self
    {
        return $this->setAddedBy($realUserFinder());
    }

    /**
     * @param Clients $addedBy
     *
     * @return self
     */
    private function setAddedBy(Clients $addedBy): self
    {
        $this->addedBy = $addedBy;

        return $this;
    }
}
