<?php

declare(strict_types=1);

namespace Unilend\Agency\Serializer\Normalizer;

use Exception;
use Symfony\Component\Security\Core\Security;
use Symfony\Component\Serializer\Normalizer\ContextAwareDenormalizerInterface;
use Symfony\Component\Serializer\Normalizer\DenormalizerAwareInterface;
use Symfony\Component\Serializer\Normalizer\DenormalizerAwareTrait;
use Symfony\Component\Serializer\Normalizer\ObjectToPopulateTrait;
use Unilend\Agency\Entity\AbstractProjectMember;
use Unilend\Agency\Entity\AgentMember;
use Unilend\Agency\Entity\BorrowerMember;
use Unilend\Agency\Entity\ParticipationMember;
use Unilend\Core\Repository\UserRepository;

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

    /**
     * {@inheritDoc}
     */
    public function supportsDenormalization($data, string $type, string $format = null, array $context = [])
    {
        return !isset($context[static::ALREADY_CALLED]) && \in_array($type, [AbstractProjectMember::class, BorrowerMember::class, AgentMember::class, ParticipationMember::class]);
    }

    /**
     * {@inheritDoc}
     *
     * @throws Exception
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
