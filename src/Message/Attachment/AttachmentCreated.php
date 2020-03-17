<?php

declare(strict_types=1);

namespace Unilend\Message\Attachment;

use Unilend\Entity\FileVersion;

class AttachmentCreated
{
    /** @var int */
    private $attachmentId;

    /**
     * @param FileVersion $attachment
     */
    public function __construct(FileVersion $attachment)
    {
        $this->attachmentId = $attachment->getId();
    }

    /**
     * @return int
     */
    public function getAttachmentId(): int
    {
        return $this->attachmentId;
    }
}
