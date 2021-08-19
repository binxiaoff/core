<?php

declare(strict_types=1);

namespace KLS\Core\DataTransformer;

use ApiPlatform\Core\Validator\ValidatorInterface;
use Exception;
use InvalidArgumentException;
use KLS\Core\DTO\FileInput;
use KLS\Core\Entity\File;
use KLS\Core\Entity\User;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\Security\Core\Security;

class FileInputDataTransformer
{
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
     * @throws AccessDeniedHttpException
     * @throws InvalidArgumentException
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
                return $uploader->upload($targetEntity, $fileInput, $user, $file);
            }
        }

        throw new InvalidArgumentException(\sprintf('The targetEntity (%s) is not supported by any uploader', \get_class($targetEntity)));
    }
}
