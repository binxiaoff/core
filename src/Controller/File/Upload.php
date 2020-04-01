<?php

declare(strict_types=1);

namespace Unilend\Controller\File;

use ApiPlatform\Core\Api\IriConverterInterface;
use Doctrine\ORM\{ORMException, OptimisticLockException};
use Exception;
use League\Flysystem\FileExistsException;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\Security\Core\Security;
use Unilend\Entity\{Clients, File, Project};
use Unilend\Service\File\FileUploadManager;

class Upload
{
    /** @var FileUploadManager */
    private $fileUploadManager;
    /** @var Security */
    private $security;
    /** @var IriConverterInterface */
    private $converter;

    /**
     * @param FileUploadManager     $fileUploadManager
     * @param Security              $security
     * @param IriConverterInterface $converter
     */
    public function __construct(FileUploadManager $fileUploadManager, Security $security, IriConverterInterface $converter)
    {
        $this->fileUploadManager = $fileUploadManager;
        $this->security          = $security;
        $this->converter         = $converter;
    }

    /**
     * @param Project $data
     * @param Request $request
     *
     * @throws ORMException
     * @throws OptimisticLockException
     * @throws Exception
     * @throws FileExistsException
     *
     * @return File
     */
    public function __invoke(Project $data, Request $request): File
    {
        $user = $this->security->getUser();

        // If a "user" is found in the request, it means that we want to upload a file for the "user".
        // In this case, we check if the current user is admin.
        if ($userIri = $request->request->get('user')) {
            if (false === $this->security->isGranted(Clients::ROLE_ADMIN)) {
                throw new AccessDeniedHttpException();
            }
            $user = $this->converter->getItemFromIri($userIri);
        }

        // Dynamically find the requested document based on defined operation name
        $document = array_map(function ($element) {
            return ucfirst($element);
        }, explode('_', $request->request->get('_api_item_operation_name')));

        $getter = 'get' . $document;
        $setter = 'set' . $document;

        if (false === is_callable($data, $getter) || false === is_callable($data, $setter)) {
            throw new Exception();
        }

        $file = $this->fileUploadManager->upload(
            $request->files->get('file'),
            $user->getCurrentStaff(),
            $data->{$getter}(),
        );

        $data->{$setter}($file);

        return $file;
    }
}
