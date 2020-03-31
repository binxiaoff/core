<?php

declare(strict_types=1);

namespace Unilend\Entity;

use Symfony\Component\Validator\Constraints as Assert;
use Unilend\Traits\ConstantsAwareTrait;

class FileInput
{
    use ConstantsAwareTrait;

    public const PROJECT_TYPE_CONFIDENTIALITY_DISCLAIMER = 'project_confidentiality_disclaimer';
    public const PROJECT_TYPE_DESCRIPTION                = 'project_description';

    public const PROJECT_FILE_TYPES = [
        ProjectFile::TYPE_GENERAL,
        ProjectFile::TYPE_ACCOUNTING_FINANCIAL,
        ProjectFile::TYPE_KYC,
        ProjectFile::TYPE_LEGAL,
    ];

    public const PROJECT_FIELDS_TYPES = [
        self::PROJECT_TYPE_DESCRIPTION,
        self::PROJECT_TYPE_CONFIDENTIALITY_DISCLAIMER,
    ];

    /**
     * Assert its a File.
     */
    public $uploadedFile;

    /**
     * @Assert\NotBlank(groups={"file:patch"})
     * @Assert\NotNull(groups={"file:patch"})
     */
    public $file;

    /**
     * @Assert\NotBlank(groups={"file:post"})
     * @Assert\NotNull(groups={"file:post"})
     */
    public $targetEntity;

    /**
     * @Assert\NotBlank(groups={"file:post"})
     * @Assert\NotNull(groups={"file:post"})
     */
    public $type;

    public $meta;

    /**
     * @return array
     */
    public static function getProjectTypes(): array
    {
        return array_merge(self::getConstants('PROJECT_FILE_TYPES'), self::getConstants('PROJECT_FIELDS_TYPES'));
    }
}
