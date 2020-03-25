<?php

declare(strict_types=1);

namespace Unilend\DataPersister;

use ApiPlatform\Core\DataPersister\{ContextAwareDataPersisterInterface, DataPersisterInterface};
use Doctrine\ORM\NonUniqueResultException;
use Exception;
use InvalidArgumentException;
use Symfony\Component\Security\Core\Security;
use Unilend\Entity\{Clients, Staff, StaffStatus};
use Unilend\Repository\StaffRepository;

class StaffDataPersister implements ContextAwareDataPersisterInterface
{
    /** @var DataPersisterInterface */
    private $decorated;
    /** @var StaffRepository */
    private $staffRepository;
    /** @var Security */
    private $security;

    /**
     * @param DataPersisterInterface $decorated
     * @param StaffRepository        $staffRepository
     * @param Security               $security
     */
    public function __construct(DataPersisterInterface $decorated, StaffRepository $staffRepository, Security $security)
    {
        $this->decorated       = $decorated;
        $this->staffRepository = $staffRepository;
        $this->security        = $security;
    }

    /**
     * {@inheritdoc}
     *
     * @var Staff
     */
    public function supports($data, array $context = []): bool
    {
        return $this->decorated->supports($data);
    }

    /**
     * {@inheritdoc}
     *
     * @var Staff
     *
     * @throws NonUniqueResultException
     * @throws Exception
     */
    public function persist($data, array $context = [])
    {
        $user         = $this->security->getUser();
        $currentStaff = $user instanceof Clients ? $user->getCurrentStaff() : null;

        if (null === $currentStaff && false === $data instanceof Staff) {
            return $this->decorated->persist($data);
        }

        $persistedObject = $data;

        if ('collection' === ($context['operation_type'] ?? null) && 'post' === ($context['collection_operation_name'] ?? null)) {
            $persistedObject = $this->staffRepository->findOneByClientEmailAndCompany($data->getClient()->getEmail(), $data->getCompany());

            if (false === $persistedObject->isArchived()) {
                throw new InvalidArgumentException('You cannot post an unarchived staff');
            }

            $persistedObject->setRoles($data->getRoles());
            $persistedObject->setMarketSegments($data->getMarketSegments());
            $persistedObject->setCurrentStatus(new StaffStatus($persistedObject, StaffStatus::STATUS_ACTIVE, $currentStaff));
        }

        return $this->decorated->persist($persistedObject);
    }

    /**
     * {@inheritdoc}
     *
     * @var Staff
     */
    public function remove($data, array $context = [])
    {
        $this->decorated->remove($data);
    }
}
