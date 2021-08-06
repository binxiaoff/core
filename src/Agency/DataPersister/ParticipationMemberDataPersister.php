<?php

declare(strict_types=1);

namespace Unilend\Agency\DataPersister;

use ApiPlatform\Core\DataPersister\ContextAwareDataPersisterInterface;
use Doctrine\ORM\NonUniqueResultException;
use Doctrine\ORM\OptimisticLockException;
use Doctrine\ORM\ORMException;
use Exception;
use Unilend\Agency\Entity\ParticipationMember;
use Unilend\Core\Entity\Staff;
use Unilend\Core\Repository\StaffRepository;

class ParticipationMemberDataPersister implements ContextAwareDataPersisterInterface
{
    private ContextAwareDataPersisterInterface $decorated;
    private StaffRepository $staffRepository;

    public function __construct(ContextAwareDataPersisterInterface $decorated, StaffRepository $staffRepository)
    {
        $this->decorated       = $decorated;
        $this->staffRepository = $staffRepository;
    }

    public function supports($data, array $context = []): bool
    {
        return $this->decorated->supports($data);
    }

    /**
     * @param mixed $data
     *
     * @throws Exception
     * @throws NonUniqueResultException
     * @throws ORMException
     * @throws OptimisticLockException
     */
    public function persist($data, array $context = [])
    {
        $result = $this->decorated->persist($data);

        // Only handle participation Member
        if (false === $data instanceof ParticipationMember) {
            return $result;
        }

        $company = $data->getParticipation()->getParticipant();

        // Ignore signed companies
        if ($company->hasSigned()) {
            return $result;
        }

        $user = $data->getUser();

        // Record staff for external banks to allow connection
        $staff = $this->staffRepository->findOneByEmailAndCompany($user->getEmail(), $company);

        if (null === $staff) {
            $staff = new Staff($user, $company->getRootTeam());
            $this->staffRepository->save($staff);
        }

        return $result;
    }

    public function remove($data, array $context = [])
    {
        return $this->decorated->remove($data, $context);
    }
}
