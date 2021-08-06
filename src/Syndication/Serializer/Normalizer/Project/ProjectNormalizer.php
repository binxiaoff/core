<?php

declare(strict_types=1);

namespace Unilend\Syndication\Serializer\Normalizer\Project;

use Symfony\Component\Security\Core\Security;
use Symfony\Component\Serializer\Normalizer\AbstractNormalizer;
use Symfony\Component\Serializer\Normalizer\ContextAwareNormalizerInterface;
use Symfony\Component\Serializer\Normalizer\NormalizerAwareInterface;
use Symfony\Component\Serializer\Normalizer\NormalizerAwareTrait;
use Unilend\Core\Entity\Company;
use Unilend\Core\Entity\Constant\SyndicationModality\ParticipationType;
use Unilend\Core\Entity\Staff;
use Unilend\Core\Entity\User;
use Unilend\Syndication\Entity\Project;
use Unilend\Syndication\Security\Voter\ProjectVoter;

class ProjectNormalizer implements ContextAwareNormalizerInterface, NormalizerAwareInterface
{
    use NormalizerAwareTrait;

    private const ALREADY_CALLED = 'PROJECT_ATTRIBUTE_NORMALIZER_ALREADY_CALLED';

    private Security $security;

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
        $context[self::ALREADY_CALLED] = true;

        $user = $this->security->getUser();

        $currentStaff = $user instanceof User ? $user->getCurrentStaff() : null;

        $currentCompany = $currentStaff instanceof Staff ? $currentStaff->getCompany() : null;

        $isCAGMember = $currentCompany instanceof Company ? $currentCompany->isCAGMember() : false;

        $context[AbstractNormalizer::GROUPS] = \array_merge($context[AbstractNormalizer::GROUPS] ?? [], $this->getAdditionalNormalizerGroups($object, $currentCompany));

        $normalized = $this->normalizer->normalize($object, $format, $context);

        if (\is_array($normalized) && false === $isCAGMember && ParticipationType::SUB_PARTICIPATION === $normalized['participationType']) {
            unset($normalized['participationType']);
        }

        return $normalized;
    }

    private function getAdditionalNormalizerGroups(Project $project, ?Company $connectedCompany): array
    {
        $additionalGroups = [];

        if ($this->security->isGranted(ProjectVoter::ATTRIBUTE_ADMIN_VIEW, $project)) {
            $additionalGroups[] = Project::SERIALIZER_GROUP_ADMIN_READ;
        }

        if ($connectedCompany && $connectedCompany->isCAGMember()) {
            $additionalGroups[] = Project::SERIALIZER_GROUP_GCA_READ;
        }

        return $additionalGroups;
    }
}
