<?php

declare(strict_types=1);

namespace Unilend\Core\Serializer\Normalizer\Staff;

use ApiPlatform\Core\Api\IriConverterInterface;
use Doctrine\ORM\NonUniqueResultException;
use Symfony\Component\Security\Core\Security;
use Symfony\Component\Serializer\Normalizer\{AbstractNormalizer,
    ContextAwareDenormalizerInterface,
    DenormalizerAwareInterface,
    DenormalizerAwareTrait,
    ObjectToPopulateTrait};
use Unilend\Core\Entity\Staff;
use Unilend\Core\Entity\User;
use Unilend\Core\Repository\StaffRepository;
use Unilend\Core\Repository\UserRepository;
use Unilend\Core\Security\Voter\StaffVoter;

class StaffDenormalizer implements ContextAwareDenormalizerInterface, DenormalizerAwareInterface
{
    use DenormalizerAwareTrait;
    use ObjectToPopulateTrait;

    private const ALREADY_CALLED = 'STAFF_ATTRIBUTE_DENORMALIZER_ALREADY_CALLED';

    /** @var Security */
    private Security $security;
    /** @var UserRepository */
    private UserRepository $userRepository;
    /** @var IriConverterInterface */
    private IriConverterInterface $iriConverter;
    /** @var StaffRepository */
    private StaffRepository $staffRepository;

    private static array $registeredEmails = [];

    /**
     * @param Security              $security
     * @param UserRepository        $userRepository
     * @param StaffRepository       $staffRepository
     * @param IriConverterInterface $iriConverter
     */
    public function __construct(Security $security, UserRepository $userRepository, StaffRepository $staffRepository, IriConverterInterface $iriConverter)
    {
        $this->security        = $security;
        $this->userRepository  = $userRepository;
        $this->iriConverter    = $iriConverter;
        $this->staffRepository = $staffRepository;
    }

    /**
     * {@inheritdoc}
     * @throws NonUniqueResultException
     */
    public function denormalize($data, $type, ?string $format = null, array $context = [])
    {
        $context[self::ALREADY_CALLED] = true;

        /** @var User $user */
        $user = $this->security->getUser();

        $context[AbstractNormalizer::DEFAULT_CONSTRUCTOR_ARGUMENTS][Staff::class]['addedBy'] = $user instanceof User ? $user->getCurrentStaff() : null;

        /** @var Staff $staff */
        $staff = $this->extractObjectToPopulate(Staff::class, $context);

        $company = null;

        // Try to get from staff
        if ($staff) {
            $company = $staff->getCompany();
        }

        // OR get constructor company if provided (when create staff from ProjectParticipationMemberDenormalizer)
        if (null === $company && isset($context[AbstractNormalizer::DEFAULT_CONSTRUCTOR_ARGUMENTS][Staff::class]['company'])) {
            $company = $context[AbstractNormalizer::DEFAULT_CONSTRUCTOR_ARGUMENTS][Staff::class]['company'];
        }

        // else, get from request
        if (null === $company && (isset($data['company']) && \is_string($data['company']))) {
            $company = $this->iriConverter->getItemFromIri($data['company']);
        }

        $email = $data['user']['email'] ?? null;

        if (null === $staff && $email && $company) {
            unset($data['user']);
            $staff         = $this->staffRepository->findOneByEmailAndCompany((string) $email, $company);
            $context[AbstractNormalizer::OBJECT_TO_POPULATE] = $staff;

            if (null === $staff) {
                $existingUser  = self::$registeredEmails[$email] ?? $this->userRepository->findOneBy(['email' => $email]);
                $context[AbstractNormalizer::DEFAULT_CONSTRUCTOR_ARGUMENTS][Staff::class]['user'] = $existingUser;

                if (null === $existingUser) {
                    // retrieve user from his email or create it
                    $context[AbstractNormalizer::GROUPS] = $context[AbstractNormalizer::GROUPS] ?? [];
                    $context[AbstractNormalizer::GROUPS][] = 'user:create';
                    $data['user']['email'] = $email;
                }
            }
        }

        /** @var Staff $denormalized */
        $denormalized = $this->denormalizer->denormalize($data, $type, $format, $context);

        self::$registeredEmails[$email] = $denormalized->getUser();

        return $denormalized;
    }

    /**
     * {@inheritdoc}
     */
    public function supportsDenormalization($data, $type, ?string $format = null, array $context = []): bool
    {
        return !isset($context[self::ALREADY_CALLED]) && Staff::class === $type;
    }
}
