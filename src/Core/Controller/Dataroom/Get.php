<?php

declare(strict_types=1);

namespace Unilend\Core\Controller\Dataroom;

use Doctrine\ORM\OptimisticLockException;
use Doctrine\ORM\ORMException;
use Exception;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;
use Symfony\Component\Security\Core\Security;
use Unilend\Core\Entity\Drive;
use Unilend\Core\Entity\File;
use Unilend\Core\Entity\FileDownload;
use Unilend\Core\Entity\Folder;
use Unilend\Core\Entity\User;
use Unilend\Core\Repository\FileDownloadRepository;
use Unilend\Core\Service\File\FileDownloadManager;

class Get
{
    private Security $security;

    private FileDownloadManager $fileDownloadManager;

    private FileDownloadRepository $fileDownloadRepository;

    public function __construct(
        Security $security,
        FileDownloadManager $fileDownloadManager,
        FileDownloadRepository $fileDownloadRepository
    ) {
        $this->security               = $security;
        $this->fileDownloadManager    = $fileDownloadManager;
        $this->fileDownloadRepository = $fileDownloadRepository;
    }

    /**
     * @throws ORMException
     * @throws OptimisticLockException
     * @throws Exception
     *
     * @return Drive|Folder|File|Response
     */
    public function __invoke(Drive $data, Request $request)
    {
        $return = $data->get($request->get('path'));

        $user = $this->security->getUser();

        if (false === ($user instanceof User)) {
            throw new AccessDeniedException();
        }

        if (null === $return) {
            throw new NotFoundHttpException();
        }

        if (
            $return instanceof File
            && in_array('application/octet-stream', $request->getAcceptableContentTypes(), true)
        ) {
            $this->fileDownloadRepository->save(new FileDownload($return->getCurrentFileVersion(), $user, 'dataroom'));
            $return = $this->fileDownloadManager->download($return->getCurrentFileVersion());
        }

        return $return;
    }
}
