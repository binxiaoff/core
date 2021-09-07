<?php

declare(strict_types=1);

namespace KLS\Core\Controller\File;

use Doctrine\ORM\OptimisticLockException;
use Doctrine\ORM\ORMException;
use KLS\Core\Entity\File;
use KLS\Core\Service\File\FileDeleteManager;

class Delete
{
    /** @var FileDeleteManager */
    private $fileDeleteManager;

    public function __construct(FileDeleteManager $fileDeleteManager)
    {
        $this->fileDeleteManager = $fileDeleteManager;
    }

    /**
     * @throws ORMException
     * @throws OptimisticLockException
     */
    public function __invoke(File $data, string $type): void
    {
        $this->fileDeleteManager->delete($data, $type);
    }
}
