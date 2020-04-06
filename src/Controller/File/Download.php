<?php

declare(strict_types=1);

namespace Unilend\Controller\File;

use Doctrine\ORM\{ORMException, OptimisticLockException};
use Exception;
use Symfony\Component\HttpFoundation\{Request, StreamedResponse};
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\Security\Core\Security;
use Unilend\Entity\{Clients, FileDownload, FileVersion};
use Unilend\Repository\FileDownloadRepository;
use Unilend\Security\Voter\FileDownloadVoter;
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
     * @param string      $type
     *
     * @throws ORMException
     * @throws OptimisticLockException
     * @throws Exception
     *
     * @return StreamedResponse
     */
    public function __invoke(FileVersion $data, Request $request, string $type): StreamedResponse
    {
        $user         = $this->security->getUser();
        $currentStaff = $user instanceof Clients ? $user->getCurrentStaff() : null;

        if (null === $currentStaff) {
            throw new AccessDeniedHttpException();
        }

        $fileDownload = new FileDownload($data, $currentStaff, $type);

        if (false === $this->security->isGranted(FileDownloadVoter::ATTRIBUTE_CREATE, $fileDownload)) {
            throw new AccessDeniedHttpException();
        }

        $this->fileDownloadRepository->save($fileDownload);

        return $this->fileDownloadManager->download($data);
    }
}
