<?php

declare(strict_types=1);

namespace Unilend\Entity\Interfaces;

use Unilend\Entity\AttachmentType;

interface EntityAttachmentTypeInterface
{
    /**
     * @return EntityAttachmentTypeCategoryInterface|null
     */
    public function getCategory(): ?EntityAttachmentTypeCategoryInterface;

    /**
     * @return AttachmentType|null
     */
    public function getAttachmentType(): ?AttachmentType;
}
