<?php

declare(strict_types=1);

namespace Unilend\DataTransformer;

use ApiPlatform\Core\DataTransformer\DataTransformerInterface;
use Doctrine\ORM\NonUniqueResultException;
use Exception;
use Symfony\Component\Security\Core\Security;
use Unilend\Entity\Staff;
use Unilend\Entity\StaffStatus;
use Unilend\Repository\StaffRepository;

class StaffDataTransformer implements DataTransformerInterface
{
    /**
     * @var StaffRepository
     */
    private $staffRepository;

    /**
     * @var Security
     */
    private $security;

    /**
     * @param StaffRepository $staffRepository
     * @param Security        $security
     */
    public function __construct(StaffRepository $staffRepository, Security $security)
    {
        $this->staffRepository = $staffRepository;
        $this->security        = $security;
    }

    /**
     * {@inheritdoc}
     *
     * @throws NonUniqueResultException
     * @throws Exception
     */
    public function transform($object, string $to, array $context = [])
    {
        /** @var Staff $object */
        $user = $this->security->getUser();

        /** @var Staff $existingStaff */
        $existingStaff = $this->staffRepository->findOneByClientEmailAndCompany($object->getClient()->getEmail(), $object->getCompany());

        // In post case we simply reactivate previous staff
        if (
            $user
            && 'collection' === $context['operation_type']
            && 'post' === $context['collection_operation_name']
            && $existingStaff && $existingStaff->isArchived()
        ) {
            $submitterStaff = $user->getCurrentStaff();
            $existingStaff->setRoles($object->getRoles());
            $existingStaff->setMarketSegments($object->getMarketSegments());
            $existingStaff->setCurrentStatus(new StaffStatus($existingStaff, StaffStatus::STATUS_ACTIVE, $submitterStaff));

            return $existingStaff;
        }

        return $object;
    }

    /**
     * {@inheritdoc}
     */
    public function supportsTransformation($data, string $to, array $context = []): bool
    {
        // If data is already a staff, the data should be already transformed
        return false === $data instanceof Staff && Staff::class === $to;
    }
}
