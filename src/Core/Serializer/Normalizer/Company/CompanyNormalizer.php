<?php

declare(strict_types=1);

namespace Unilend\Core\Serializer\Normalizer\Company;

use Symfony\Component\Security\Core\Security;
use Symfony\Component\Serializer\Normalizer\AbstractNormalizer;
use Symfony\Component\Serializer\Normalizer\ContextAwareNormalizerInterface;
use Symfony\Component\Serializer\Normalizer\NormalizerAwareInterface;
use Symfony\Component\Serializer\Normalizer\NormalizerInterface;
use Unilend\Core\Entity\Company;
use Unilend\Core\Entity\Staff;
use Unilend\Core\Entity\User;

class CompanyNormalizer implements ContextAwareNormalizerInterface, NormalizerAwareInterface
{
    private const ALREADY_CALLED = 'COMPANY_NORMALIZER_ALREADY_CALLED';

    private NormalizerInterface $normalizer;

    private Security $security;

    public function __construct(Security $security)
    {
        $this->security = $security;
    }

    public function supportsNormalization($data, string $format = null, array $context = [])
    {
        return $data instanceof Company && !isset($context[static::ALREADY_CALLED]);
    }

    public function setNormalizer(NormalizerInterface $normalizer): CompanyNormalizer
    {
        $this->normalizer = $normalizer;

        return $this;
    }

    public function normalize($object, string $format = null, array $context = [])
    {
        $context[static::ALREADY_CALLED] = true;

        $currentUser = $this->security->getUser();

        $currentStaff = $currentUser instanceof User ? $currentUser->getCurrentStaff() : null;

        $currentCompany = $currentStaff instanceof Staff ? $currentStaff->getCompany() : null;

        if ($currentCompany === $object) {
            $context[AbstractNormalizer::GROUPS]   = $context[AbstractNormalizer::GROUPS] ?? [];
            $context[AbstractNormalizer::GROUPS][] = Company::SERIALIZER_GROUP_COMPANY_STAFF_READ;

            if ($currentStaff->isManager()) {
                $context[AbstractNormalizer::GROUPS][] = Company::SERIALIZER_GROUP_COMPANY_ADMIN_READ;
            }
        }

        return $this->normalizer->normalize($object, $format, $context);
    }
}
