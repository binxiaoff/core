<?php

declare(strict_types=1);

namespace Unilend\Controller\File;

use ApiPlatform\Core\Exception\RuntimeException;
use Doctrine\ORM\OptimisticLockException;
use Doctrine\ORM\ORMException;
use League\Flysystem\FileNotFoundException;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\Security\Core\Security;
use Unilend\Entity\{FileDownload, FileVersion};
use Unilend\Repository\FileDownloadRepository;
use Unilend\Service\FileSystem\FileSystemHelper;

class Download
{
    /** @var FileSystemHelper */
    private $fileSystemHelper;
    /** @var Security */
    private $security;
    /** @var FileDownloadRepository */
    private $fileDownloadRepository;

    /**
     * @param FileSystemHelper       $fileSystemHelper
     * @param FileDownloadRepository $fileDownloadRepository
     * @param Security               $security
     */
    public function __construct(FileSystemHelper $fileSystemHelper, FileDownloadRepository $fileDownloadRepository, Security $security)
    {
        $this->fileSystemHelper       = $fileSystemHelper;
        $this->security               = $security;
        $this->fileDownloadRepository = $fileDownloadRepository;
    }

    /**
     * @param FileVersion $data
     * @param Request     $request
     *
     * @throws ORMException
     * @throws OptimisticLockException
     * @throws \Exception
     * @throws FileNotFoundException
     *
     * @return StreamedResponse
     */
    public function __invoke(FileVersion $data, Request $request): StreamedResponse
    {
        // Useless if we use DTO
        $user = $this->security->getUser();

        if (null === $user) {
            throw new AccessDeniedHttpException();
        }

        $currentStaff = $user->getCurrentStaff();

        if (null === $currentStaff) {
            throw new RuntimeException();
        }

        $this->fileDownloadRepository->save(new FileDownload($data, $currentStaff));

        return $this->fileSystemHelper->download(
            $this->fileSystemHelper->getFileSystemForClass($data),
            $data->getPath(),
            $data->getOriginalName()
        );
    }
}
