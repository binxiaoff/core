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
use Symfony\Component\HttpFoundation\Exception\BadRequestException;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;
use Symfony\Component\Security\Core\Security;

class Post
{
    private FileUploadManager $fileUploadManager;
    private Security $security;

    public function __construct(FileUploadManager $fileUploadManager, Security $security)
    {
        $this->fileUploadManager = $fileUploadManager;
        $this->security          = $security;
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

        // Verify filenames before any upload
        // Hence the need to have 2 loops
        $fileEntities = \array_map(static function ($file) use ($parent) {
            if (false === $file instanceof UploadedFile) {
                throw new BadRequestException();
            }

            if (false === \in_array($file->getClientMimeType(), FileInput::ACCEPTED_MEDIA_TYPE)) {
                throw new BadRequestException(\sprintf('%s is not an acceptable media type', $file->getClientMimeType()));
            }

            $file = new File($file->getClientOriginalName());

            if ($parent->exist($file->getName())) {
                throw new BadRequestException('The file already exist');
            }

            return $file;
        }, $files);

        foreach ($files as $index => $uploadedFile) {
            $file = $fileEntities[$index];

            $this->fileUploadManager->upload($uploadedFile, $user, $file);

            $parent->addFile($file);
        }
    }
}
