<?php

declare(strict_types=1);

namespace KLS\Syndication\Serializer\Normalizer\Project;

use KLS\Core\Entity\Company;
use KLS\Core\Entity\Constant\SyndicationModality\ParticipationType;
use KLS\Core\Entity\Staff;
use KLS\Core\Entity\User;
use KLS\Syndication\Entity\Project;
use KLS\Syndication\Security\Voter\ProjectVoter;
use Symfony\Component\Security\Core\Security;
use Symfony\Component\Serializer\Normalizer\AbstractNormalizer;
use Symfony\Component\Serializer\Normalizer\ContextAwareNormalizerInterface;
use Symfony\Component\Serializer\Normalizer\NormalizerAwareInterface;
use Symfony\Component\Serializer\Normalizer\NormalizerAwareTrait;

class ProjectNormalizer implements ContextAwareNormalizerInterface, NormalizerAwareInterface
{
    use NormalizerAwareTrait;

    private const ALREADY_CALLED = 'PROJECT_ATTRIBUTE_NORMALIZER_ALREADY_CALLED';

    private Security $security;

    public function __construct(Security $security)
    {
        $this->security = $security;
    }

    public function supportsNormalization($data, string $format = null, array $context = [])
    {
        if (isset($context[self::ALREADY_CALLED])) {
            return false;
        }

        return $data instanceof Project;
    }

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
