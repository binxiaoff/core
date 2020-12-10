<?php

declare(strict_types=1);

namespace Unilend\Serializer\Normalizer\ProjectOrganizer;

use Symfony\Component\Security\Core\Security;
use Symfony\Component\Serializer\Normalizer\ContextAwareNormalizerInterface;
use Symfony\Component\Serializer\Normalizer\NormalizerAwareInterface;
use Symfony\Component\Serializer\Normalizer\NormalizerAwareTrait;
use Unilend\Core\Entity\Clients;
use Unilend\Entity\ProjectOrganizer;

class ProjectOrganizerNormalizer implements ContextAwareNormalizerInterface, NormalizerAwareInterface
{
    use NormalizerAwareTrait;

    private const ALREADY_CALLED = 'PROJECT_ORGANIZER_ATTRIBUTE_NORMALIZER_ALREADY_CALLED';

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
     * @inheritDoc
     */
    public function supportsNormalization($data, string $format = null, array $context = [])
    {
        return !isset($context[self::ALREADY_CALLED]) && $data instanceof ProjectOrganizer;
    }

    /**
     * @inheritDoc
     */
    public function normalize($object, string $format = null, array $context = [])
    {
        $context[self::ALREADY_CALLED] = true;

        $normalized = $this->normalizer->normalize($object, $format, $context);

        /** @var Clients $user */
        $user = $this->security->getUser();

        $staff = $user instanceof Clients ? $user->getCurrentStaff() : null;

        $company = null !== $staff ? $staff->getCompany() : null;

        $isCAG = null !== $company ? $company->isCAGMember() : false;

        if (\is_array($normalized) && false === $isCAG) {
            $index = array_search(ProjectOrganizer::DUTY_PROJECT_ORGANIZER_RUN, $normalized['roles'] ?? [], true);

            if (false !== $index) {
                unset($normalized['roles'][$index]);
            }
        }

        return $normalized;
    }
}
