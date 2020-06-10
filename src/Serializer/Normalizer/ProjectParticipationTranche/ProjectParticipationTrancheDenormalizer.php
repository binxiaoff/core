<?php

declare(strict_types=1);

namespace Unilend\Serializer\Normalizer\ProjectParticipationTranche;

use Symfony\Component\Security\Core\Security;
use Symfony\Component\Serializer\Normalizer\{ContextAwareDenormalizerInterface, DenormalizerAwareInterface, DenormalizerAwareTrait, ObjectToPopulateTrait};
use Unilend\Entity\{Clients, ProjectParticipationTranche};
use Unilend\Service\ProjectParticipation\ProjectParticipationManager;

class ProjectParticipationTrancheDenormalizer implements ContextAwareDenormalizerInterface, DenormalizerAwareInterface
{
    use DenormalizerAwareTrait;
    use ObjectToPopulateTrait;

    private const ALREADY_CALLED = 'PROJECT_PARTICIPATION_TRANCHE_ATTRIBUTE_DENORMALIZER_ALREADY_CALLED';

    /** @var Security */
    private $security;
    /** @var ProjectParticipationManager */
    private $projectParticipationManager;

    /**
     * @param Security                    $security
     * @param ProjectParticipationManager $projectParticipationManager
     */
    public function __construct(Security $security, ProjectParticipationManager $projectParticipationManager)
    {
        $this->security                    = $security;
        $this->projectParticipationManager = $projectParticipationManager;
    }

    /**
     * {@inheritdoc}
     */
    public function supportsDenormalization($data, $type, $format = null, array $context = []): bool
    {
        return !isset($context[self::ALREADY_CALLED]) && ProjectParticipationTranche::class === $type;
    }

    /**
     * {@inheritdoc}
     */
    public function denormalize($data, $type, $format = null, array $context = [])
    {
        /** @var ProjectParticipationTranche $projectParticipationTranche */
        $projectParticipationTranche = $this->extractObjectToPopulate(ProjectParticipationTranche::class, $context);
        if ($projectParticipationTranche) {
            $context['groups'] = array_merge($context['groups'] ?? [], $this->getAdditionalDenormalizerGroups($projectParticipationTranche));
        }

        $context[self::ALREADY_CALLED] = true;

        return $this->denormalizer->denormalize($data, $type, $format, $context);
    }

    /**
     * @param ProjectParticipationTranche $projectParticipationTranche
     *
     * @return array
     */
    private function getAdditionalDenormalizerGroups(ProjectParticipationTranche $projectParticipationTranche): array
    {
        $client = $this->security->getUser();
        $staff  = $client instanceof Clients ? $client->getCurrentStaff() : null;

        if (null === $staff) {
            return [];
        }

        $groups = [];

        if (
            $this->security->isGranted('ROLE_ADMIN')
            || $projectParticipationTranche->getProjectParticipation()->getProject()->getSubmitterCompany() === $staff->getCompany()
        ) {
            $groups[] = ProjectParticipationTranche::SERIALIZER_GROUP_ARRANGER_WRITE;
        }

        if (
            $this->security->isGranted('ROLE_ADMIN')
            || $this->projectParticipationManager->isParticipationOwner($staff, $projectParticipationTranche->getProjectParticipation())
        ) {
            $groups[] = ProjectParticipationTranche::SERIALIZER_GROUP_PARTICIPANT_OWNER_WRITE;
        }

        return $groups;
    }
}
