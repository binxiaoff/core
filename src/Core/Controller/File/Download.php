<?php

declare(strict_types=1);

namespace Unilend\Core\Controller\File;

use Doctrine\ORM\OptimisticLockException;
use Doctrine\ORM\ORMException;
use Exception;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\Security\Core\Security;
use Unilend\Core\Entity\FileDownload;
use Unilend\Core\Entity\FileVersion;
use Unilend\Core\Entity\User;
use Unilend\Core\Repository\FileDownloadRepository;
use Unilend\Core\Security\Voter\FileDownloadVoter;
use Unilend\Core\Service\File\FileDownloadManager;

class Download
{
    /** @var Security */
    private Security $security;
    /** @var FileDownloadRepository */
    private FileDownloadRepository $fileDownloadRepository;
    /** @var FileDownloadManager */
    private FileDownloadManager $fileDownloadManager;

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
        $user = $this->security->getUser();

        if (false === $user instanceof User) {
            throw new AccessDeniedHttpException(sprintf(
                'Attempt to download with %s%s instead of object of class %s',
                \is_object($user) ? 'object of class ' : '',
                \is_object($user) ? \get_class($user) : gettype($user),
                User::class
            ));
        }

        $token = $this->security->getToken();

        $company = null;

        if ($token) {
            $company = $token->getAttribute('company');
        }

        $fileDownload = new FileDownload($data, $user, $type, $company);

        if (false === $this->security->isGranted(FileDownloadVoter::ATTRIBUTE_CREATE, $fileDownload)) {
            throw new AccessDeniedHttpException();
        }

        $this->fileDownloadRepository->save($fileDownload);

        return $this->fileDownloadManager->download($data);
    }
}
