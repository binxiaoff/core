<?php

declare(strict_types=1);

namespace Unilend\Serializer\Normalizer\Staff;

use ApiPlatform\Core\Api\IriConverterInterface;
use Doctrine\ORM\NonUniqueResultException;
use Symfony\Component\Security\Core\Security;
use Symfony\Component\Serializer\Normalizer\{AbstractNormalizer,
    ContextAwareDenormalizerInterface,
    DenormalizerAwareInterface,
    DenormalizerAwareTrait,
    ObjectToPopulateTrait};
use Unilend\Entity\Clients;
use Unilend\Entity\Company;
use Unilend\Entity\Staff;
use Unilend\Exception\Staff\StaffNotFoundException;
use Unilend\Repository\ClientsRepository;
use Unilend\Repository\StaffRepository;
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
    /** @var StaffRepository */
    private StaffRepository $staffRepository;

    private static array $registeredEmails = [];

    /**
     * @param Security              $security
     * @param ClientsRepository     $clientsRepository
     * @param StaffRepository       $staffRepository
     * @param IriConverterInterface $iriConverter
     */
    public function __construct(Security $security, ClientsRepository $clientsRepository, StaffRepository $staffRepository, IriConverterInterface $iriConverter)
    {
        $this->security          = $security;
        $this->clientsRepository = $clientsRepository;
        $this->iriConverter      = $iriConverter;
        $this->staffRepository   = $staffRepository;
    }

    /**
     * {@inheritdoc}
     * @throws NonUniqueResultException
     */
    public function denormalize($data, $type, ?string $format = null, array $context = [])
    {
        $context[self::ALREADY_CALLED] = true;

        /** @var Staff $staff */
        $staff = $this->extractObjectToPopulate(Staff::class, $context);

        if ($staff) {
            $context[AbstractNormalizer::GROUPS] = array_merge($context[AbstractNormalizer::GROUPS] ?? [], $this->getAdditionalGroups($staff));
        }

        $user = $this->security->getUser();

        $context[AbstractNormalizer::DEFAULT_CONSTRUCTOR_ARGUMENTS][Staff::class]['addedBy'] = $user instanceof Clients ? $user->getCurrentStaff() : null;

        // get constructor company if provided (when create staff from ProjectParticipationMemberDenormalizer)
        /** @var Company $company */
        $company = $context[AbstractNormalizer::DEFAULT_CONSTRUCTOR_ARGUMENTS][Staff::class]['company'] ?? null;

        // else, get from request
        if (null === $company && \is_string($data['company'])) {
            $company = isset($data['company']) ? $this->iriConverter->getItemFromIri($data['company']) : null;
        }

        $emailClient = $data['client']['email'] ?? null;

        // External bank mandatory role and
        if (false === $company->isCAGMember()) {
            $data['roles'] = [Staff::DUTY_STAFF_OPERATOR];
            $data['marketSegment'] = [];
        }

        if (null === $staff && $emailClient && $company) {
            unset($data['client']);
            $staff         = $this->staffRepository->findOneByClientEmailAndCompany((string) $emailClient, $company);
            $context[AbstractNormalizer::OBJECT_TO_POPULATE] = $staff;

            if (null === $staff) {
                $existingClient  = self::$registeredEmails[$emailClient] ?? $this->clientsRepository->findOneBy(['email' => $emailClient]);
                $context[AbstractNormalizer::DEFAULT_CONSTRUCTOR_ARGUMENTS][Staff::class]['client'] = $existingClient;

                if (null === $existingClient) {
                    // retrieve client from his email or create it
                    $context[AbstractNormalizer::GROUPS] = $context[AbstractNormalizer::GROUPS] ?? [];
                    $context[AbstractNormalizer::GROUPS][] = 'client:create';
                    $data['client']['email'] = $emailClient;
                }
            }
        }

        /** @var Staff $denormalized */
        $denormalized = $this->denormalizer->denormalize($data, $type, $format, $context);

        self::$registeredEmails[$emailClient] = $denormalized->getClient();

        return $denormalized;
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
