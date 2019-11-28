<?php

declare(strict_types=1);

namespace Unilend\Controller\Attachment;

use Exception;
use League\Flysystem\FileNotFoundException;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Unilend\Entity\Attachment;
use Unilend\Service\FileSystem\FileSystemHelper;

class Download
{
    /** @var FileSystemHelper */
    private $fileSystemHelper;

    /**
     * @param FileSystemHelper $fileSystemHelper
     */
    public function __construct(FileSystemHelper $fileSystemHelper)
    {
        $this->fileSystemHelper = $fileSystemHelper;
    }

    /**
     * @param Attachment $data
     *
     * @throws Exception
     * @throws FileNotFoundException
     *
     * @return StreamedResponse
     */
    public function __invoke(Attachment $data): StreamedResponse
    {
        return $this->fileSystemHelper->download($this->fileSystemHelper->getFileSystemForClass($data), $data->getPath(), $data->getOriginalName());
    }
}
