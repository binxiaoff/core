<?php

declare(strict_types=1);

namespace Unilend\Entity\Traits;

use DateTime;
use Symfony\Component\Serializer\Annotation\Groups;

trait ArchivableTrait
{
    /**
     * @var DateTime
     *
     * @ORM\Column(type="datetime", nullable=true)
     *
     * @Groups({"archived:read", "archived:write"})
     */
    private $archived;

    /**
     * @param DateTime $archived
     *
     * @return self
     */
    public function setArchived(DateTime $archived): self
    {
        $this->archived = $archived;

        return $this;
    }

    /**
     * @return DateTime
     */
    public function getArchived(): ?DateTime
    {
        return $this->archived;
    }
}
