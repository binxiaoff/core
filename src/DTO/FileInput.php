<?php

declare(strict_types=1);

namespace Unilend\DTO;

use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\Validator\Context\ExecutionContextInterface;
use Unilend\Entity\Project;
use Unilend\Entity\ProjectFile;
use Unilend\Entity\ProjectParticipation;

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
     * @Assert\Choice(callback="getFileTypes")
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
     * @return array|string[]
     */
    public static function getFileTypes(): array
    {
        return array_merge(...array_values(static::getFileTypesEntityMapping()));
    }

    /**
     * @return array|array[]
     */
    public static function getFileTypesEntityMapping(): array
    {
        return [
            Project::class              => array_merge(Project::getProjectFileTypes(), ProjectFile::getProjectFileTypes()),
            ProjectParticipation::class => ProjectParticipation::getFileTypes(),
        ];
    }

    /**
     * @param ExecutionContextInterface $context
     * @param $payload
     */
    public function validateTargetEntity(ExecutionContextInterface $context, $payload)
    {
        $fileTypesClassMapping = static::getFileTypesEntityMapping();

        $targetEntityClass = \get_class($this->targetEntity);

        if (false === \in_array($this->type, $fileTypesClassMapping[$targetEntityClass] ?? [], true)) {
            $context->buildViolation('Upload.targetEntity.incorrect')
                ->setParameters([
                    'targetEntityClass' => $targetEntityClass,
                    'type'              => $this->type,
                ])
                ->addViolation()
            ;
        }
    }
}
