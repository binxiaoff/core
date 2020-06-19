<?php

declare(strict_types=1);

namespace Unilend\Entity\Traits;

use DateTimeImmutable;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Annotation\Groups;

trait ArchivableTrait
{
    /**
     * @var DateTimeImmutable|null
     *
     * @ORM\Column(type="datetime_immutable", nullable=true)
     *
     * @Groups({"archivable:read", "archivable:write"})
     */
    private $archived;

    /**
     * @param DateTimeImmutable $archived
     *
     * @return self
     */
    public function setArchived(?DateTimeImmutable $archived): self
    {
        $this->archived = $archived;

        return $this;
    }

    /**
     * @return DateTimeImmutable
     */
    public function getArchived(): ?DateTimeImmutable
    {
        return $this->archived;
    }
}
