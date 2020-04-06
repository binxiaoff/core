<?php

declare(strict_types=1);

namespace Unilend\DTO;

use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\Validator\Constraints as Assert;
use Unilend\Entity\Project;
use Unilend\Entity\ProjectFile;

class FileInput
{
    /**
     * @var UploadedFile
     *
     * @Assert\File
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
     * @Assert\NotBlank
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
