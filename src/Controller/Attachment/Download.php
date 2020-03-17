<?php

declare(strict_types=1);

namespace Unilend\Controller\Attachment;

use Doctrine\ORM\{ORMException, OptimisticLockException};
use Exception;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Symfony\Component\Security\Core\Security;
use Unilend\Entity\{AttachmentDownload, FileVersion};
use Unilend\Repository\AttachmentDownloadRepository;
use Unilend\Service\FileSystem\FileDownloadManager;

class Download
{
    /** @var FileDownloadManager */
    private $fileDownloadManager;
    /** @var Security */
    private $security;
    /** @var AttachmentDownloadRepository */
    private $attachmentDownloadRepository;

    /**
     * @param FileDownloadManager          $fileDownloadManager
     * @param AttachmentDownloadRepository $attachmentDownloadRepository
     * @param Security                     $security
     */
    public function __construct(FileDownloadManager $fileDownloadManager, AttachmentDownloadRepository $attachmentDownloadRepository, Security $security)
    {
        $this->fileDownloadManager          = $fileDownloadManager;
        $this->security                     = $security;
        $this->attachmentDownloadRepository = $attachmentDownloadRepository;
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

        $this->attachmentDownloadRepository->save(new AttachmentDownload($data, $currentStaff));

        return $this->fileDownloadManager->download($data);
    }
}
