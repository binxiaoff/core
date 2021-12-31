<?php

declare(strict_types=1);

namespace KLS\Core\Controller\Dataroom;

use Defuse\Crypto\Exception\EnvironmentIsBrokenException;
use Defuse\Crypto\Exception\IOException;
use Doctrine\ORM\OptimisticLockException;
use Doctrine\ORM\ORMException;
use Exception;
use KLS\Core\DTO\FileInput;
use KLS\Core\Entity\AbstractFolder;
use KLS\Core\Entity\Drive;
use KLS\Core\Entity\File;
use KLS\Core\Entity\User;
use KLS\Core\Exception\Drive\FolderAlreadyExistsException;
use KLS\Core\Service\File\FileUploadManager;
use League\Flysystem\FilesystemException;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;
use Symfony\Component\Security\Core\Security;
use Symfony\Component\Validator\Constraints\All;
use Symfony\Component\Validator\Constraints\Callback;
use Symfony\Component\Validator\Constraints\File as FileConstraint;
use Symfony\Component\Validator\Constraints\Sequentially;
use Symfony\Component\Validator\Constraints\Type;
use Symfony\Component\Validator\Validator\ValidatorInterface;

class Post
{
    private FileUploadManager $fileUploadManager;
    private Security $security;
    private ValidatorInterface $validator;

    public function __construct(
        FileUploadManager $fileUploadManager,
        Security $security,
        ValidatorInterface $validator
    ) {
        $this->fileUploadManager = $fileUploadManager;
        $this->security          = $security;
        $this->validator         = $validator;
    }

    /**
     * @throws EnvironmentIsBrokenException
     * @throws FilesystemException
     * @throws IOException
     * @throws ORMException
     * @throws OptimisticLockException
     * @throws Exception
     */
    public function __invoke(Drive $data, Request $request): AbstractFolder
    {
        $path         = $request->attributes->get('path');
        $parentFolder = $data->get($path);

        $user = $this->security->getUser();

        if (false === ($user instanceof User)) {
            throw new AccessDeniedException();
        }

        if (false === ($parentFolder instanceof AbstractFolder)) {
            throw new NotFoundHttpException();
        }

        if (0 === $request->files->count()) {
            // No file to upload  => we are trying to create a folder
            $this->handleFolder($parentFolder, $request);
        } else {
            // File are in the request => we are trying to upload files
            $this->handleFiles($parentFolder, $request, $user);
        }

        return $parentFolder;
    }

    private function handleFolder(AbstractFolder $parent, Request $request): void
    {
        // Only handle post parameter or json body
        $name = $request->request->get('name') ?? $request->toArray()['name'] ?? null;

        if (null === $name) {
            throw new BadRequestHttpException('There must be a folder name');
        }

        if ($parent->exist($name)) {
            throw new BadRequestHttpException('The folder already exist');
        }

        try {
            $parent->createFolder($name);
        } catch (FolderAlreadyExistsException $e) {
            throw new BadRequestHttpException();
        }
    }

    /**
     * @throws EnvironmentIsBrokenException
     * @throws FilesystemException
     * @throws IOException
     * @throws ORMException
     * @throws OptimisticLockException
     * @throws Exception
     */
    private function handleFiles(AbstractFolder $parent, Request $request, User $user): void
    {
        $files = \array_values($request->files->all());

        $constraints = [
            new All(
                new Sequentially([
                    new Type(UploadedFile::class),
                    new FileConstraint(),
                    new Callback(function ($files) use ($parent) {
                        if ($parent->exist($files->getClientOriginalName())) {
                            throw new BadRequestHttpException('The file already exist');
                        }

                        if (false === \in_array($files->getMimeType(), FileInput::ACCEPTED_MEDIA_TYPE, true)) {
                            throw new BadRequestHttpException(\sprintf(
                                '%s is not an acceptable media type',
                                $files->getMimeType()
                            ));
                        }
                    }),
                ]),
            ),
        ];

        $this->validator->validate($files, $constraints);

        foreach ($files as $uploadedFile) {
            $file = new File($uploadedFile->getClientOriginalName());

            $this->fileUploadManager->upload($uploadedFile, $user, $file);

            $parent->addFile($file);
        }
    }
}
