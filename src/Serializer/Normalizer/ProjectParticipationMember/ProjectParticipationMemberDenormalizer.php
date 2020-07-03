<?php

declare(strict_types=1);

namespace Unilend\Serializer\Normalizer\ProjectParticipationMember;

use Symfony\Component\Security\Core\Security;
use Symfony\Component\Serializer\Normalizer\{ContextAwareDenormalizerInterface, DenormalizerAwareInterface, DenormalizerAwareTrait, ObjectToPopulateTrait};
use Unilend\Entity\ProjectParticipationMember;
use Unilend\Security\Voter\ProjectParticipationMemberVoter;

class ProjectParticipationMemberDenormalizer implements ContextAwareDenormalizerInterface, DenormalizerAwareInterface
{
    use DenormalizerAwareTrait;
    use ObjectToPopulateTrait;

    private const ALREADY_CALLED = 'PROJECT_PARTICIPATION_MEMBER_ATTRIBUTE_DENORMALIZER_ALREADY_CALLED';

    /** @var Security */
    private Security $security;

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
    public function supportsDenormalization($data, string $type, string $format = null, array $context = []): bool
    {
        return !isset($context[self::ALREADY_CALLED]) && ProjectParticipationMember::class === $type;
    }

    /**
     * {@inheritdoc}
     */
    public function denormalize($data, string $type, string $format = null, array $context = [])
    {
        /** @var ProjectParticipationMember $projectParticipationMember */
        $projectParticipationMember = $this->extractObjectToPopulate(ProjectParticipationMember::class, $context);

        if ($projectParticipationMember) {
            $context['groups'] = array_merge($context['groups'] ?? [], $this->getAdditionalDenormalizerGroups($projectParticipationMember));
        }

        $context[self::ALREADY_CALLED] = true;

        return $this->denormalizer->denormalize($data, $type, $format, $context);
    }

    /**
     * @param ProjectParticipationMember $projectParticipationMember
     *
     * @return array
     */
    private function getAdditionalDenormalizerGroups(ProjectParticipationMember $projectParticipationMember): array
    {
        $groups = [];

        if ($this->security->isGranted(ProjectParticipationMemberVoter::ATTRIBUTE_ACCEPT_NDA, $projectParticipationMember)) {
            $groups[] = ProjectParticipationMember::SERIALIZER_GROUP_PROJECT_PARTICIPATION_MEMBER_OWNER_WRITE;
        }

        return $groups;
    }
}
