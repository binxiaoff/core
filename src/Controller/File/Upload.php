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
use Symfony\Component\Serializer\Normalizer\AbstractNormalizer;
use Unilend\Entity\Clients;
use Unilend\Entity\File;
use Unilend\Entity\Project;
use Unilend\Security\Voter\ProjectVoter;
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
     * @param Request $request
     *
     * @throws ORMException
     * @throws OptimisticLockException
     * @throws FileExistsException
     *
     * @return File
     */
    public function __invoke(Request $request): File
    {
        /** @var Clients $user */
        $user = $this->security->getUser();

        // If a "user" is found in the request, it means that we want to upload a file for the "user".
        // In this case, we check if the current user is admin.
        if ($userIri = $request->request->get('user')) {
            if (false === $this->security->isGranted(Clients::ROLE_ADMIN)) {
                throw new AccessDeniedHttpException();
            }
            $user = $this->converter->getItemFromIri($userIri);
        }

        $type = $request->request->get('type');

        if (null === $type) {
            throw new \InvalidArgumentException('You should define a type for the project file.');
        }

        $projectIri = $request->request->get('project');
        /** @var Project $project */
        $project = $projectIri ? $this->converter->getItemFromIri($projectIri, [AbstractNormalizer::GROUPS => ['project:read']]) : null;

        if (false === $this->security->isGranted(ProjectVoter::ATTRIBUTE_EDIT, $project)) {
            throw new AccessDeniedHttpException('You cannot upload file for the project');
        }

        $pathName = $request->request->get('_api_item_operation_name');
        $file     = null;

        switch ($pathName) {
            case 'project_description':
                $file = 'DescriptionDocument';

                break;
            case 'project_confidentiality':
                $file = 'ConfidentialityDisclaimer';

                break;
        }

        $getter = 'get' . $file;
        $setter = 'set' . $file;

        if (false === is_callable($project, $getter) || false === is_callable($project, $setter)) {
            throw new Exception();
        }

        $file = $this->fileManager->uploadFile(
            $project->{$getter}(),
            $request->files->get('file'),
            $user->getCurrentStaff()
        );

        $project->{$setter}($file);

        $this->entityManager->flush();

        return $file;
    }
}
