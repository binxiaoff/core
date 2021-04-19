<?php

declare(strict_types=1);

namespace Unilend\Agency\Doctrine\Listener;

use Doctrine\ORM\Event\OnFlushEventArgs;
use Doctrine\ORM\ORMException;
use Unilend\Agency\Entity\Project as AgencyProject;
use Unilend\Syndication\Entity\Project as ArrangementProject;
use Unilend\Syndication\Repository\ProjectRepository as ArrangementRepository;

class ProjectListener
{
    private ArrangementRepository $arrangementProjectRepository;

    public function __construct(ArrangementRepository $arrangementProjectRepository)
    {
        $this->arrangementProjectRepository = $arrangementProjectRepository;
    }

    /**
     * @throws ORMException
     */
    public function onFlush(OnFlushEventArgs $args)
    {
        $em = $args->getEntityManager();

        $uow = $em->getUnitOfWork();

        $classMetadata = $em->getClassMetadata(ArrangementProject::class);

        foreach ($uow->getScheduledEntityInsertions() as $entity) {
            if ($entity instanceof AgencyProject && $entity->getSourcePublicId()) {
                $arrangementProject = $this->arrangementProjectRepository->findOneBy(['publicId' => $entity->getSourcePublicId()]);

                if ($arrangementProject) {
                    $arrangementProject->setAgencyImported(true);

                    $em->persist($arrangementProject);

                    $uow->computeChangeSet($classMetadata, $arrangementProject);
                    $uow->scheduleExtraUpdate($arrangementProject, $uow->getEntityChangeSet($arrangementProject));
                }
            }
        }
    }
}
