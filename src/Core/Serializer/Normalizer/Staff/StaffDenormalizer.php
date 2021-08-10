<?php

declare(strict_types=1);

namespace KLS\Core\Serializer\Normalizer\Staff;

use ApiPlatform\Core\Api\IriConverterInterface;
use Doctrine\ORM\NonUniqueResultException;
use KLS\Core\Entity\Staff;
use KLS\Core\Entity\Team;
use KLS\Core\Entity\User;
use KLS\Core\Repository\StaffRepository;
use KLS\Core\Repository\UserRepository;
use Symfony\Component\Security\Core\Security;
use Symfony\Component\Serializer\Normalizer\AbstractNormalizer;
use Symfony\Component\Serializer\Normalizer\ContextAwareDenormalizerInterface;
use Symfony\Component\Serializer\Normalizer\DenormalizerAwareInterface;
use Symfony\Component\Serializer\Normalizer\DenormalizerAwareTrait;
use Symfony\Component\Serializer\Normalizer\ObjectToPopulateTrait;

class StaffDenormalizer implements ContextAwareDenormalizerInterface, DenormalizerAwareInterface
{
    use DenormalizerAwareTrait;
    use ObjectToPopulateTrait;

    private const ALREADY_CALLED = 'STAFF_ATTRIBUTE_DENORMALIZER_ALREADY_CALLED';

    private Security $security;
    private UserRepository $userRepository;
    private IriConverterInterface $iriConverter;
    private StaffRepository $staffRepository;
    private static array $registeredEmails = [];

    public function __construct(Security $security, UserRepository $userRepository, StaffRepository $staffRepository, IriConverterInterface $iriConverter)
    {
        $this->security        = $security;
        $this->userRepository  = $userRepository;
        $this->iriConverter    = $iriConverter;
        $this->staffRepository = $staffRepository;
    }

    /**
     * @param mixed $data
     * @param mixed $type
     *
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
        if (null === $company && isset($context[AbstractNormalizer::DEFAULT_CONSTRUCTOR_ARGUMENTS][Staff::class]['team'])) {
            /** @var Team $team */
            $team    = $context[AbstractNormalizer::DEFAULT_CONSTRUCTOR_ARGUMENTS][Staff::class]['team'];
            $company = $team->getCompany();
        }

        // else, get from request
        if (null === $company && (isset($data['company']) && \is_string($data['company']))) {
            $company = $this->iriConverter->getItemFromIri($data['company']);
        }

        if (null === $company && (isset($data['team']) && \is_string($data['team']))) {
            $team    = $this->iriConverter->getItemFromIri($data['team']);
            $company = $team->getCompany();
        }

        $email = $data['user']['email'] ?? null;

        if (null === $staff && $email && $company) {
            unset($data['user']);
            $staff                                           = $this->staffRepository->findOneByEmailAndCompany((string) $email, $company);
            $context[AbstractNormalizer::OBJECT_TO_POPULATE] = $staff;

            if (null === $staff) {
                $existingUser                                                                     = self::$registeredEmails[$email] ?? $this->userRepository->findOneBy(['email' => $email]);
                $context[AbstractNormalizer::DEFAULT_CONSTRUCTOR_ARGUMENTS][Staff::class]['user'] = $existingUser;

                if (null === $existingUser) {
                    // retrieve user from his email or create it
                    $context[AbstractNormalizer::GROUPS]   = $context[AbstractNormalizer::GROUPS] ?? [];
                    $context[AbstractNormalizer::GROUPS][] = 'user:create';
                    $data['user']['email']                 = $email;
                }
            }
        }

        /** @var Staff $denormalized */
        $denormalized = $this->denormalizer->denormalize($data, $type, $format, $context);

        self::$registeredEmails[$email] = $denormalized->getUser();

        return $denormalized;
    }

    public function supportsDenormalization($data, $type, ?string $format = null, array $context = []): bool
    {
        return !isset($context[self::ALREADY_CALLED]) && Staff::class === $type;
    }
}
