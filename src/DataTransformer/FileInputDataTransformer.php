<?php

declare(strict_types=1);

namespace Unilend\DataTransformer;

use ApiPlatform\Core\Api\IriConverterInterface;
use ApiPlatform\Core\Bridge\Symfony\Routing\IriConverter;
use ApiPlatform\Core\DataTransformer\DataTransformerInterface;
use ApiPlatform\Core\Validator\ValidatorInterface;
use Doctrine\ORM\EntityManagerInterface;
use Prophecy\Exception\InvalidArgumentException;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\Security\Core\Security;
use Unilend\Entity\{Clients, File, FileInput, Project, ProjectFile};
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
        $request          = Request::createFromGlobals();
        $validationGroups = ['file:post'];

        if ($request->isMethod(Request::METHOD_PATCH)) {
            $validationGroups[] = 'file:patch';
        }

        $this->validator->validate($data, [
            'groups' => $validationGroups,
        ]);

        $uploadedFile = $data->uploadedFile;
        $meta         = $data->meta;
        $fileIri      = $data->file;

        if ($fileIri && $request->isMethod(Request::METHOD_POST)) {
            throw new InvalidArgumentException('A File should not be linked to a POST request.');
        }

        $targetEntity = $this->iriConverter->getItemFromIri($data->targetEntity);
        $type         = $data->type;

        if ($targetEntity instanceof Project && false === in_array($type, FileInput::getProjectTypes())) {
            throw new InvalidArgumentException('You cant upload a file with this type.');
        }

        /** @var File $file */
        $file = $this->iriConverter->getItemFromIri($fileIri);

        if (false === $file instanceof File) {
            throw new InvalidArgumentException('Invalid File IRI');
        }

        $user = $this->getUser($meta['userIri']);

        $file = $this->fileUploadManager->upload($uploadedFile, $user->getCurrentStaff(), $file);

        if ($targetEntity instanceof Project) {
            if (in_array($type, FileInput::PROJECT_FILE_TYPES) && $request->isMethod(Request::METHOD_POST)) {
                $projectFile = new ProjectFile($type, $file, $targetEntity, $user);
                $this->entityManager->persist($projectFile);
            }
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

    /**
     * @param $userIri
     *
     * @return Clients
     */
    private function getUser($userIri): Clients
    {
        $user = $this->security->getUser();

        if (null !== $userIri) {
            if (false === $this->security->isGranted(Clients::ROLE_ADMIN)) {
                throw new AccessDeniedHttpException();
            }

            $user = $this->iriConverter->getItemFromIri($userIri);

            if (false === $user instanceof Clients) {
                throw new InvalidArgumentException('Invalid user IRI.');
            }
        }

        return $user;
    }
}
