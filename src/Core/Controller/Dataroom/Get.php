<?php

declare(strict_types=1);

namespace KLS\Core\Controller\Dataroom;

use Doctrine\ORM\OptimisticLockException;
use Doctrine\ORM\ORMException;
use Exception;
use KLS\Core\Entity\Drive;
use KLS\Core\Entity\File;
use KLS\Core\Entity\FileDownload;
use KLS\Core\Entity\Folder;
use KLS\Core\Entity\User;
use KLS\Core\Repository\FileDownloadRepository;
use KLS\Core\Service\File\FileDownloadManager;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;
use Symfony\Component\Security\Core\Security;

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

        if (null === $return) {
            throw new NotFoundHttpException();
        }

        if (
            $return instanceof File
            && \in_array('application/octet-stream', $request->getAcceptableContentTypes(), true)
        ) {
            $user = $this->security->getUser();

            if (false === ($user instanceof User)) {
                throw new AccessDeniedException();
            }

            $this->fileDownloadRepository->save(new FileDownload($return->getCurrentFileVersion(), $user, 'dataroom'));
            $return = $this->fileDownloadManager->download($return->getCurrentFileVersion());
        }

        return $return;
    }
}
