<?php

declare(strict_types=1);

namespace Unilend\Serializer\Normalizer\ProjectParticipationTranche;

use Symfony\Component\Security\Core\Security;
use Symfony\Component\Serializer\Normalizer\{ContextAwareNormalizerInterface, NormalizerAwareInterface, NormalizerAwareTrait};
use Unilend\Entity\{Clients, Project, ProjectParticipationTranche};
use Unilend\Service\ProjectParticipation\ProjectParticipationManager;

class ProjectParticipationTrancheNormalizer implements ContextAwareNormalizerInterface, NormalizerAwareInterface
{
    use NormalizerAwareTrait;

    private const ALREADY_CALLED = 'PROJECT_PARTICIPATION_TRANCHE_ATTRIBUTE_NORMALIZER_ALREADY_CALLED';

    /** @var Security */
    private $security;
    /** @var ProjectParticipationManager */
    private $projectParticipationManager;

    /**
     * @param Security                    $security
     * @param ProjectParticipationManager $projectParticipationManager
     */
    public function __construct(
        Security $security,
        ProjectParticipationManager $projectParticipationManager
    ) {
        $this->security                    = $security;
        $this->projectParticipationManager = $projectParticipationManager;
    }

    /**
     * {@inheritdoc}
     */
    public function supportsNormalization($data, $format = null, array $context = []): bool
    {
        if (isset($context[self::ALREADY_CALLED])) {
            return false;
        }

        return $data instanceof ProjectParticipationTranche;
    }

    /**
     * {@inheritdoc}
     */
    public function normalize($object, $format = null, array $context = [])
    {
        $context['groups'] = array_merge($context['groups'] ?? [], $this->getAdditionalNormalizerGroups($object));

        $context[self::ALREADY_CALLED] = true;

        return $this->normalizer->normalize($object, $format, $context);
    }

    /**
     * @param ProjectParticipationTranche $projectParticipationTranche
     *
     * @return array
     */
    private function getAdditionalNormalizerGroups(ProjectParticipationTranche $projectParticipationTranche): array
    {
        $client = $this->security->getUser();
        $staff  = $client instanceof Clients ? $client->getCurrentStaff() : null;

        if (null === $staff) {
            return [];
        }

        $project = $projectParticipationTranche->getProjectParticipation()->getProject();

        if (
            $this->security->isGranted('ROLE_ADMIN')
            || Project::OFFER_VISIBILITY_PUBLIC === $project->getOfferVisibility()
            || $this->projectParticipationManager->isParticipationOwner($staff, $projectParticipationTranche->getProjectParticipation())
            || $project->getSubmitterCompany() === $staff->getCompany()
        ) {
            return [ProjectParticipationTranche::SERIALIZER_GROUP_SENSITIVE_READ];
        }

        return [];
    }
}
