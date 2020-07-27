<?php

declare(strict_types=1);

namespace Unilend\Serializer\Normalizer\Staff;

use Symfony\Component\Security\Core\Security;
use Symfony\Component\Serializer\Normalizer\{ContextAwareDenormalizerInterface, DenormalizerAwareInterface, DenormalizerAwareTrait,
    ObjectToPopulateTrait};
use Unilend\Entity\Staff;
use Unilend\Security\Voter\StaffVoter;

class StaffDenormalizer implements ContextAwareDenormalizerInterface, DenormalizerAwareInterface
{
    use DenormalizerAwareTrait;
    use ObjectToPopulateTrait;

    private const ALREADY_CALLED = 'STAFF_ATTRIBUTE_DENORMALIZER_ALREADY_CALLED';

    /** @var Security */
    private Security $security;

    /**
     * @param Security $security
     */
    public function __construct(Security $security)
    {
        $this->security = $security;
    }

    /**
     * {@inheritdoc}
     */
    public function denormalize($data, $type, ?string $format = null, array $context = [])
    {
        // In patch is only allowed for manager and coordinator so we don't need to check
        if ('post' === ($context['collection_operation_name'] ?? '')) {
            /** @var Staff $staff */
            $staff = $this->extractObjectToPopulate(Staff::class, $context);

            if ($staff) {
                $context['groups'] = array_merge($context['groups'] ?? [], $this->getAdditionalGroups($staff));
            }
        }

        $context[self::ALREADY_CALLED] = true;

        return $this->denormalizer->denormalize($data, $type, $format, $context);
    }

    /**
     * {@inheritdoc}
     */
    public function supportsDenormalization($data, $type, ?string $format = null, array $context = []): bool
    {
        return !isset($context[self::ALREADY_CALLED]) && Staff::class === $type;
    }

    /**
     * @param Staff $staff
     *
     * @return array
     */
    private function getAdditionalGroups(Staff $staff): array
    {
        if ($this->security->isGranted(StaffVoter::ATTRIBUTE_ADMIN_EDIT, $staff)) {
            return [Staff::SERIALIZER_GROUP_ADMIN_CREATE];
        }

        return [];
    }
}
