<?php

declare(strict_types=1);

namespace Unilend\Controller\File;

use ApiPlatform\Core\Api\IriConverterInterface;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\OptimisticLockException;
use Doctrine\ORM\ORMException;
use Exception;
use League\Flysystem\FileExistsException;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\Security\Core\Security;
use Unilend\Entity\Clients;
use Unilend\Entity\File;
use Unilend\Entity\Project;
use Unilend\Service\File\FileManager;

class Upload
{
    /** @var FileManager */
    private $fileManager;
    /** @var Security */
    private $security;
    /** @var IriConverterInterface */
    private $converter;
    /**
     * @var EntityManagerInterface
     */
    private $entityManager;

    /**
     * @param FileManager            $fileManager
     * @param Security               $security
     * @param IriConverterInterface  $converter
     * @param EntityManagerInterface $entityManager
     */
    public function __construct(FileManager $fileManager, Security $security, IriConverterInterface $converter, EntityManagerInterface $entityManager)
    {
        $this->fileManager   = $fileManager;
        $this->security      = $security;
        $this->converter     = $converter;
        $this->entityManager = $entityManager;
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

        $file = $this->fileManager->upload(
            $data->{$getter}(),
            $request->files->get('file'),
            $user->getCurrentStaff()
        );

        $data->{$setter}($file);

        $this->entityManager->flush();

        return $file;
    }
}
