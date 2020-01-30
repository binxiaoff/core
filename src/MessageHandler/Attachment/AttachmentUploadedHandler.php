<?php

declare(strict_types=1);

namespace Unilend\MessageHandler\Attachment;

use Symfony\Component\Messenger\Handler\MessageHandlerInterface;
use Twig\Error\{LoaderError, RuntimeError, SyntaxError};
use Unilend\Message\Attachment\AttachmentUploaded;
use Unilend\Repository\AttachmentRepository;
use Unilend\Service\{Attachment\AttachmentNotifier};

class AttachmentUploadedHandler implements MessageHandlerInterface
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
     * @param AttachmentUploaded $attachmentUploaded
     *
     * @throws LoaderError
     * @throws RuntimeError
     * @throws SyntaxError
     */
    public function __invoke(AttachmentUploaded $attachmentUploaded)
    {
        $attachment = $this->attachmentRepository->find($attachmentUploaded->getAttachmentId());

        if ($attachment) {
            $this->attachmentNotifier->notifyUploaded($attachment);
        }
    }
}
