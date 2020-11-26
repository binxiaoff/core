<?php

declare(strict_types=1);

namespace Unilend\Core\Controller\File;

use Doctrine\ORM\{ORMException, OptimisticLockException};
use Unilend\Core\Entity\File;
use Unilend\Core\Service\File\FileDeleteManager;

class Delete
{
    /** @var FileDeleteManager */
    private $fileDeleteManager;

    /**
     * @param FileDeleteManager $fileDeleteManager
     */
    public function __construct(FileDeleteManager $fileDeleteManager)
    {
        $this->fileDeleteManager = $fileDeleteManager;
    }

    /**
     * @param File   $data
     * @param string $type
     *
     * @throws ORMException
     * @throws OptimisticLockException
     */
    public function __invoke(File $data, string $type): void
    {
        $this->fileDeleteManager->delete($data, $type);
    }
}
