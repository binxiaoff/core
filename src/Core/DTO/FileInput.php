<?php

declare(strict_types=1);

namespace KLS\Core\DTO;

use KLS\Core\Entity\Message;
use KLS\Syndication\Agency\Entity\Term;
use KLS\Syndication\Arrangement\Entity\Project;
use KLS\Syndication\Arrangement\Entity\ProjectFile;
use KLS\Syndication\Arrangement\Entity\ProjectParticipation;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\Validator\Context\ExecutionContextInterface;

class FileInput
{
    public const ACCEPTED_MEDIA_TYPE = [
        'application/zip',
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

    /**
     * @Assert\Choice(callback="getFileTypes")
     */
    public string $type;

    public function __construct(UploadedFile $uploadedFile, string $type, object $targetEntity)
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
        return \array_merge(...\array_values(static::getFileTypesEntityMapping()));
    }

    /**
     * @Assert\Callback
     *
     * @param mixed $payload
     */
    public function validateTargetEntity(ExecutionContextInterface $context, $payload)
    {
        $fileTypesClassMapping = static::getFileTypesEntityMapping();

        $targetEntityClass = \get_class($this->targetEntity);

        if (
            \is_string($targetEntityClass) && false === \in_array($this->type, $fileTypesClassMapping[$targetEntityClass] ?? [], true)
        ) {
            $context->buildViolation('Upload.targetEntity.incorrect')
                ->atPath('targetEntity')
                ->setParameters([
                    'targetEntityClass' => $targetEntityClass,
                    'type'              => $this->type,
                ])
                ->addViolation()
            ;
        }
    }

    /**
     * @return array|array[]
     */
    private static function getFileTypesEntityMapping(): array
    {
        return [
            Project::class              => \array_merge(Project::getProjectFileTypes(), ProjectFile::getProjectFileTypes()),
            ProjectParticipation::class => ProjectParticipation::getFileTypes(),
            Message::class              => Message::getFileTypes(),
            Term::class                 => Term::getFileTypes(),
        ];
    }
}
