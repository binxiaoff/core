<?php

declare(strict_types=1);

namespace Unilend\Controller\File;

use Doctrine\ORM\{ORMException, OptimisticLockException};
use League\Flysystem\FileExistsException;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\Security\Core\Security;
use Unilend\DataTransformer\FileInputDataTransformer;
use Unilend\DTO\FileInput;
use Unilend\Entity\{Clients, File};
use Unilend\Repository\FileRepository;

class Upload
{
    /** @var Security */
    private $security;
    /** @var FileInputDataTransformer */
    private $fileInputDataTransformer;
    /**
     * @var FileRepository
     */
    private $fileRepository;

    /**
     * @param Security                 $security
     * @param FileInputDataTransformer $fileInputDataTransformer
     * @param FileRepository           $fileRepository
     */
    public function __construct(Security $security, FileInputDataTransformer $fileInputDataTransformer, FileRepository $fileRepository)
    {
        $this->security                 = $security;
        $this->fileInputDataTransformer = $fileInputDataTransformer;
        $this->fileRepository           = $fileRepository;
    }

    /**
     * @param Request     $request
     * @param string|null $id
     *
     * @throws FileExistsException
     * @throws ORMException
     * @throws OptimisticLockException
     *
     * @return File
     */
    public function __invoke(Request $request, ?string $id): File
    {
        $user         = $this->security->getUser();
        $currentStaff = $user instanceof Clients ? $user->getCurrentStaff() : null;

        if (null === $currentStaff) {
            throw new AccessDeniedHttpException();
        }

        $file = $id ? $this->fileRepository->findOneBy(['publicId' => $id]) : null;

        $fileInput = new FileInput($request->files->get('file'), $request->request->get('type'), $request->request->get('targetEntity'));

        return $this->fileInputDataTransformer->transform($fileInput, $file);
    }
}
