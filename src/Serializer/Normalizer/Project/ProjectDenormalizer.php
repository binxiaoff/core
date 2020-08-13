<?php

declare(strict_types=1);

namespace Unilend\Serializer\Normalizer\ProjectParticipation;

use Symfony\Component\Security\Core\Security;
use Symfony\Component\Serializer\Normalizer\{ContextAwareDenormalizerInterface, DenormalizerAwareInterface, DenormalizerAwareTrait, ObjectToPopulateTrait};
use Unilend\Entity\Project;
use Unilend\Entity\ProjectParticipation;
use Unilend\Security\Voter\ProjectParticipationVoter;

class ProjectDenormalizer implements ContextAwareDenormalizerInterface, DenormalizerAwareInterface
{
    use DenormalizerAwareTrait;
    use ObjectToPopulateTrait;

    private const ALREADY_CALLED = 'PROJECT_DENORMALIZER_ALREADY_CALLED';

    /** @var Security */
    private $security;

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
    public function supportsDenormalization($data, $type, $format = null, array $context = []): bool
    {
        return !isset($context[self::ALREADY_CALLED]) && Project::class === $type;
    }

    /**
     * {@inheritdoc}
     */
    public function denormalize($data, $type, $format = null, array $context = [])
    {
        $context[self::ALREADY_CALLED] = true;

        /** @var Project $project */
        $project = $this->denormalizer->denormalize($data, $type, $format, $context);
        if (false === $project->isSubParticipation() && $project->getRiskType() !== null) {
            $project->setRiskType(null);
        }

        return $project;
    }
}
