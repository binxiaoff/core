<?php

declare(strict_types=1);

namespace Unilend\CreditGuaranty\Serializer\Normalizer;

use Symfony\Component\Serializer\Normalizer\AbstractNormalizer;
use Symfony\Component\Serializer\Normalizer\ContextAwareNormalizerInterface;
use Symfony\Component\Serializer\Normalizer\NormalizerAwareInterface;
use Symfony\Component\Serializer\Normalizer\NormalizerAwareTrait;
use Unilend\Core\Entity\Company;
use Unilend\CreditGuaranty\Repository\StaffPermissionRepository;

class CompanyNormalizer implements ContextAwareNormalizerInterface, NormalizerAwareInterface
{
    use NormalizerAwareTrait;

    private const ALREADY_CALLED = __CLASS__ . '_ALREADY_CALLED';

    private StaffPermissionRepository $staffPermissionRepository;

    public function __construct(StaffPermissionRepository $staffPermissionRepository)
    {
        $this->staffPermissionRepository = $staffPermissionRepository;
    }

    /**
     * {@inheritDoc}
     */
    public function supportsNormalization($data, string $format = null, array $context = []): bool
    {
        return $data instanceof Company && !isset($context[static::ALREADY_CALLED]);
    }

    /**
     * {@inheritDoc}
     */
    public function normalize($object, string $format = null, array $context = [])
    {
        $context[static::ALREADY_CALLED] = true;

        /** @var array $data */
        $data = $this->normalizer->normalize($object, $format, $context);

        if (false === isset($data['creditGuarantyAdminStaff'])) {
            $context[AbstractNormalizer::GROUPS] = array_merge($context[AbstractNormalizer::GROUPS] ?? [], ['staff:read', 'user:read']);
            $data['creditGuarantyAdminStaff']    = [];
            if ($object instanceof Company) {
                $staffPermissions = $this->staffPermissionRepository->findParticipationAdmins($object);
                foreach ($staffPermissions as $staffPermission) {
                    $data['creditGuarantyAdminStaff'][] = $this->normalizer->normalize($staffPermission->getStaff(), $format, $context);
                }
            }
        }

        return $data;
    }
}
