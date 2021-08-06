<?php

declare(strict_types=1);

namespace Unilend\Syndication\Serializer\Normalizer\ProjectParticipationMember;

use ApiPlatform\Core\Api\IriConverterInterface;
use Symfony\Component\Security\Core\Security;
use Symfony\Component\Serializer\Normalizer\AbstractNormalizer;
use Symfony\Component\Serializer\Normalizer\ContextAwareDenormalizerInterface;
use Symfony\Component\Serializer\Normalizer\DenormalizerAwareInterface;
use Symfony\Component\Serializer\Normalizer\DenormalizerAwareTrait;
use Symfony\Component\Serializer\Normalizer\ObjectToPopulateTrait;
use Unilend\Core\Entity\Staff;
use Unilend\Core\Entity\User;
use Unilend\Syndication\Entity\ProjectParticipation;
use Unilend\Syndication\Entity\ProjectParticipationMember;

class ProjectParticipationMemberDenormalizer implements ContextAwareDenormalizerInterface, DenormalizerAwareInterface
{
    use DenormalizerAwareTrait;
    use ObjectToPopulateTrait;

    private const ALREADY_CALLED = 'PROJECT_PARTICIPATION_MEMBER_ATTRIBUTE_DENORMALIZER_ALREADY_CALLED';

    private Security $security;

    private IriConverterInterface $iriConverter;

    public function __construct(Security $security, IriConverterInterface $iriConverter)
    {
        $this->security     = $security;
        $this->iriConverter = $iriConverter;
    }

    public function supportsDenormalization($data, string $type, string $format = null, array $context = []): bool
    {
        return !isset($context[self::ALREADY_CALLED]) && ProjectParticipationMember::class === $type;
    }

    public function denormalize($data, string $type, string $format = null, array $context = [])
    {
        $context[self::ALREADY_CALLED] = true;

        /** @var ProjectParticipationMember $projectParticipationMember */
        $projectParticipationMember = $this->extractObjectToPopulate(ProjectParticipationMember::class, $context);

        // Disallow creating staff with other company than the participation
        if (isset($data['staff']) && \is_array($data['staff'])) {
            unset($data['staff']['company']);
        }

        /** @var User $user */
        $user = $this->security->getUser();

        /** @var ProjectParticipation $participation */
        $participation = $projectParticipationMember
            ? $projectParticipationMember->getProjectParticipation()
            : $context[AbstractNormalizer::DEFAULT_CONSTRUCTOR_ARGUMENTS][ProjectParticipationMember::class]['projectParticipation'] ?? null;

        if (null === $participation && isset($data['projectParticipation'])) {
            $participation = $this->iriConverter->getItemFromIri($data['projectParticipation'], [AbstractNormalizer::GROUPS => []]);
        }

        // permit to create staff from email (CALS-2023)
        if (null === $projectParticipationMember) {
            $context[AbstractNormalizer::GROUPS] = \array_merge($context[AbstractNormalizer::GROUPS] ?? [], ['role:write', 'staff:create', 'user:create']);
        }
        $context[AbstractNormalizer::DEFAULT_CONSTRUCTOR_ARGUMENTS][ProjectParticipationMember::class]['addedBy'] = $user->getCurrentStaff();
        $context[AbstractNormalizer::DEFAULT_CONSTRUCTOR_ARGUMENTS][Staff::class]['team']                         = $participation->getParticipant()->getRootTeam();

        return $this->denormalizer->denormalize($data, $type, $format, $context);
    }
}
