<?php

declare(strict_types=1);

namespace KLS\Agency\Serializer\Normalizer;

use Exception;
use KLS\Agency\Entity\AbstractProjectMember;
use KLS\Agency\Entity\AgentMember;
use KLS\Agency\Entity\BorrowerMember;
use KLS\Agency\Entity\ParticipationMember;
use KLS\Core\Repository\UserRepository;
use Symfony\Component\Security\Core\Security;
use Symfony\Component\Serializer\Exception\ExceptionInterface;
use Symfony\Component\Serializer\Normalizer\ContextAwareDenormalizerInterface;
use Symfony\Component\Serializer\Normalizer\DenormalizerAwareInterface;
use Symfony\Component\Serializer\Normalizer\DenormalizerAwareTrait;
use Symfony\Component\Serializer\Normalizer\ObjectToPopulateTrait;

class AbstractProjectMemberNormalizer implements ContextAwareDenormalizerInterface, DenormalizerAwareInterface
{
    use ObjectToPopulateTrait;
    use DenormalizerAwareTrait;

    private const ALREADY_CALLED = __CLASS__ . '_ALREADY_CALLED';

    private Security $security;
    private UserRepository $userRepository;

    public function __construct(
        Security $security,
        UserRepository $userRepository
    ) {
        $this->security       = $security;
        $this->userRepository = $userRepository;
    }

    public function supportsDenormalization($data, string $type, string $format = null, array $context = [])
    {
        return !isset($context[static::ALREADY_CALLED]) && \in_array($type, [AbstractProjectMember::class, BorrowerMember::class, AgentMember::class, ParticipationMember::class]);
    }

    /**
     * @param mixed $data
     *
     * @throws Exception
     * @throws ExceptionInterface
     */
    public function denormalize($data, string $type, string $format = null, array $context = [])
    {
        $context[static::ALREADY_CALLED] = true;

        $archived = $data['archived'] ?? false;
        unset($data['archived']);

        /** @var AbstractProjectMember $projectMember */
        $projectMember = $this->denormalizer->denormalize($data, $type, $format, $context);

        $user = $this->security->getUser();

        $user = $user ? $this->userRepository->findOneBy(['email' => $user->getUsername()]) : null;

        if (
            $archived
            && $projectMember
            && (false === $projectMember->isArchived())
            && $user !== $projectMember->getUser()
        ) {
            $projectMember->archive($user);
        }

        return $projectMember;
    }
}
