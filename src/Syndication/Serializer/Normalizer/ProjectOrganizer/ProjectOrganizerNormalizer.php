<?php

declare(strict_types=1);

namespace KLS\Syndication\Serializer\Normalizer\ProjectOrganizer;

use KLS\Core\Entity\User;
use KLS\Syndication\Entity\ProjectOrganizer;
use Symfony\Component\Security\Core\Security;
use Symfony\Component\Serializer\Normalizer\ContextAwareNormalizerInterface;
use Symfony\Component\Serializer\Normalizer\NormalizerAwareInterface;
use Symfony\Component\Serializer\Normalizer\NormalizerAwareTrait;

class ProjectOrganizerNormalizer implements ContextAwareNormalizerInterface, NormalizerAwareInterface
{
    use NormalizerAwareTrait;

    private const ALREADY_CALLED = 'PROJECT_ORGANIZER_ATTRIBUTE_NORMALIZER_ALREADY_CALLED';

    private Security $security;

    public function __construct(Security $security)
    {
        $this->security = $security;
    }

    public function supportsNormalization($data, string $format = null, array $context = [])
    {
        return !isset($context[self::ALREADY_CALLED]) && $data instanceof ProjectOrganizer;
    }

    public function normalize($object, string $format = null, array $context = [])
    {
        $context[self::ALREADY_CALLED] = true;

        $normalized = $this->normalizer->normalize($object, $format, $context);

        /** @var User $user */
        $user = $this->security->getUser();

        $staff = $user instanceof User ? $user->getCurrentStaff() : null;

        $company = null !== $staff ? $staff->getCompany() : null;

        $isCAG = null !== $company ? $company->isCAGMember() : false;

        if (\is_array($normalized) && false === $isCAG) {
            $index = \array_search(ProjectOrganizer::DUTY_PROJECT_ORGANIZER_RUN, $normalized['roles'] ?? [], true);

            if (false !== $index) {
                unset($normalized['roles'][$index]);
            }
        }

        return $normalized;
    }
}
