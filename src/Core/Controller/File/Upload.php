<?php

declare(strict_types=1);

namespace Unilend\Core\Controller\File;

use ApiPlatform\Core\Api\IriConverterInterface;
use Doctrine\ORM\{ORMException, OptimisticLockException};
use League\Flysystem\FileExistsException;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\Security\Core\Security;
use Symfony\Component\Serializer\Normalizer\AbstractNormalizer;
use Unilend\Core\DataTransformer\FileInputDataTransformer;
use Unilend\Core\DTO\FileInput;
use Unilend\Core\Entity\File;
use Unilend\Core\Entity\User;
use Unilend\Core\Repository\FileRepository;

class Upload
{
    /** @var Security */
    private Security $security;
    /** @var FileInputDataTransformer */
    private FileInputDataTransformer $fileInputDataTransformer;
    /** @var FileRepository */
    private FileRepository $fileRepository;
    /** @var IriConverterInterface */
    private IriConverterInterface $iriConverter;

    /**
     * @param Security                 $security
     * @param FileInputDataTransformer $fileInputDataTransformer
     * @param FileRepository           $fileRepository
     * @param IriConverterInterface    $iriConverter
     */
    public function __construct(
        Security $security,
        FileInputDataTransformer $fileInputDataTransformer,
        FileRepository $fileRepository,
        IriConverterInterface $iriConverter
    ) {
        $this->security                 = $security;
        $this->fileInputDataTransformer = $fileInputDataTransformer;
        $this->fileRepository           = $fileRepository;
        $this->iriConverter             = $iriConverter;
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
        $currentStaff = $user instanceof User ? $user->getCurrentStaff() : null;

        if (null === $currentStaff) {
            throw new AccessDeniedHttpException();
        }

        $file = $id ? $this->fileRepository->findOneBy(['publicId' => $id]) : null;

        // No group, no joint, more performance
        $targetEntity = $this->iriConverter->getItemFromIri($request->request->get('targetEntity'), [AbstractNormalizer::GROUPS => []]);

        $fileInput = new FileInput($request->files->get('file'), $request->request->get('type'), $targetEntity);

        return $this->fileInputDataTransformer->transform($fileInput, $file);
    }
}
