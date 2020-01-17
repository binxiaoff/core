<?php

declare(strict_types=1);

namespace Unilend\Serializer\Normalizer\Staff;

use Symfony\Component\Security\Core\Security;
use Symfony\Component\Serializer\Normalizer\{ContextAwareNormalizerInterface, NormalizerAwareInterface, NormalizerAwareTrait};
use Unilend\Entity\{Clients, Staff};

class StaffNormalizer implements ContextAwareNormalizerInterface, NormalizerAwareInterface
{
    use NormalizerAwareTrait;

    private const ALREADY_CALLED = 'STAFF_ATTRIBUTE_NORMALIZER_ALREADY_CALLED';

    /** @var Security */
    private $security;

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
    public function supportsNormalization($data, $format = null, array $context = [])
    {
        if (isset($context[self::ALREADY_CALLED])) {
            return false;
        }

        return $data instanceof Staff;
    }

    /**
     * {@inheritdoc}
     */
    public function normalize($object, $format = null, array $context = [])
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
        $client = $this->security->getUser();

        if (!$client instanceof Clients) {
            return [];
        }

        $connectedStaff = $client->getStaff();

        return  $connectedStaff && $connectedStaff->hasRole(Staff::DUTY_STAFF_ADMIN) && $connectedStaff->getCompany() === $staff->getCompany() ?
            [Staff::SERIALIZER_GROUP_ADMIN_READ, 'role:read'] : [];
    }
}
