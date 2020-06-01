<?php

declare(strict_types=1);

namespace Unilend\DTO;

use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\Validator\Constraints as Assert;
use Unilend\Entity\Project;
use Unilend\Entity\ProjectFile;

class FileInput
{
    public const ACCEPTED_MEDIA_TYPE = [
        'application/pdf',
        'application/vnd.ms-excel',
        'application/vnd.ms-powerpoint',
        'application/msword',
        'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        'application/vnd.openxmlformats-officedocument.presentationml.presentation',
        'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
        'application/x-iwork-numbers-sffnumbers',
        'application/x-iwork-keynote-sffkey',
        'application/x-iwork-pages-sffpages',
        'application/vnd.oasis.opendocument.spreadsheet',
        'application/vnd.oasis.opendocument.presentation',
        'application/vnd.oasis.opendocument.text',
        'image/jpeg',
        'image/png',
        'text/csv',
        'text/plain',
    ];

    /**
     * @var UploadedFile
     *
     * @Assert\File(maxSize="250Mi", mimeTypes=Unilend\DTO\FileInput::ACCEPTED_MEDIA_TYPE)
     */
    public $uploadedFile;

    /**
     * @var string
     *
     * @Assert\NotBlank
     */
    public $targetEntity;

    /**
     * @var string
     *
     * @Assert\Choice(callback="getProjectFileTypes")
     */
    public $type;

    /**
     * @param UploadedFile $uploadedFile
     * @param string       $type
     * @param string       $targetEntity
     */
    public function __construct(UploadedFile $uploadedFile, string $type, string $targetEntity)
    {
        $this->uploadedFile = $uploadedFile;
        $this->type         = $type;
        $this->targetEntity = $targetEntity;
    }

    /**
     * @return array
     */
    public static function getProjectFileTypes(): array
    {
        return array_merge(Project::getProjectFileTypes(), ProjectFile::getProjectFileTypes());
    }
}
