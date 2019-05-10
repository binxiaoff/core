<?php

declare(strict_types=1);

namespace Unilend\Entity\Interfaces;

interface EntityAttachmentTypeCategoryInterface
{
    /**
     * @return string|null
     */
    public function getLabel(): ?string;

    /**
     * @return int|null
     */
    public function getId(): ?int;
}
