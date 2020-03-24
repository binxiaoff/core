<?php

declare(strict_types=1);

namespace Unilend\Controller\File;

use Doctrine\ORM\OptimisticLockException;
use Doctrine\ORM\ORMException;
use League\Flysystem\FileNotFoundException;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Security\Core\Security;
use Unilend\Entity\{FileDownload, Project};
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
     * @param Project $data
     * @param Request $request
     *
     * @throws FileNotFoundException
     * @throws ORMException
     * @throws OptimisticLockException
     *
     * @return StreamedResponse
     */
    public function __invoke(Project $data, Request $request): StreamedResponse
    {
        $pathName = $request->request->get('_api_item_operation_name');
        $document = null;

        switch ($pathName) {
            case 'project_description':
                $document = $data->getDescriptionDocument();

                break;
            case 'project_confidentiality':
                $document = $data->getConfidentialityDisclaimer();

                break;
        }

        if (null === $document) {
            throw new NotFoundHttpException();
        }

        $user               = $this->security->getUser();
        $currentStaff       = $user->getCurrentStaff();
        $currentFileVersion = $document->getCurrentFileVersion();

        $this->fileDownloadRepository->save(new FileDownload($currentFileVersion, $currentStaff));

        return $this->fileSystemHelper->download(
            $this->fileSystemHelper->getFileSystemForClass($currentFileVersion),
            $currentFileVersion->getPath(),
            $currentFileVersion->getOriginalName()
        );
    }
}
