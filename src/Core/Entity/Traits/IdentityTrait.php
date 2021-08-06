<?php

declare(strict_types=1);

namespace Unilend\Core\Entity\Traits;

use Doctrine\ORM\Mapping as ORM;

trait IdentityTrait
{
    /**
     * @ORM\Column(type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="IDENTITY")
     */
    private ?int $id;

    public function getId(): ?int
    {
        return $this->id;
    }
}
