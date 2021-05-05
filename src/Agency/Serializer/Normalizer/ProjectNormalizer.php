<?php

declare(strict_types=1);

namespace Unilend\Agency\Serializer\Normalizer;

use Symfony\Component\Security\Core\Security;
use Symfony\Component\Serializer\Normalizer\AbstractNormalizer;
use Symfony\Component\Serializer\Normalizer\ContextAwareDenormalizerInterface;
use Symfony\Component\Serializer\Normalizer\DenormalizerAwareInterface;
use Symfony\Component\Serializer\Normalizer\DenormalizerAwareTrait;
use Symfony\Component\Serializer\Normalizer\ObjectToPopulateTrait;
use Unilend\Agency\Entity\Project;
use Unilend\Agency\Entity\ProjectStatus;
use Unilend\Core\Entity\User;

class ProjectNormalizer implements ContextAwareDenormalizerInterface, DenormalizerAwareInterface
{
    use ObjectToPopulateTrait;
    use DenormalizerAwareTrait;

    private const ALREADY_CALLED = __CLASS__ . '_ALREADY_CALLED';

    private Security $security;

    public function __construct(Security $security)
    {
        $this->security = $security;
    }

    /**
     * {@inheritDoc}
     */
    public function supportsDenormalization($data, string $type, string $format = null, array $context = [])
    {
        return !isset($context[static::ALREADY_CALLED]) && Project::class === $type;
    }

    /**
     * {@inheritDoc}
     */
    public function denormalize($data, string $type, string $format = null, array $context = [])
    {
        $project = $this->extractObjectToPopulate(Project::class, $context);

        if (null !== $project) {
            $context[AbstractNormalizer::DEFAULT_CONSTRUCTOR_ARGUMENTS][ProjectStatus::class]['project'] = $project;
        }

        $user = $this->security->getUser();

        if ($user instanceof User) {
            $staff = $user->getCurrentStaff();

            if ($staff) {
                $context[AbstractNormalizer::DEFAULT_CONSTRUCTOR_ARGUMENTS][ProjectStatus::class]['addedBy'] = $staff;
            }
        }

        if (isset($data['currentStatus'])) {
            if (\is_int($data['currentStatus'])) {
                $data['currentStatus'] = [
                    'status' => $data['currentStatus'],
                ];
            }

            if (\is_array($data['currentStatus'])) {
                unset($data['currentStatus']['project'], $data['currentStatus']['addedBy']);
            }
        }

        $context[static::ALREADY_CALLED] = true;

        return $this->denormalizer->denormalize($data, $type, $format, $context);
    }
}
