<?php

declare(strict_types=1);

namespace Unilend\Serializer\Normalizer\ProjectParticipation;

use Symfony\Component\Security\Core\Security;
use Symfony\Component\Serializer\Normalizer\{ContextAwareNormalizerInterface, NormalizerAwareInterface, NormalizerAwareTrait};
use Unilend\Entity\{Clients, Project, ProjectParticipation};

class ProjectParticipationNormalizer implements ContextAwareNormalizerInterface, NormalizerAwareInterface
{
    use NormalizerAwareTrait;

    private const ALREADY_CALLED = 'PROJECT_PARTICIPATION_ATTRIBUTE_NORMALIZER_ALREADY_CALLED';

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
    public function supportsNormalization($data, $format = null, array $context = [])
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
        if (!$client instanceof Clients) {
            return [];
        }

        $project = $participation->getProject();

        $clientCurrentCompany = $client->getCompany();

        if (
            $this->security->isGranted('ROLE_ADMIN')
            || $participation->getCompany() === $clientCurrentCompany
            || $participation->getProject()->getSubmitterCompany() === $clientCurrentCompany
        ) {
            return [ProjectParticipation::SERIALIZER_GROUP_ADMIN_READ, ProjectParticipation::SERIALIZER_GROUP_SENSITIVE_READ];
        }

        if (Project::OFFER_VISIBILITY_PUBLIC === $project->getOfferVisibility()) {
            return [ProjectParticipation::SERIALIZER_GROUP_SENSITIVE_READ];
        }

        return [];
    }
}
