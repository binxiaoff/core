<?php

declare(strict_types=1);

namespace Unilend\Message\Attachment;

use Unilend\Entity\Attachment;

class AttachmentCreated
{
    /** @var int */
    private $attachmentId;

    /**
     * @param Attachment $attachment
     */
    public function __construct(Attachment $attachment)
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
