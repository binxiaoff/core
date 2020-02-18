<?php

declare(strict_types=1);

namespace Unilend\Serializer\Normalizer\Staff;

use ApiPlatform\Core\Api\IriConverterInterface;
use Symfony\Component\Security\Core\Security;
use Symfony\Component\Serializer\Normalizer\{ContextAwareDenormalizerInterface, ContextAwareNormalizerInterface, DenormalizerAwareInterface, DenormalizerAwareTrait,
    NormalizerAwareInterface, NormalizerAwareTrait};
use Unilend\Entity\{Clients, Company, Staff};

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
            $this->canAdminNormalize($object->getCompany()) ? [Staff::SERIALIZER_GROUP_ADMIN_READ, 'role:read'] : []
        );

        $context[self::NORMALIZER_ALREADY_CALLED] = true;

        return $this->normalizer->normalize($object, $format, $context);
    }

    /**
     * {@inheritdoc}
     */
    public function denormalize($data, $type, $format = null, array $context = [])
    {
        // In patch is only allowed for manager and coordinator so we don't need to check
        if ('post' === ($context['collection_operation_name'] ?? '') && isset($data['company'])) {
            /** @var Company $company */
            $company = $this->iriConverter->getItemFromIri($data['company']);

            $context['groups'] = array_merge($context['groups'] ?? [], $this->canAdminDenormalize($company) ? [Staff::SERIALIZER_GROUP_ADMIN_CREATE] : []);
        }

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
     * @param Company $company
     *
     * @return bool
     */
    private function canAdminNormalize(Company $company): bool
    {
        $connectedStaff = $this->getConnectedStaff();

        // TODO We might be able to add the condition into the voter
        // @see https://lafabriquebyca.atlassian.net/browse/CALS-832
        return ($connectedStaff && $connectedStaff->getCompany() === $company) || $this->security->isGranted(Clients::ROLE_ADMIN);
    }

    /**
     * @param Company $company
     *
     * @return bool
     */
    private function canAdminDenormalize(Company $company): bool
    {
        $connectedStaff = $this->getConnectedStaff();

        // TODO We might be able to add the condition into the voter
        // @see https://lafabriquebyca.atlassian.net/browse/CALS-832
        return ($connectedStaff && $this->canAdminNormalize($company) && ($connectedStaff->isAdmin() || $connectedStaff->isManager()))
            || $this->security->isGranted(Clients::ROLE_ADMIN);
    }

    /**
     * @return Staff|null
     */
    private function getConnectedStaff(): ?Staff
    {
        $client = $this->security->getUser();

        if (!$client instanceof Clients) {
            return null;
        }

        return $client->getStaff();
    }
}
