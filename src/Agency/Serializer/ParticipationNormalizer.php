<?php

declare(strict_types=1);

namespace Unilend\Agency\Serializer;

use ApiPlatform\Core\Api\IriConverterInterface;
use ApiPlatform\Core\Validator\ValidatorInterface;
use Symfony\Component\Serializer\Exception\ExceptionInterface;
use Symfony\Component\Serializer\Normalizer\AbstractNormalizer;
use Symfony\Component\Serializer\Normalizer\DenormalizerAwareTrait;
use Symfony\Component\Serializer\Normalizer\ObjectToPopulateTrait;
use Unilend\Agency\Entity\BorrowerTrancheShare;
use Unilend\Agency\Entity\Participation;
use Unilend\Agency\Entity\ParticipationTrancheAllocation;

class ParticipationNormalizer
{
    use ObjectToPopulateTrait;
    use DenormalizerAwareTrait;

    private const ALREADY_CALLED = __CLASS__ . '_ALREADY_CALLED';

    private IriConverterInterface $iriConverter;

    private ValidatorInterface $validator;

    /**
     * @param IriConverterInterface $iriConverter
     * @param ValidatorInterface    $validator
     */
    public function __construct(IriConverterInterface $iriConverter, ValidatorInterface $validator)
    {
        $this->iriConverter = $iriConverter;
        $this->validator = $validator;
    }

    /**
     * @inheritDoc
     */
    public function supportsDenormalization($data, string $type, string $format = null, array $context = [])
    {
        return $type === Participation::class && !isset($context[static::ALREADY_CALLED]);
    }

    /**
     * @param $data
     * @param string      $type
     * @param string|null $format
     * @param array       $context
     *
     * @return Participation
     *
     * @throws ExceptionInterface
     */
    public function denormalize($data, string $type, string $format = null, array $context = [])
    {
        $context[static::ALREADY_CALLED] = true;

        /**
         * @var Participation $denormalized
         */
        $denormalized = $this->denormalizer->denormalize($data, $type, $format, $context);

        if (\array_key_exists('allocations', $data) && \is_array($data['allocations'])) {
            $allocations = array_map(function ($datum) use ($denormalized) {
                $borrowerTrancheShare = null;

                if (\is_string($datum)) {
                    $borrowerTrancheShare = $this->iriConverter->getItemFromIri($datum);
                }

                if (\is_array($datum)) {
                    unset($datum['participation']);

                    /** @var BorrowerTrancheShare $updatedBorrowerTrancheShare */
                    $borrowerTrancheShare = $this->denormalizer->denormalize(
                        $datum,
                        ParticipationTrancheAllocation::class,
                        'array',
                        [
                            AbstractNormalizer::OBJECT_TO_POPULATE =>
                                isset($datum['@id']) ? $this->iriConverter->getItemFromIri($datum['@id'], [AbstractNormalizer::GROUPS => []]) : null,
                            AbstractNormalizer::GROUPS => [
                                'agency:participationTrancheAllocation:write',
                            ] + (false === isset($datum['@id']) ? ['agency:participationTrancheAllocation:create'] : []) ,
                            AbstractNormalizer::DEFAULT_CONSTRUCTOR_ARGUMENTS => [
                                ParticipationTrancheAllocation::class => [
                                    'tranche' => $denormalized,
                                ],
                            ],
                        ]
                    );
                }

                if ($borrowerTrancheShare ?? false) {
                    $this->validator->validate($borrowerTrancheShare);
                }

                return $borrowerTrancheShare;
            }, $data['allocations']);

            $denormalized->setAllocations(array_filter($allocations));
        }

        return $denormalized;
    }
}
