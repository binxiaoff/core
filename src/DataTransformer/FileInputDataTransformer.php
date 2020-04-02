<?php

declare(strict_types=1);

namespace Unilend\DataTransformer;

use ApiPlatform\Core\Api\IriConverterInterface;
use ApiPlatform\Core\Bridge\Symfony\Routing\IriConverter;
use ApiPlatform\Core\DataTransformer\DataTransformerInterface;
use ApiPlatform\Core\Serializer\AbstractItemNormalizer;
use ApiPlatform\Core\Validator\ValidatorInterface;
use Doctrine\ORM\EntityManagerInterface;
use Prophecy\Exception\InvalidArgumentException;
use Symfony\Component\Security\Core\Security;
use Unilend\Entity\{File, FileInput, Project, ProjectFile};
use Unilend\Service\File\FileUploadManager;

class FileInputDataTransformer implements DataTransformerInterface
{
    /** @var ValidatorInterface */
    private $validator;
    /** @var IriConverter */
    private $iriConverter;
    /** @var Security */
    private $security;
    /** @var FileUploadManager */
    private $fileUploadManager;
    /** @var EntityManagerInterface */
    private $entityManager;

    /**
     * @param ValidatorInterface     $validator
     * @param IriConverterInterface  $iriConverter
     * @param Security               $security
     * @param FileUploadManager      $fileUploadManager
     * @param EntityManagerInterface $entityManager
     */
    public function __construct(
        ValidatorInterface $validator,
        IriConverterInterface $iriConverter,
        Security $security,
        FileUploadManager $fileUploadManager,
        EntityManagerInterface $entityManager
    ) {
        $this->validator         = $validator;
        $this->iriConverter      = $iriConverter;
        $this->security          = $security;
        $this->fileUploadManager = $fileUploadManager;
        $this->entityManager     = $entityManager;
    }

    /**
     * @param        $data
     * @param string $to
     * @param array  $context
     *
     * @throws \Exception
     *
     * @return array|object|File|null
     */
    public function transform($data, string $to, array $context = [])
    {
        $this->validator->validate($data);

        $requestFile = $context[AbstractItemNormalizer::OBJECT_TO_POPULATE];
        $isPost      = null === $requestFile;

        $targetEntity = $this->iriConverter->getItemFromIri($data->targetEntity);
        $type         = $data->type;

        if (false === $targetEntity instanceof Project || false === in_array($type, FileInput::getProjectTypes())) {
            throw new InvalidArgumentException('You cant upload a file with this type.');
        }

        $uploadedFile = $data->uploadedFile;
        $meta         = $data->meta;
        $user         = $this->security->getUser();

        $file = $this->fileUploadManager->upload($uploadedFile, $user->getCurrentStaff(), $requestFile);

        if (in_array($type, FileInput::PROJECT_FILE_TYPES) && $isPost) {
            $projectFile = new ProjectFile($type, $file, $targetEntity, $user);
            $this->entityManager->persist($projectFile);
        }

        return $file;
    }

    /**
     * @param        $data
     * @param string $to
     * @param array  $context
     *
     * @return bool
     */
    public function supportsTransformation($data, string $to, array $context = []): bool
    {
        if ($data instanceof File) {
            return false;
        }

        return File::class === $to && null !== ($context['input']['class'] ?? null);
    }
}
