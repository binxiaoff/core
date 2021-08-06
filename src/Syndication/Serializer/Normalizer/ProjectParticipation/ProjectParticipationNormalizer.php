<?php

declare(strict_types=1);

namespace Unilend\Syndication\Serializer\Normalizer\ProjectParticipation;

use Symfony\Component\Security\Core\Security;
use Symfony\Component\Serializer\Normalizer\AbstractNormalizer;
use Symfony\Component\Serializer\Normalizer\ContextAwareNormalizerInterface;
use Symfony\Component\Serializer\Normalizer\NormalizerAwareInterface;
use Symfony\Component\Serializer\Normalizer\NormalizerAwareTrait;
use Unilend\Syndication\Entity\ProjectParticipation;
use Unilend\Syndication\Entity\ProjectParticipationTranche;
use Unilend\Syndication\Security\Voter\ProjectParticipationVoter;

class ProjectParticipationNormalizer implements ContextAwareNormalizerInterface, NormalizerAwareInterface
{
    use NormalizerAwareTrait;

    private const ALREADY_CALLED = 'PROJECT_PARTICIPATION_ATTRIBUTE_NORMALIZER_ALREADY_CALLED';

    private Security $security;

    public function __construct(Security $security)
    {
        $this->security = $security;
    }

    public function supportsNormalization($data, string $format = null, array $context = []): bool
    {
        if (isset($context[self::ALREADY_CALLED])) {
            return false;
        }

        return $data instanceof ProjectParticipation;
    }

    public function normalize($object, string $format = null, array $context = [])
    {
        $context[AbstractNormalizer::GROUPS] = \array_merge($context[AbstractNormalizer::GROUPS] ?? [], $this->getAdditionalNormalizerGroups($object));

        $context[self::ALREADY_CALLED] = true;

        return $this->normalizer->normalize($object, $format, $context);
    }

    private function getAdditionalNormalizerGroups(ProjectParticipation $projectParticipation): array
    {
        $group = [];

        if ($this->security->isGranted(ProjectParticipationVoter::ATTRIBUTE_ADMIN_VIEW, $projectParticipation)) {
            $group[] = ProjectParticipation::SERIALIZER_GROUP_ADMIN_READ;
        }

        if ($this->security->isGranted(ProjectParticipationVoter::ATTRIBUTE_SENSITIVE_VIEW, $projectParticipation)) {
            $group = \array_merge($group, [ProjectParticipation::SERIALIZER_GROUP_SENSITIVE_READ, ProjectParticipationTranche::SERIALIZER_GROUP_SENSITIVE_READ]);
        }

        return $group;
    }
}
