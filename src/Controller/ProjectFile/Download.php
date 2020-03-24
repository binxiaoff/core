<?php

declare(strict_types=1);

namespace Unilend\Controller\ProjectFile;

use Doctrine\ORM\{ORMException, OptimisticLockException};
use Exception;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Symfony\Component\Security\Core\Security;
use Unilend\Entity\{FileDownload, FileVersion};
use Unilend\Repository\FileDownloadRepository;
use Unilend\Service\FileSystem\FileDownloadManager;

class Download
{
    /** @var FileDownloadManager */
    private $fileDownloadManager;
    /** @var Security */
    private $security;
    /** @var FileDownloadRepository */
    private $fileDownloadRepository;

    /**
     * @param FileDownloadManager          $fileDownloadManager
     * @param FileDownloadRepository $fileDownloadRepository
     * @param Security               $security
     */
    public function __construct(FileDownloadManager $fileDownloadManager, FileDownloadRepository $fileDownloadRepository, Security $security)
    {
        $this->fileDownloadManager          = $fileDownloadManager;
        $this->security               = $security;
        $this->fileDownloadRepository = $fileDownloadRepository;
    }

    /**
     * @param FileVersion $data
     *
     * @throws OptimisticLockException
     * @throws Exception
     * @throws ORMException
     *
     * @return StreamedResponse
     */
    public function __invoke(FileVersion $data): StreamedResponse
    {
        $user         = $this->security->getUser();
        $currentStaff = $user->getCurrentStaff();

        $this->fileDownloadRepository->save(new FileDownload($data, $currentStaff));

        return $this->fileDownloadManager->download($data);
    }
}
