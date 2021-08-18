<?php

declare(strict_types=1);

namespace KLS\Core\DataTransformer;

use ApiPlatform\Core\Validator\ValidatorInterface;
use Exception;
use KLS\Core\DTO\FileInput;
use KLS\Core\Entity\File;
use KLS\Core\Entity\User;
use KLS\Core\Service\FileInput\FileInputDataUploadTrait;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\Security\Core\Security;

class FileInputDataTransformer
{
    use FileInputDataUploadTrait;

    private ValidatorInterface $validator;
    private Security $security;

    /** @var iterable|FileInputDataUploadInterface[] */
    private iterable $uploaders;

    public function __construct(ValidatorInterface $validator, Security $security, iterable $uploaders)
    {
        $this->validator = $validator;
        $this->security  = $security;
        $this->uploaders = $uploaders;
    }

    /**
     * @throws Exception
     */
    public function transform(FileInput $fileInput, ?File $file): File
    {
        $this->validator->validate($fileInput);

        $user = $this->security->getUser();

        if (false === $user instanceof User) {
            throw new AccessDeniedHttpException('Attempt to transform fileInput into file without valid user');
        }

        $targetEntity = $fileInput->targetEntity;

        foreach ($this->uploaders as $uploader) {
            if ($uploader->supports($targetEntity)) {
                $file = $uploader->upload($targetEntity, $fileInput, $user, $file);
            }
        }

        return $file;
    }
}
