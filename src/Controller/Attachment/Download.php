<?php

declare(strict_types=1);

namespace Unilend\Controller\Attachment;

use Doctrine\Common\Persistence\ObjectManager;
use Exception;
use League\Flysystem\FileNotFoundException;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Symfony\Component\Security\Core\Security;
use Unilend\Entity\Attachment;
use Unilend\Entity\AttachmentDownload;
use Unilend\Service\FileSystem\FileSystemHelper;

class Download
{
    /** @var FileSystemHelper */
    private $fileSystemHelper;
    /** @var ObjectManager */
    private $entityManager;
    /** @var Security */
    private $security;

    /**
     * @param FileSystemHelper $fileSystemHelper
     * @param ObjectManager    $entityManager
     * @param Security         $security
     */
    public function __construct(
        FileSystemHelper $fileSystemHelper,
        ObjectManager $entityManager,
        Security $security
    ) {
        $this->fileSystemHelper = $fileSystemHelper;
        $this->entityManager    = $entityManager;
        $this->security         = $security;
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
        $this->entityManager->persist(new AttachmentDownload($data, $this->security->getUser()));
        $this->entityManager->flush();

        return $this->fileSystemHelper->download($this->fileSystemHelper->getFileSystemForClass($data), $data->getPath(), $data->getOriginalName());
    }
}
