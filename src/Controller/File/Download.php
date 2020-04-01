<?php

declare(strict_types=1);

namespace Unilend\Controller\File;

use ApiPlatform\Core\Exception\RuntimeException;
use Doctrine\ORM\{ORMException, OptimisticLockException};
use Exception;
use Symfony\Component\HttpFoundation\{Request, StreamedResponse};
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\Security\Core\Security;
use Unilend\Entity\{FileDownload, FileVersion};
use Unilend\Repository\FileDownloadRepository;
use Unilend\Service\File\FileDownloadManager;

class Download
{
    /** @var Security */
    private $security;
    /** @var FileDownloadRepository */
    private $fileDownloadRepository;
    /** @var FileDownloadManager */
    private $fileDownloadManager;

    /**
     * @param FileDownloadManager    $fileDownloadManager
     * @param FileDownloadRepository $fileDownloadRepository
     * @param Security               $security
     */
    public function __construct(FileDownloadManager $fileDownloadManager, FileDownloadRepository $fileDownloadRepository, Security $security)
    {
        $this->fileDownloadManager    = $fileDownloadManager;
        $this->security               = $security;
        $this->fileDownloadRepository = $fileDownloadRepository;
    }

    /**
     * @param FileVersion $data
     * @param Request     $request
     *
     * @throws ORMException
     * @throws OptimisticLockException
     * @throws Exception
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

        return $this->fileDownloadManager->download($data);
    }
}
