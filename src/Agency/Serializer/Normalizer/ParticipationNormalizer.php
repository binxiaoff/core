<?php

declare(strict_types=1);

namespace Unilend\Agency\Serializer\Normalizer;

use ApiPlatform\Core\Api\IriConverterInterface;
use Symfony\Component\Serializer\Exception\ExceptionInterface;
use Symfony\Component\Serializer\Normalizer\AbstractNormalizer;
use Symfony\Component\Serializer\Normalizer\ContextAwareDenormalizerInterface;
use Symfony\Component\Serializer\Normalizer\DenormalizerAwareInterface;
use Unilend\Agency\Entity\Participation;
use Unilend\Agency\Entity\ParticipationTrancheAllocation;
use Unilend\Agency\Entity\Project;

class ParticipationNormalizer implements ContextAwareDenormalizerInterface, DenormalizerAwareInterface
{
    use NestedDenormalizationTrait;

    private const ALREADY_CALLED = __CLASS__ . '_ALREADY_CALLED';

    private IriConverterInterface $iriConverter;

    public function __construct(IriConverterInterface $iriConverter)
    {
        $this->iriConverter = $iriConverter;
    }

    /**
     * {@inheritDoc}
     */
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

        return $this->nestedDenormalize($data, $type, $format, $context, ['allocations']);
    }

    /**
     * {@inheritDoc}
     */
    protected function updateContextBeforeSecondDenormalization($denormalized, array $context): array
    {
        $context[AbstractNormalizer::DEFAULT_CONSTRUCTOR_ARGUMENTS][ParticipationTrancheAllocation::class]['participation'] = $denormalized;

        return $context;
    }
}
