<?php

declare(strict_types=1);

namespace Unilend\Serializer\Normalizer\Staff;

use ApiPlatform\Core\Api\IriConverterInterface;
use Symfony\Component\Security\Core\Security;
use Symfony\Component\Serializer\Normalizer\{ContextAwareDenormalizerInterface, ContextAwareNormalizerInterface, DenormalizerAwareInterface, DenormalizerAwareTrait,
    NormalizerAwareInterface, NormalizerAwareTrait};
use Unilend\Entity\{Clients, Companies, Staff};

class StaffNormalizer implements ContextAwareNormalizerInterface, NormalizerAwareInterface, ContextAwareDenormalizerInterface, DenormalizerAwareInterface
{
    use NormalizerAwareTrait;
    use DenormalizerAwareTrait;

    private const NORMALIZER_ALREADY_CALLED   = 'STAFF_ATTRIBUTE_NORMALIZER_ALREADY_CALLED';
    private const DENORMALIZER_ALREADY_CALLED = 'STAFF_ATTRIBUTE_DENORMALIZER_ALREADY_CALLED';

    /** @var Security */
    private $security;
    /** @var IriConverterInterface */
    private $iriConverter;

    /**
     * @param Security              $security
     * @param IriConverterInterface $iriConverter
     */
    public function __construct(
        Security $security,
        IriConverterInterface $iriConverter
    ) {
        $this->security     = $security;
        $this->iriConverter = $iriConverter;
    }

    /**
     * {@inheritdoc}
     */
    public function supportsNormalization($data, $format = null, array $context = [])
    {
        if (isset($context[self::NORMALIZER_ALREADY_CALLED])) {
            return false;
        }

        return $data instanceof Staff;
    }

    /**
     * {@inheritdoc}
     */
    public function normalize($object, $format = null, array $context = [])
    {
        $context['groups'] = array_merge(
            $context['groups'] ?? [],
            $this->canAdministrate($object->getCompany()) ? [Staff::SERIALIZER_GROUP_ADMIN_READ, 'role:read'] : []
        );

        $context[self::NORMALIZER_ALREADY_CALLED] = true;

        return $this->normalizer->normalize($object, $format, $context);
    }

    /**
     * {@inheritdoc}
     */
    public function denormalize($data, $type, $format = null, array $context = [])
    {
        $company = $this->iriConverter->getItemFromIri($data['company'] ?? '');

        $context['groups'] = array_merge($context['groups'] ?? [], $this->canAdministrate($company) ? [Staff::SERIALIZER_GROUP_ADMIN_CREATE] : []);

        $context[self::DENORMALIZER_ALREADY_CALLED] = true;

        return $this->denormalizer->denormalize($data, $type, $format, $context);
    }

    /**
     * {@inheritdoc}
     */
    public function supportsDenormalization($data, $type, $format = null, array $context = []): bool
    {
        return !isset($context[self::DENORMALIZER_ALREADY_CALLED]) && Staff::class === $type;
    }

    /**
     * @param Companies $company
     *
     * @return bool
     */
    private function canAdministrate(Companies $company): bool
    {
        $client = $this->security->getUser();

        if (!$client instanceof Clients) {
            return false;
        }

        $connectedStaff = $client->getStaff();

        // TODO We might be able to add the condition int the voter
        return (
            $connectedStaff && $connectedStaff->hasRole(Staff::DUTY_STAFF_ADMIN) && $connectedStaff->getCompany() === $company
        ) || $this->security->isGranted(Clients::ROLE_ADMIN);
    }
}
