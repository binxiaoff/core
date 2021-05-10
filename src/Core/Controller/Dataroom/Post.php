<?php

declare(strict_types=1);

namespace Unilend\Core\Controller\Dataroom;

use Defuse\Crypto\Exception\EnvironmentIsBrokenException;
use Defuse\Crypto\Exception\IOException;
use Doctrine\ORM\OptimisticLockException;
use Doctrine\ORM\ORMException;
use Exception;
use League\Flysystem\FileExistsException;
use Symfony\Component\HttpFoundation\Exception\BadRequestException;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;
use Symfony\Component\Security\Core\Security;
use Unilend\Core\DTO\FileInput;
use Unilend\Core\Entity\AbstractFolder;
use Unilend\Core\Entity\Drive;
use Unilend\Core\Entity\File;
use Unilend\Core\Entity\User;
use Unilend\Core\Exception\Drive\FolderAlreadyExistsException;
use Unilend\Core\Service\File\FileUploadManager;

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
     * @throws FileExistsException
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
            // No file to upload  => we are tring to create a folder
            $this->handleFolder($parentFolder, $request);
        } else {
            // File are in the request => we are trying to upload files
            $this->handleFiles($parentFolder, $request, $user);
        }

        return $parentFolder;
    }

    private function handleFolder(AbstractFolder $parent, Request $request)
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
     * @throws FileExistsException
     * @throws IOException
     * @throws ORMException
     * @throws OptimisticLockException
     * @throws Exception
     */
    private function handleFiles(AbstractFolder $parent, Request $request, User $user)
    {
        $files = array_values($request->files->all());

        // Verify filenames before any upload
        // Hence the need to have 2 loops
        $fileEntities = array_map(static function ($file) use ($parent) {
            if (false === $file instanceof UploadedFile) {
                throw new BadRequestException();
            }

            if (false === in_array($file->getClientMimeType(), FileInput::ACCEPTED_MEDIA_TYPE)) {
                throw new BadRequestException(sprintf('%s is not an acceptable media type', $file->getClientMimeType()));
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
