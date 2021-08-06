<?php

declare(strict_types=1);

namespace Unilend\Syndication\Serializer\Normalizer\ProjectParticipationTranche;

use Symfony\Component\Security\Core\Security;
use Symfony\Component\Serializer\Normalizer\AbstractNormalizer;
use Symfony\Component\Serializer\Normalizer\ContextAwareNormalizerInterface;
use Symfony\Component\Serializer\Normalizer\NormalizerAwareInterface;
use Symfony\Component\Serializer\Normalizer\NormalizerAwareTrait;
use Unilend\Syndication\Entity\ProjectParticipationTranche;
use Unilend\Syndication\Security\Voter\ProjectParticipationTrancheVoter;

class ProjectParticipationTrancheNormalizer implements ContextAwareNormalizerInterface, NormalizerAwareInterface
{
    use NormalizerAwareTrait;

    private const ALREADY_CALLED = 'PROJECT_PARTICIPATION_TRANCHE_ATTRIBUTE_NORMALIZER_ALREADY_CALLED';

    /** @var Security */
    private $security;

    public function __construct(Security $security)
    {
        $this->security = $security;
    }

    /**
     * {@inheritdoc}
     */
    public function supportsNormalization($data, string $format = null, array $context = []): bool
    {
        if (isset($context[self::ALREADY_CALLED])) {
            return false;
        }

        return $data instanceof ProjectParticipationTranche;
    }

    /**
     * {@inheritdoc}
     */
    public function normalize($object, string $format = null, array $context = [])
    {
        $context[AbstractNormalizer::GROUPS] = \array_merge($context[AbstractNormalizer::GROUPS] ?? [], $this->getAdditionalNormalizerGroups($object));

        $context[self::ALREADY_CALLED] = true;

        return $this->normalizer->normalize($object, $format, $context);
    }

    private function getAdditionalNormalizerGroups(ProjectParticipationTranche $projectParticipationTranche): array
    {
        if ($this->security->isGranted(ProjectParticipationTrancheVoter::ATTRIBUTE_SENSITIVE_VIEW, $projectParticipationTranche)) {
            return [ProjectParticipationTranche::SERIALIZER_GROUP_SENSITIVE_READ];
        }

        return [];
    }
}
