<?php

declare(strict_types=1);

namespace Unilend\Repository;

use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Common\Persistence\ManagerRegistry;
use Doctrine\ORM\NonUniqueResultException;
use Unilend\Entity\{Project, ProjectParticipationContact, Staff};

/**
 * @method ProjectParticipationContact|null find($id, $lockMode = null, $lockVersion = null)
 * @method ProjectParticipationContact|null findOneBy(array $criteria, array $orderBy = null)
 * @method ProjectParticipationContact[]    findAll()
 * @method ProjectParticipationContact[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class ProjectParticipationContactRepository extends ServiceEntityRepository
{
    /**
     * @param ManagerRegistry $registry
     */
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, ProjectParticipationContact::class);
    }

    /**
     * @param Project $project
     * @param Staff   $staff
     *
     * @throws NonUniqueResultException
     *
     * @return ProjectParticipationContact|null
     *
     * @todo Maybe, we should replace the field client by staff in the ProjectParticipationContact class.
     *       But as we don't know if we can add a non-staff (new) client as a participant or not, we won't change it now, we need a confirmation on this point.
     *       Otherwise, after the change, the same staff can always be (technically) in different participations of the same project (when a staff is occasionally invited to anther
     *       entity's participation), we need also a confirmation from business unit that it can happen or not. Thus, finding a ProjectParticipationContact by searching the company
     *       in ProjectParticipation is just a workaround.
     */
    public function findByProjectAndStaff(Project $project, Staff $staff): ?ProjectParticipationContact
    {
        $queryBuilder = $this->createQueryBuilder('ppc')
            ->innerJoin('ppc.projectParticipation', 'pp')
            ->where('ppc.client = :client')
            ->andWhere('pp.company = :company')
            ->andWhere('pp.project = :project')
            ->setParameters([
                'client'  => $staff->getClient(),
                'project' => $project,
                'company' => $staff->getCompany(),
            ])
        ;

        return $queryBuilder->getQuery()->getOneOrNullResult();
    }
}
