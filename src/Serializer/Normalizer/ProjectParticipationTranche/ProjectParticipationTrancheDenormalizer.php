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
use Unilend\Entity\ProjectParticipationTranche;
use Unilend\Service\ProjectParticipation\ProjectParticipationManager;

class ProjectParticipationTrancheDenormalizer implements ContextAwareDenormalizerInterface, DenormalizerAwareInterface
{
    use DenormalizerAwareTrait;
    use ObjectToPopulateTrait;

    private const ALREADY_CALLED = 'PROJECT_PARTICIPATION_TRANCHE_ATTRIBUTE_DENORMALIZER_ALREADY_CALLED';

    /** @var Security */
    private Security $security;
    /** @var ProjectParticipationManager */
    private ProjectParticipationManager $projectParticipationManager;

    /**
     * @param Security                    $security
     * @param ProjectParticipationManager $projectParticipationManager
     */
    public function __construct(Security $security, ProjectParticipationManager $projectParticipationManager)
    {
        $this->security = $security;
        $this->projectParticipationManager = $projectParticipationManager;
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
        $projectParticipation = $projectParticipationTranche->getProjectParticipation();

        $groups = [];

        $currentUser = $this->security->getUser();

        $currentStaff = $currentUser instanceof Clients ? $currentUser->getCurrentStaff() : null;

        if ($currentStaff) {
            $project = $projectParticipation->getProject();

            $label = $project->getCurrentStatus()->getHumanLabel();


            if ($this->projectParticipationManager->isParticipationOwner($projectParticipation, $currentStaff)) {
                $groups[] = 'projectParticipationTranche:owner:write';
                $groups[] = "projectParticipationTranche:owner:$label:write";
            }

            if ($this->projectParticipationManager->isParticipationArranger($projectParticipation, $currentStaff)) {
                $groups[] = 'projectParticipationTranche:arranger:write';
                $groups[] = "projectParticipationTranche:arranger:$label:write";
            }
        }

        return $groups;
    }
}
