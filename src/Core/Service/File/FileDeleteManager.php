<?php

declare(strict_types=1);

namespace KLS\Core\Service\File;

use KLS\Core\Entity\File;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class FileDeleteManager
{
    /** @var iterable|FileDeleteInterface[] */
    private iterable $managers;

    public function __construct(iterable $managers)
    {
        $this->managers = $managers;
    }

    public function delete(File $file, string $type): void
    {
        foreach ($this->managers as $manager) {
            if ($manager->supports($type)) {
                $manager->delete($file, $type);

                return;
            }
        }

        throw new NotFoundHttpException(\sprintf('Unable to delete the file "%s" of type "%s"', $file->getPublicId(), $type));
    }
}
