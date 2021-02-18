<?php

declare(strict_types=1);

namespace Unilend\Agency\Serializer;

use ApiPlatform\Core\Api\IriConverterInterface;
use ApiPlatform\Core\Validator\ValidatorInterface;
use Symfony\Component\Serializer\Exception\ExceptionInterface;
use Symfony\Component\Serializer\Normalizer\AbstractNormalizer;
use Symfony\Component\Serializer\Normalizer\ContextAwareDenormalizerInterface;
use Symfony\Component\Serializer\Normalizer\DenormalizerAwareInterface;
use Symfony\Component\Serializer\Normalizer\DenormalizerAwareTrait;
use Symfony\Component\Serializer\Normalizer\ObjectToPopulateTrait;
use Unilend\Agency\Entity\BorrowerTrancheShare;
use Unilend\Agency\Entity\Tranche;

class TrancheNormalizer implements ContextAwareDenormalizerInterface, DenormalizerAwareInterface
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
        return $type === Tranche::class && !isset($context[static::ALREADY_CALLED]);
    }

    /**
     * @inheritDoc
     */
    public function denormalize($data, string $type, string $format = null, array $context = [])
    {
        $context[static::ALREADY_CALLED] = true;

        /**
         * @var Tranche $denormalized
         */
        $denormalized = $this->denormalizer->denormalize($data, $type, $format, $context);

        $borrowerTrancheShares = array_map(function ($datum) use ($denormalized) {
            $borrowerTrancheShare = null;

            if (\is_string($datum)) {
                $borrowerTrancheShare = $this->iriConverter->getItemFromIri($datum);
            }

            if (\is_array($datum)) {
                unset($datum['tranche']);

                /** @var BorrowerTrancheShare $updatedBorrowerTrancheShare */
                $borrowerTrancheShare = $this->denormalizer->denormalize(
                    $datum,
                    BorrowerTrancheShare::class,
                    'array',
                    [
                        AbstractNormalizer::OBJECT_TO_POPULATE =>
                            isset($datum['@id']) ? $this->iriConverter->getItemFromIri($datum['@id'], [AbstractNormalizer::GROUPS => []]) : null,
                        AbstractNormalizer::GROUPS => ['agency:borrowerTrancheShare:write', 'money:write'],
                        AbstractNormalizer::DEFAULT_CONSTRUCTOR_ARGUMENTS => [
                            BorrowerTrancheShare::class => [
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
        }, $data['borrowerShares'] ?? []);

        $denormalized->setBorrowerShares(array_filter($borrowerTrancheShares));

        return $denormalized;
    }
}
