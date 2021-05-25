<?php

declare(strict_types=1);

namespace Unilend\Core\Entity\Traits;

use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Annotation\Groups;
use Unilend\Core\Entity\Staff;

trait BlamableAddedTrait
{
    /**
     * @var Staff
     *
     * @ORM\ManyToOne(targetEntity="Unilend\Core\Entity\Staff", cascade={"persist"})
     * @ORM\JoinColumn(name="added_by", referencedColumnName="id", nullable=false)
     *
     * @Groups({"blameable:read"})
     */
    private $addedBy;

    public function getAddedBy(): Staff
    {
        return $this->addedBy;
    }
}
