<?php

declare(strict_types=1);

namespace KLS\Core\Entity\Traits;

use Doctrine\ORM\Mapping as ORM;
use KLS\Core\Entity\Staff;
use Symfony\Component\Serializer\Annotation\Groups;

trait BlamableAddedTrait
{
    /**
     * @var Staff
     *
     * @ORM\ManyToOne(targetEntity="KLS\Core\Entity\Staff")
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
