<?php

declare(strict_types=1);

namespace Unilend\Agency\Serializer\Normalizer;

use ApiPlatform\Core\Api\IriConverterInterface;
use Symfony\Component\Security\Core\Security;
use Symfony\Component\Serializer\Exception\ExceptionInterface;
use Symfony\Component\Serializer\Normalizer\AbstractNormalizer;
use Symfony\Component\Serializer\Normalizer\ContextAwareDenormalizerInterface;
use Symfony\Component\Serializer\Normalizer\DenormalizerAwareInterface;
use Symfony\Component\Serializer\Normalizer\ObjectToPopulateTrait;
use Unilend\Agency\Entity\Participation;
use Unilend\Agency\Entity\ParticipationTrancheAllocation;
use Unilend\Agency\Entity\Project;
use Unilend\Agency\Security\Voter\ParticipationVoter;

class ParticipationNormalizer implements ContextAwareDenormalizerInterface, DenormalizerAwareInterface
{
    use NestedDenormalizationTrait;
    use ObjectToPopulateTrait;

    private const ALREADY_CALLED = __CLASS__ . '_ALREADY_CALLED';

    private IriConverterInterface $iriConverter;
    private Security $security;

    public function __construct(IriConverterInterface $iriConverter, Security $security)
    {
        $this->iriConverter = $iriConverter;
        $this->security     = $security;
    }

    public function supportsDenormalization($data, string $type, string $format = null, array $context = [])
    {
        return Participation::class === $type && false === isset($context[static::ALREADY_CALLED]);
    }

    /**
     * @param $data
     *
     * @throws ExceptionInterface
     *
     * @return Participation
     */
    public function denormalize($data, string $type, string $format = null, array $context = [])
    {
        $context[static::ALREADY_CALLED] = true;

        if (isset($data['project']) && empty($data['pool'])) {
            unset($data['project']);

            try {
                $project = $this->iriConverter->getItemFromIri($data['project']);
            } catch (\Exception $exception) {
                $project = null;
            }

            if ($project && $project instanceof Project) {
                $data['pool'] = $this->iriConverter->getIriFromItem($project->getParticipationPools()[$data['secondary'] ?? false]);
            }
        }

        $archived = $data['archived'] ?? false;
        unset($data['archived']);

        /** @var Participation $denormalized */
        $denormalized = $this->nestedDenormalize($data, $type, $format, $context, ['allocations']);

        $canArchive = $this->security->isGranted(ParticipationVoter::ATTRIBUTE_DELETE, $denormalized);

        if ($canArchive && $archived && false === $denormalized->isArchived() && $denormalized->getProject()->isPublished()) {
            $denormalized->archive();
        }

        return $denormalized;
    }

    protected function updateContextBeforeSecondDenormalization($denormalized, array $context): array
    {
        $context[AbstractNormalizer::DEFAULT_CONSTRUCTOR_ARGUMENTS][ParticipationTrancheAllocation::class]['participation'] = $denormalized;

        return $context;
    }
}
