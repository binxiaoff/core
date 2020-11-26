<?php

declare(strict_types=1);

namespace Unilend\Serializer\Normalizer\ProjectParticipationTranche;

use Symfony\Component\Security\Core\Security;
use Symfony\Component\Serializer\Normalizer\{AbstractNormalizer,
    ContextAwareDenormalizerInterface,
    DenormalizerAwareInterface,
    DenormalizerAwareTrait,
    ObjectToPopulateTrait};
use Unilend\Core\Entity\Clients;
use Unilend\Entity\ProjectParticipationTranche;
use Unilend\Entity\ProjectStatus;
use Unilend\Service\Project\ProjectManager;
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
    /** @var ProjectManager */
    private ProjectManager $projectManager;

    /**
     * @param Security                    $security
     * @param ProjectManager              $projectManager
     * @param ProjectParticipationManager $projectParticipationManager
     */
    public function __construct(Security $security, ProjectManager $projectManager, ProjectParticipationManager $projectParticipationManager)
    {
        $this->security = $security;
        $this->projectParticipationManager = $projectParticipationManager;
        $this->projectManager = $projectManager;
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

            $currentStatus = $project->getCurrentStatus()->getStatus();
            switch ($currentStatus) {
                case ProjectStatus::STATUS_PARTICIPANT_REPLY:
                    if ($this->projectParticipationManager->isOwner($projectParticipation, $currentStaff)) {
                            $groups[] = 'projectParticipationTranche:owner:participantReply:write';
                    }
                    break;
                case ProjectStatus::STATUS_ALLOCATION:
                    if ($this->projectManager->isArranger($project, $currentStaff)) {
                        $groups[] = 'projectParticipationTranche:arranger:allocation:write';

                        if ($this->projectParticipationManager->isOwner($projectParticipation, $currentStaff)) {
                            $groups[] = 'projectParticipationTranche:arrangerOwner:allocation:write';
                        }
                    }
                    break;
            }
        }

        return $groups;
    }
}
