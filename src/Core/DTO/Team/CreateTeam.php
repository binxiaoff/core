<?php

declare(strict_types=1);

namespace Unilend\Core\DTO\Team;

use Symfony\Component\Serializer\Annotation\Groups;
use Symfony\Component\Validator\Constraints as Assert;
use Unilend\Core\Entity\Team;

class CreateTeam
{
    /**
     * @var string
     *
     * @Assert\NotBlank
     *
     * @Groups({"team:create"})
     */
    public string $name;

    /**
     * @var Team
     *
     * @Assert\NotBlank
     *
     * @Groups({"team:create"})
     */
    public Team $parent;

    /**
     * @param string $name
     * @param Team   $parent
     */
    public function __construct(string $name, Team $parent)
    {
        $this->name = $name;
        $this->parent = $parent;
    }
}
