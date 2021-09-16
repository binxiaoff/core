<?php

declare(strict_types=1);

namespace KLS\Core\DTO;

use KLS\Core\Entity\Interfaces\FileTypesAwareInterface;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\Validator\Context\ExecutionContextInterface;

class FileInput
{
    public const ACCEPTED_MEDIA_TYPE = [
        'application/zip',
        'application/x-zip',
        'application/x-zip-compressed',
        'application/x-compress',
        'application/x-compressed',
        'multipart/x-zip',
        'application/pdf',
        'application/vnd.ms-excel',
        'application/vnd.ms-excel.sheet.macroenabled.12', // .xlsm
        'application/encrypted', // Allow password protected file
        'application/vnd.ms-powerpoint',
        'application/msword',
        'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        'application/vnd.openxmlformats-officedocument.presentationml.presentation',
        'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
        'application/vnd.oasis.opendocument.spreadsheet',
        'application/vnd.oasis.opendocument.presentation',
        'application/vnd.oasis.opendocument.text',
        'image/jpeg',
        'image/png',
        'text/csv',
        'text/plain',
    ];

    /**
     * @Assert\File(maxSize="250Mi", mimeTypes=KLS\Core\DTO\FileInput::ACCEPTED_MEDIA_TYPE)
     */
    public UploadedFile $uploadedFile;

    /**
     * @Assert\NotBlank
     */
    public object $targetEntity;

    public string $type;

    public function __construct(UploadedFile $uploadedFile, string $type, object $targetEntity)
    {
        $this->uploadedFile = $uploadedFile;
        $this->type         = $type;
        $this->targetEntity = $targetEntity;
    }

    /**
     * @Assert\Callback
     *
     * @param mixed $payload
     */
    public function validateTargetEntityAndTypeMapping(ExecutionContextInterface $context, $payload): void
    {
        if (
            $this->targetEntity instanceof FileTypesAwareInterface
            && false === \in_array($this->type, $this->targetEntity::getFileTypes(), true)
        ) {
            $context->buildViolation('Core.Upload.fileInput.type.not_matching')
                ->setParameter('%type%', $this->type)
                ->addViolation()
            ;
        }
    }
}
