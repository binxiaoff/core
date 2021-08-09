<?php

declare(strict_types=1);

namespace KLS\Core\Controller\File;

use Doctrine\ORM\OptimisticLockException;
use Doctrine\ORM\ORMException;
use Exception;
use KLS\Core\Entity\FileDownload;
use KLS\Core\Entity\FileVersion;
use KLS\Core\Entity\User;
use KLS\Core\Repository\FileDownloadRepository;
use KLS\Core\Security\Voter\FileDownloadVoter;
use KLS\Core\Service\File\FileDownloadManager;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\Security\Core\Security;

class Download
{
    private Security $security;
    private FileDownloadRepository $fileDownloadRepository;
    private FileDownloadManager $fileDownloadManager;

    public function __construct(FileDownloadManager $fileDownloadManager, FileDownloadRepository $fileDownloadRepository, Security $security)
    {
        $this->fileDownloadManager    = $fileDownloadManager;
        $this->security               = $security;
        $this->fileDownloadRepository = $fileDownloadRepository;
    }

    /**
     * @throws ORMException
     * @throws OptimisticLockException
     * @throws Exception
     */
    public function __invoke(FileVersion $data, Request $request, string $type): StreamedResponse
    {
        $user = $this->security->getUser();

        if (false === $user instanceof User) {
            throw new AccessDeniedHttpException(\sprintf(
                'Attempt to download with %s%s instead of object of class %s',
                \is_object($user) ? 'object of class ' : '',
                \is_object($user) ? \get_class($user) : \gettype($user),
                User::class
            ));
        }

        $token = $this->security->getToken();

        $company = $token && $token->hasAttribute('company') ? $token->getAttribute('company') : null;

        $fileDownload = new FileDownload($data, $user, $type, $company);

        if (false === $this->security->isGranted(FileDownloadVoter::ATTRIBUTE_CREATE, $fileDownload)) {
            throw new AccessDeniedHttpException();
        }

        $this->fileDownloadRepository->save($fileDownload);

        return $this->fileDownloadManager->download($data);
    }
}
