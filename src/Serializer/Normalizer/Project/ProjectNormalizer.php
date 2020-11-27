<?php

declare(strict_types=1);

namespace Unilend\Serializer\Normalizer\Project;

use Symfony\Component\Security\Core\Security;
use Symfony\Component\Serializer\Normalizer\{AbstractNormalizer,
    ContextAwareNormalizerInterface,
    NormalizerAwareInterface,
    NormalizerAwareTrait};
use Unilend\Core\Entity\Clients;
use Unilend\Core\Entity\Company;
use Unilend\Core\Entity\Staff;
use Unilend\Syndication\Entity\{Project};
use Unilend\Syndication\Security\Voter\ProjectVoter;

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
        $context[self::ALREADY_CALLED] = true;

        $client = $this->security->getUser();

        $currentStaff = $client instanceof Clients ? $client->getCurrentStaff() : null;

        $currentCompany = $currentStaff instanceof Staff ? $currentStaff->getCompany() : null;

        $isCAGMember = $currentCompany instanceof Company ? $currentCompany->isCAGMember() : false;

        $context[AbstractNormalizer::GROUPS] = array_merge($context[AbstractNormalizer::GROUPS] ?? [], $this->getAdditionalNormalizerGroups($object, $currentCompany));

        $normalized = $this->normalizer->normalize($object, $format, $context);

        if (\is_array($normalized) && false === $isCAGMember && $normalized['participationType'] === Project::PROJECT_PARTICIPATION_TYPE_SUB_PARTICIPATION) {
            unset($normalized['participationType']);
        }

        return $normalized;
    }

    /**
     * @param Project      $project
     * @param Company|null $connectedCompany
     *
     * @return array
     */
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
