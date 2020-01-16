<?php

declare(strict_types=1);

namespace Unilend\Controller\Attachment;

use Exception;
use League\Flysystem\FileNotFoundException;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Symfony\Component\Security\Core\Security;
use Unilend\Entity\{Attachment, AttachmentDownload};
use Unilend\Repository\AttachmentDownloadRepository;
use Unilend\Service\FileSystem\FileSystemHelper;

class Download
{
    /** @var FileSystemHelper */
    private $fileSystemHelper;
    /** @var Security */
    private $security;
    /** @var AttachmentDownloadRepository */
    private $repository;

    /**
     * @param FileSystemHelper             $fileSystemHelper
     * @param AttachmentDownloadRepository $repository
     * @param Security                     $security
     */
    public function __construct(FileSystemHelper $fileSystemHelper, AttachmentDownloadRepository $repository, Security $security)
    {
        $this->fileSystemHelper = $fileSystemHelper;
        $this->security         = $security;
        $this->repository       = $repository;
    }

    /**
     * @param Attachment $data
     *
     * @throws FileNotFoundException
     * @throws Exception
     *
     * @return StreamedResponse
     */
    public function __invoke(Attachment $data): StreamedResponse
    {
        $this->repository->save(new AttachmentDownload($data, $this->security->getUser()));

        return $this->fileSystemHelper->download($this->fileSystemHelper->getFileSystemForClass($data), $data->getPath(), $data->getOriginalName());
    }
}
