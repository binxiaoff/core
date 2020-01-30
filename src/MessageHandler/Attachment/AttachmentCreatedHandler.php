<?php

declare(strict_types=1);

namespace Unilend\MessageHandler\Attachment;

use Symfony\Component\Messenger\Handler\MessageHandlerInterface;
use Twig\Error\{LoaderError, RuntimeError, SyntaxError};
use Unilend\Message\Attachment\AttachmentCreated;
use Unilend\Repository\AttachmentRepository;
use Unilend\Service\{Attachment\AttachmentNotifier};

class AttachmentCreatedHandler implements MessageHandlerInterface
{
    /** @var AttachmentRepository */
    private $attachmentRepository;
    /** @var AttachmentNotifier */
    private $attachmentNotifier;

    /**
     * @param AttachmentRepository $attachmentRepository
     * @param AttachmentNotifier   $attachmentNotifier
     */
    public function __construct(AttachmentRepository $attachmentRepository, AttachmentNotifier $attachmentNotifier)
    {
        $this->attachmentRepository = $attachmentRepository;
        $this->attachmentNotifier   = $attachmentNotifier;
    }

    /**
     * @param AttachmentCreated $attachmentCreated
     *
     * @throws LoaderError
     * @throws RuntimeError
     * @throws SyntaxError
     */
    public function __invoke(AttachmentCreated $attachmentCreated)
    {
        $attachment = $this->attachmentRepository->find($attachmentCreated->getAttachmentId());

        if ($attachment) {
            $this->attachmentNotifier->notifyUploaded($attachment);
        }
    }
}
