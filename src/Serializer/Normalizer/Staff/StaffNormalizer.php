<?php

declare(strict_types=1);

namespace Unilend\Serializer\Normalizer\Staff;

use Symfony\Component\Security\Core\Security;
use Symfony\Component\Serializer\Normalizer\{ContextAwareNormalizerInterface, DenormalizerAwareTrait,
    NormalizerAwareInterface, NormalizerAwareTrait};
use Unilend\Entity\Staff;
use Unilend\Security\Voter\StaffVoter;

class StaffNormalizer implements ContextAwareNormalizerInterface, NormalizerAwareInterface
{
    use NormalizerAwareTrait;
    use DenormalizerAwareTrait;

    private const ALREADY_CALLED   = 'STAFF_ATTRIBUTE_NORMALIZER_ALREADY_CALLED';

    /** @var Security */
    private Security $security;

    /**
     * @param Security $security
     */
    public function __construct(Security $security)
    {
        $this->security     = $security;
    }

    /**
     * {@inheritdoc}
     */
    public function supportsNormalization($data, ?string $format = null, array $context = []): bool
    {
        if (isset($context[self::ALREADY_CALLED])) {
            return false;
        }

        return $data instanceof Staff;
    }

    /**
     * {@inheritdoc}
     */
    public function normalize($object, ?string $format = null, array $context = [])
    {
        $context['groups'] = array_merge($context['groups'] ?? [], $this->getAdditionalNormalizerGroups($object));

        $context[self::ALREADY_CALLED] = true;

        return $this->normalizer->normalize($object, $format, $context);
    }


    /**
     * @param Staff $staff
     *
     * @return array
     */
    private function getAdditionalNormalizerGroups(Staff $staff): array
    {
        if ($this->security->isGranted(StaffVoter::ATTRIBUTE_ADMIN_VIEW, $staff)) {
            return [Staff::SERIALIZER_GROUP_ADMIN_READ, 'role:read'];
        }

        return [];
    }
}
