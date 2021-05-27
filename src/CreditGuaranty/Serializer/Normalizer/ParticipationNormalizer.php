<?php

declare(strict_types=1);

namespace Unilend\CreditGuaranty\Serializer\Normalizer;

use ApiPlatform\Core\Api\IriConverterInterface;
use Symfony\Component\Serializer\Normalizer\AbstractNormalizer;
use Symfony\Component\Serializer\Normalizer\ContextAwareNormalizerInterface;
use Symfony\Component\Serializer\Normalizer\NormalizerAwareInterface;
use Symfony\Component\Serializer\Normalizer\NormalizerAwareTrait;
use Unilend\Core\Entity\Company;
use Unilend\CreditGuaranty\Entity\Participation;
use Unilend\CreditGuaranty\Repository\StaffPermissionRepository;

class ParticipationNormalizer implements ContextAwareNormalizerInterface, NormalizerAwareInterface
{
    use NormalizerAwareTrait;

    private const ALREADY_CALLED = __CLASS__ . '_ALREADY_CALLED';
    private StaffPermissionRepository $staffPermissionRepository;
    private IriConverterInterface     $iriConverter;

    public function __construct(StaffPermissionRepository $staffPermissionRepository, IriConverterInterface $iriConverter)
    {
        $this->staffPermissionRepository = $staffPermissionRepository;
        $this->iriConverter              = $iriConverter;
    }

    /**
     * {@inheritDoc}
     */
    public function supportsNormalization($data, string $format = null, array $context = []): bool
    {
        return $data instanceof Participation && !isset($context[static::ALREADY_CALLED]);
    }

    /**
     * {@inheritDoc}
     */
    public function normalize($object, string $format = null, array $context = [])
    {
        $context[static::ALREADY_CALLED] = true;

        /** @var array $data */
        $data = $this->normalizer->normalize($object, $format, $context);

        if (false === isset($data['adminStaff']) && isset($data['participant'])) {
            $context[AbstractNormalizer::GROUPS] = array_merge($context[AbstractNormalizer::GROUPS] ?? [], ['staff:read', 'user:read']);
            $data['adminStaff']                  = [];
            $company                             = $this->iriConverter->getItemFromIri($data['participant'], []);
            if ($company instanceof Company) {
                $staffPermissions = $this->staffPermissionRepository->findParticipationAdmins($company);
                foreach ($staffPermissions as $staffPermission) {
                    $data['adminStaff'][] = $this->normalizer->normalize($staffPermission->getStaff(), $format, $context);
                }
            }
        }

        return $data;
    }
}
