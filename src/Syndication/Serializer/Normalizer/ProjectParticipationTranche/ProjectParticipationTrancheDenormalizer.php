<?php

declare(strict_types=1);

namespace Unilend\Syndication\Serializer\Normalizer\ProjectParticipationTranche;

use Symfony\Component\Security\Core\Security;
use Symfony\Component\Serializer\Normalizer\{AbstractNormalizer,
    ContextAwareDenormalizerInterface,
    DenormalizerAwareInterface,
    DenormalizerAwareTrait,
    ObjectToPopulateTrait};
use Unilend\Core\Entity\User;
use Unilend\Syndication\Entity\ProjectParticipationMember;
use Unilend\Syndication\Entity\ProjectParticipationTranche;
use Unilend\Syndication\Entity\ProjectStatus;
use Unilend\Syndication\Security\Voter\ProjectParticipationTrancheVoter;
use Unilend\Syndication\Security\Voter\ProjectParticipationVoter;
use Unilend\Syndication\Security\Voter\ProjectVoter;
use Unilend\Syndication\Service\Project\ProjectManager;
use Unilend\Syndication\Service\ProjectParticipation\ProjectParticipationManager;

class ProjectParticipationTrancheDenormalizer implements ContextAwareDenormalizerInterface, DenormalizerAwareInterface
{
    use DenormalizerAwareTrait;
    use ObjectToPopulateTrait;

    private const ALREADY_CALLED = 'PROJECT_PARTICIPATION_TRANCHE_ATTRIBUTE_DENORMALIZER_ALREADY_CALLED';

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

        /** @var User $user */
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

        $currentStaff = $currentUser instanceof User ? $currentUser->getCurrentStaff() : null;

        if ($currentStaff) {
            $project = $projectParticipation->getProject();

            $currentStatus = $project->getCurrentStatus()->getStatus();
            switch ($currentStatus) {
                case ProjectStatus::STATUS_PARTICIPANT_REPLY:
                    if ($this->security->isGranted(ProjectParticipationTrancheVoter::ATTRIBUTE_EDIT, $projectParticipationTranche)) {
                            $groups[] = 'projectParticipationTranche:owner:participantReply:write';
                    }
                    break;
                case ProjectStatus::STATUS_ALLOCATION:
                    if ($this->security->isGranted(ProjectVoter::ATTRIBUTE_EDIT, $project)) {
                        $groups[] = 'projectParticipationTranche:arranger:allocation:write';

                        if ($projectParticipation->isArrangerParticipation()) {
                            $groups[] = 'projectParticipationTranche:arrangerOwner:allocation:write';
                        }
                    }
                    break;
            }
        }

        return $groups;
    }
}
