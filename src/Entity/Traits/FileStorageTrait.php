<?php

declare(strict_types=1);

namespace Unilend\Entity\Traits;

trait FileStorageTrait
{
    /**
     * @var string
     *
     * @ORM\Column(type="string", length=191, nullable=true)
     */
    private $relativeFilePath;

    /**
     * @param string|null $relativeFilePath
     *
     * @return self
     */
    public function setRelativeFilePath(?string $relativeFilePath): self
    {
        $this->relativeFilePath = $relativeFilePath;

        return $this;
    }

    /**
     * @return string
     */
    public function getRelativeFilePath(): ?string
    {
        return $this->relativeFilePath;
    }
}
