<?php

declare(strict_types=1);

namespace Unilend\Serializer\Normalizer\ProjectParticipation;

use Symfony\Component\Security\Core\Security;
use Symfony\Component\Serializer\Normalizer\{ContextAwareNormalizerInterface, NormalizerAwareInterface, NormalizerAwareTrait};
use Unilend\Entity\{Clients, Project, ProjectParticipation, ProjectParticipationTranche};
use Unilend\Service\ProjectParticipation\ProjectParticipationManager;

class ProjectParticipationNormalizer implements ContextAwareNormalizerInterface, NormalizerAwareInterface
{
    use NormalizerAwareTrait;

    private const ALREADY_CALLED = 'PROJECT_PARTICIPATION_ATTRIBUTE_NORMALIZER_ALREADY_CALLED';

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
    public function supportsNormalization($data, $format = null, array $context = []): bool
    {
        if (isset($context[self::ALREADY_CALLED])) {
            return false;
        }

        return $data instanceof ProjectParticipation;
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
     * @param ProjectParticipation $participation
     *
     * @return array
     */
    private function getAdditionalNormalizerGroups(ProjectParticipation $participation): array
    {
        $client = $this->security->getUser();
        $staff  = $client instanceof Clients ? $client->getCurrentStaff() : null;

        if (null === $staff) {
            return [];
        }

        $project = $participation->getProject();

        $clientCurrentCompany = $client->getCompany();

        if (
            $this->security->isGranted('ROLE_ADMIN')
            || $this->projectParticipationManager->isParticipationOwner($staff, $participation)
            || $participation->getProject()->getSubmitterCompany() === $clientCurrentCompany
        ) {
            return [
                ProjectParticipation::SERIALIZER_GROUP_ADMIN_READ,
                ProjectParticipation::SERIALIZER_GROUP_SENSITIVE_READ,
                ProjectParticipationTranche::SERIALIZER_GROUP_SENSITIVE_READ,
            ];
        }

        if (Project::OFFER_VISIBILITY_PUBLIC === $project->getOfferVisibility()) {
            return [ProjectParticipation::SERIALIZER_GROUP_SENSITIVE_READ, ProjectParticipationTranche::SERIALIZER_GROUP_SENSITIVE_READ];
        }

        return [];
    }
}
