<?php

declare(strict_types=1);

namespace Unilend\Serializer\Normalizer\Staff;

use ApiPlatform\Core\Api\IriConverterInterface;
use Symfony\Component\Security\Core\Security;
use Symfony\Component\Serializer\Normalizer\{AbstractNormalizer,
    ContextAwareDenormalizerInterface,
    DenormalizerAwareInterface,
    DenormalizerAwareTrait,
    ObjectToPopulateTrait};
use Unilend\Entity\Company;
use Unilend\Entity\Staff;
use Unilend\Repository\ClientsRepository;
use Unilend\Security\Voter\StaffVoter;

class StaffDenormalizer implements ContextAwareDenormalizerInterface, DenormalizerAwareInterface
{
    use DenormalizerAwareTrait;
    use ObjectToPopulateTrait;

    private const ALREADY_CALLED = 'STAFF_ATTRIBUTE_DENORMALIZER_ALREADY_CALLED';

    /** @var Security */
    private Security $security;
    /** @var ClientsRepository */
    private ClientsRepository $clientsRepository;
    /** @var IriConverterInterface */
    private IriConverterInterface $iriConverter;

    /**
     * @param Security              $security
     * @param ClientsRepository     $clientsRepository
     * @param IriConverterInterface $iriConverter
     */
    public function __construct(Security $security, ClientsRepository $clientsRepository, IriConverterInterface $iriConverter)
    {
        $this->security = $security;
        $this->clientsRepository = $clientsRepository;
        $this->iriConverter = $iriConverter;
    }

    /**
     * {@inheritdoc}
     */
    public function denormalize($data, $type, ?string $format = null, array $context = [])
    {
        $context[self::ALREADY_CALLED] = true;

        /** @var Staff $staff */
        $staff = $this->extractObjectToPopulate(Staff::class, $context);

        if ($staff) {
            $context[AbstractNormalizer::GROUPS] = array_merge($context[AbstractNormalizer::GROUPS] ?? [], $this->getAdditionalGroups($staff));
        }

        $emailClient = $data['client']['email'] ?? null;

        // add existing client found by his email or create him
        if ($emailClient && $client = $this->clientsRepository->findOneBy(['email' => $emailClient])) {
            $data['client'] = $this->iriConverter->getIriFromItem($client);
        }

        /** @var Company $company */
        $company = $this->iriConverter->getItemFromIri($data['company']);

        if (false === $company->isCAGMember()) {
            $data['roles'] = [Staff::DUTY_STAFF_OPERATOR];
        }

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
