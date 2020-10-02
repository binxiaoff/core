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
use Unilend\Security\Voter\ProjectParticipationTrancheVoter;

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
        $projectParticipation = $projectParticipationTranche->getProjectParticipation();
        $participant = $projectParticipation->getParticipant();
        $project = $projectParticipation->getProject();
        $arranger = $project->getSubmitterCompany();

        $groups = [];

        if ($this->security->isGranted(ProjectParticipationTrancheVoter::ATTRIBUTE_ARRANGER_EDIT, $projectParticipationTranche)) {
            // The voter currently assert that we are in allocation step and the connected user entity is the arranger entity of the project
            $groups[] = ProjectParticipationTranche::SERIALIZER_GROUP_ARRANGER_WRITE;

            if ($arranger === $participant) {
                $groups[] = ProjectParticipationTranche::SERIALIZER_GROUP_INVITATION_REPLY_WRITE;
            }
        }

        if ($this->security->isGranted(ProjectParticipationTrancheVoter::ATTRIBUTE_PARTICIPATION_OWNER_EDIT, $projectParticipationTranche)) {
            $groups[] = ProjectParticipationTranche::SERIALIZER_GROUP_PARTICIPATION_OWNER_WRITE;
        }

        return $groups;
    }
}
