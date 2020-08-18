<?php

declare(strict_types=1);

namespace Unilend\Serializer\Normalizer\Project;

use Symfony\Component\Security\Core\Security;
use Symfony\Component\Serializer\Normalizer\{AbstractNormalizer,
    ContextAwareNormalizerInterface,
    NormalizerAwareInterface,
    NormalizerAwareTrait};
use Unilend\Entity\{Clients, Project};
use Unilend\Security\Voter\ProjectVoter;

class ProjectNormalizer implements ContextAwareNormalizerInterface, NormalizerAwareInterface
{
    use NormalizerAwareTrait;

    private const ALREADY_CALLED = 'PROJECT_ATTRIBUTE_NORMALIZER_ALREADY_CALLED';

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
    public function supportsNormalization($data, string $format = null, array $context = [])
    {
        if (isset($context[self::ALREADY_CALLED])) {
            return false;
        }

        return $data instanceof Project;
    }

    /**
     * {@inheritdoc}
     */
    public function normalize($object, string $format = null, array $context = [])
    {
        $context[AbstractNormalizer::GROUPS] = array_merge($context[AbstractNormalizer::GROUPS] ?? [], $this->getAdditionalNormalizerGroups($object));

        $context[self::ALREADY_CALLED] = true;

        return $this->normalizer->normalize($object, $format, $context);
    }

    /**
     * @param Project $project
     *
     * @return array
     */
    private function getAdditionalNormalizerGroups(Project $project): array
    {
        $client = $this->security->getUser();
        if (false === $client instanceof Clients) {
            return [];
        }

        if ($this->security->isGranted(ProjectVoter::ATTRIBUTE_ADMIN_VIEW, $project)) {
            return [Project::SERIALIZER_GROUP_ADMIN_READ];
        }

        return [];
    }
}
