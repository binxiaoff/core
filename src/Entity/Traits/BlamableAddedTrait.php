<?php

declare(strict_types=1);

namespace Unilend\Entity\Traits;

use Doctrine\ORM\Mapping as ORM;
use Gedmo\Mapping\Annotation as Gedmo;
use Symfony\Component\Serializer\Annotation\Groups;
use Unilend\Entity\Staff;

trait BlamableAddedTrait
{
    /**
     * @var Staff
     *
     * @ORM\ManyToOne(targetEntity="Unilend\Entity\Staff")
     * @ORM\JoinColumn(name="added_by", referencedColumnName="id", nullable=false)
     *
     * @Groups({"blameable:read"})
     *
     * @Gedmo\Blameable(on="create")
     */
    private $addedBy;

    /**
     * @return Staff
     */
    public function getAddedBy(): Staff
    {
        return $this->addedBy;
    }
}
