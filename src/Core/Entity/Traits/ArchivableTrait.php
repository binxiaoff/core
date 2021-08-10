<?php

declare(strict_types=1);

namespace KLS\Core\Entity\Traits;

use DateTime;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Annotation\Groups;

trait ArchivableTrait
{
    /**
     * SoftDeletable don't support datetime immutable.
     *
     * @ORM\Column(type="datetime", nullable=true)
     *
     * @Groups({"archivable:read", "archivable:write"})
     */
    private $archived;

    /**
     * @param DateTime $archived
     */
    public function setArchived(?DateTime $archived): self
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
