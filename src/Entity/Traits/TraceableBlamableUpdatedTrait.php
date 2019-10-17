<?php

declare(strict_types=1);

namespace Unilend\Entity\Traits;

use Doctrine\ORM\Mapping as ORM;
use Gedmo\Mapping\Annotation as Gedmo;
use Unilend\Entity\Clients;

trait TraceableBlamableUpdatedTrait
{
    use BlamableUpdatedTrait;

    /**
     * @var Clients|null
     *
     * @Gedmo\Versioned
     *
     * @ORM\ManyToOne(targetEntity="Unilend\Entity\Clients")
     * @ORM\JoinColumn(name="updated_by", referencedColumnName="id_client")
     */
    private $updatedBy;
}
