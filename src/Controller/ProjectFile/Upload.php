<?php

declare(strict_types=1);

namespace Unilend\Controller\ProjectFile;

use ApiPlatform\Core\Api\IriConverterInterface;
use Exception;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\Security\Core\Security;
use Symfony\Component\Serializer\Normalizer\AbstractNormalizer;
use Unilend\Entity\Clients;
use Unilend\Entity\FileVersion;
use Unilend\Entity\Project;
use Unilend\Entity\ProjectFile;
use Unilend\Repository\ProjectFileRepository;
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
    /** @var ProjectFileRepository */
    private $projectFileRepository;

    /**
     * @param FileManager           $fileManager
     * @param Security              $security
     * @param IriConverterInterface $converter
     * @param ProjectFileRepository $projectFileRepository
     */
    public function __construct(FileManager $fileManager, Security $security, IriConverterInterface $converter, ProjectFileRepository $projectFileRepository)
    {
        $this->fileManager           = $fileManager;
        $this->security              = $security;
        $this->converter             = $converter;
        $this->projectFileRepository = $projectFileRepository;
    }

    /**
     * @param Request $request
     *
     *@throws Exception
     *
     * @return FileVersion
     */
    public function __invoke(Request $request): FileVersion
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

        $file = $this->fileManager->upload(
            $request->files->get('file'),
            $user->getCurrentStaff(),
            $type,
            $project
        );

        $projectFile = new ProjectFile($type, $file, $project, $user);
        $this->projectFileRepository->save($projectFile);

        return $projectFile;
    }
}
