<?php

declare(strict_types=1);

namespace Unilend\Serializer\Normalizer\ProjectParticipationTranche;

use Symfony\Component\Security\Core\Security;
use Symfony\Component\Serializer\Normalizer\{AbstractNormalizer,
    ContextAwareDenormalizerInterface,
    DenormalizerAwareInterface,
    DenormalizerAwareTrait,
    ObjectToPopulateTrait};
use Unilend\Entity\Clients;
use Unilend\Entity\ProjectParticipation;
use Unilend\Entity\ProjectParticipationTranche;
use Unilend\Entity\ProjectStatus;
use Unilend\Security\Voter\ProjectParticipationTrancheVoter;
use Unilend\Security\Voter\ProjectParticipationVoter;

class ProjectParticipationTrancheDenormalizer implements ContextAwareDenormalizerInterface, DenormalizerAwareInterface
{
    use DenormalizerAwareTrait;
    use ObjectToPopulateTrait;

    private const ALREADY_CALLED = 'PROJECT_PARTICIPATION_TRANCHE_ATTRIBUTE_DENORMALIZER_ALREADY_CALLED';

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
    public function supportsDenormalization($data, $type, string $format = null, array $context = []): bool
    {
        return !isset($context[self::ALREADY_CALLED]) && ProjectParticipationTranche::class === $type;
    }

    /**
     * {@inheritdoc}
     */
    public function denormalize($data, $type, string $format = null, array $context = [])
    {
        /** @var ProjectParticipationTranche $projectParticipationTranche */
        $projectParticipationTranche = $this->extractObjectToPopulate(ProjectParticipationTranche::class, $context);
        if ($projectParticipationTranche) {
            $context[AbstractNormalizer::GROUPS] = array_merge($context[AbstractNormalizer::GROUPS] ?? [], $this->getAdditionalDenormalizerGroups($projectParticipationTranche));
        }

        $context[self::ALREADY_CALLED] = true;

        /** @var Clients $user */
        $user = $this->security->getUser();

        $context[AbstractNormalizer::DEFAULT_CONSTRUCTOR_ARGUMENTS][ProjectParticipationTranche::class]['addedBy'] = $user->getCurrentStaff();

        return $this->denormalizer->denormalize($data, $type, $format, $context);
    }

    /**
     * @param ProjectParticipationTranche $projectParticipationTranche
     *
     * @return array
     */
    private function getAdditionalDenormalizerGroups(ProjectParticipationTranche $projectParticipationTranche): array
    {
        $project = $projectParticipationTranche->getProjectParticipation()->getProject();

        $label = $project->getCurrentStatus()->getHumanLabel();

        $groups = [];

        if ($this->security->isGranted(ProjectParticipationTrancheVoter::ATTRIBUTE_OWNER, $projectParticipationTranche)) {
            $groups[] = 'projectParticipationTranche:owner:write';
            $groups[] = "projectParticipationTranche:owner:$label:write";
        }

        if ($this->security->isGranted(ProjectParticipationTrancheVoter::ATTRIBUTE_ARRANGER, $projectParticipationTranche)) {
            $groups[] = 'projectParticipationTranche:arranger:write';
            $groups[] = "projectParticipationTranche:arranger:$label:write";
        }

        return $groups;
    }
}
