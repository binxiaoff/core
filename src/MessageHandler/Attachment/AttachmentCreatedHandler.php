<?php

declare(strict_types=1);

namespace Unilend\MessageHandler\Attachment;

use InvalidArgumentException;
use Symfony\Component\Messenger\Handler\MessageHandlerInterface;
use Twig\Error\{LoaderError, RuntimeError, SyntaxError};
use Unilend\Message\Attachment\AttachmentCreated;
use Unilend\Repository\FileVersionRepository;
use Unilend\Service\{Attachment\AttachmentNotifier};

class AttachmentCreatedHandler implements MessageHandlerInterface
{
    /** @var FileVersionRepository */
    private $attachmentRepository;
    /** @var AttachmentNotifier */
    private $attachmentNotifier;

    /**
     * @param FileVersionRepository $attachmentRepository
     * @param AttachmentNotifier    $attachmentNotifier
     */
    public function __construct(FileVersionRepository $attachmentRepository, AttachmentNotifier $attachmentNotifier)
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
        $attachmentId = $attachmentCreated->getAttachmentId();
        $attachment   = $this->attachmentRepository->find($attachmentId);

        if (null === $attachment) {
            throw new InvalidArgumentException(sprintf('The attachment with id %d does not exist', $attachmentId));
        }

        $this->attachmentNotifier->notifyUploaded($attachment);
    }
}
