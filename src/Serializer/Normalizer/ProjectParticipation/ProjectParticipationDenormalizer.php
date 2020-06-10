<?php

declare(strict_types=1);

namespace Unilend\Serializer\Normalizer\ProjectParticipation;

use Doctrine\ORM\NonUniqueResultException;
use Symfony\Component\Security\Core\Security;
use Symfony\Component\Serializer\Normalizer\{ContextAwareDenormalizerInterface, DenormalizerAwareInterface, DenormalizerAwareTrait, ObjectToPopulateTrait};
use Unilend\Entity\{Clients, Project, ProjectParticipation};
use Unilend\Service\ProjectOrganizer\ProjectOrganizerManager;
use Unilend\Service\ProjectParticipation\ProjectParticipationManager;

class ProjectParticipationDenormalizer implements ContextAwareDenormalizerInterface, DenormalizerAwareInterface
{
    use DenormalizerAwareTrait;
    use ObjectToPopulateTrait;

    private const ALREADY_CALLED = 'PROJECT_PARTICIPATION_ATTRIBUTE_DENORMALIZER_ALREADY_CALLED';

    /** @var Security */
    private $security;
    /** @var ProjectParticipationManager */
    private $projectParticipationManager;
    /** @var ProjectOrganizerManager */
    private $projectOrganizerManager;

    /**
     * @param Security                    $security
     * @param ProjectParticipationManager $projectParticipationManager
     * @param ProjectOrganizerManager     $projectOrganizerManager
     */
    public function __construct(
        Security $security,
        ProjectParticipationManager $projectParticipationManager,
        ProjectOrganizerManager $projectOrganizerManager
    ) {
        $this->security                    = $security;
        $this->projectParticipationManager = $projectParticipationManager;
        $this->projectOrganizerManager     = $projectOrganizerManager;
    }

    /**
     * {@inheritdoc}
     */
    public function supportsDenormalization($data, $type, $format = null, array $context = []): bool
    {
        return !isset($context[self::ALREADY_CALLED]) && ProjectParticipation::class === $type;
    }

    /**
     * {@inheritdoc}
     *
     * @throws NonUniqueResultException
     */
    public function denormalize($data, $type, $format = null, array $context = [])
    {
        /** @var ProjectParticipation $projectParticipation */
        $projectParticipation = $this->extractObjectToPopulate(ProjectParticipation::class, $context);
        if ($projectParticipation) {
            $context['groups'] = array_merge($context['groups'] ?? [], $this->getAdditionalDenormalizerGroups($projectParticipation->getProject()));
        }

        $context[self::ALREADY_CALLED] = true;

        return $this->denormalizer->denormalize($data, $type, $format, $context);
    }

    /**
     * @param Project $project
     *
     * @throws NonUniqueResultException
     *
     * @return array
     */
    private function getAdditionalDenormalizerGroups(Project $project): array
    {
        $client = $this->security->getUser();
        $staff  = $client instanceof Clients ? $client->getCurrentStaff() : null;

        if (null === $staff) {
            return [];
        }

        $groups = [];

        if ($this->security->isGranted('ROLE_ADMIN') || $this->projectOrganizerManager->isArranger($staff, $project)) {
            $groups[] = ProjectParticipation::SERIALIZER_GROUP_ARRANGER_WRITE;
        }

        if ($this->security->isGranted('ROLE_ADMIN') || $this->projectParticipationManager->isParticipant($staff, $project)) {
            $groups[] = ProjectParticipation::SERIALIZER_GROUP_PARTICIPANT_OWNER_WRITE;
        }

        return $groups;
    }
}
