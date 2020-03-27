<?php

declare(strict_types=1);

namespace Unilend\Entity;

use Symfony\Component\HttpFoundation\File\UploadedFile;

class FileInput
{
    /**
     * @var UploadedFile
     */
    public $uploadedFile;

    // File IRI, nullable
    // ex /file/454fv-dsfh5-dfg55g
    public $file;

    // entity IRI
    // ex /project/454fv-dsfh5-dfg55g
    public $entity;

    public $meta;
}
