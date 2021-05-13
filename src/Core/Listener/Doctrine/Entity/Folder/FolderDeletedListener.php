<?php

declare(strict_types=1);

namespace Unilend\Core\Listener\Doctrine\Entity\Folder;

use Doctrine\ORM\Event\LifecycleEventArgs;
use Doctrine\ORM\ORMException;
use Unilend\Core\Entity\Drive;
use Unilend\Core\Entity\Folder;

class FolderDeletedListener
{
    /**
     * @throws ORMException
     */
    public function cascadeDeletion(Folder $folder, LifecycleEventArgs $args)
    {
        // Call to drive to properly delete folder (handle deletion of child folder and files)
        // Then doctrine remove now useless elements with orphanRemove=true
        $drive = $folder->getDrive();

        $drive->delete($folder);

        $em = $args->getEntityManager();

        // The persist is here to activate orphanRemoval=true
        $em->persist($folder->getDrive());

        $uow = $em->getUnitOfWork();

        $uow->recomputeSingleEntityChangeSet($em->getClassMetadata(Drive::class), $folder->getDrive());
    }
}
