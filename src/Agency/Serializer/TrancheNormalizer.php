<?php

declare(strict_types=1);

namespace Unilend\Agency\Serializer;

use ApiPlatform\Core\Api\IriConverterInterface;
use ApiPlatform\Core\Validator\ValidatorInterface;
use Symfony\Component\Security\Core\Security;
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

    private Security $security;

    private IriConverterInterface $iriConverter;

    private ValidatorInterface $validator;

    /**
     * @param IriConverterInterface $iriConverter
     * @param ValidatorInterface    $validator
     * @param Security              $security
     */
    public function __construct(IriConverterInterface $iriConverter, ValidatorInterface $validator, Security $security)
    {
        $this->iriConverter = $iriConverter;
        $this->validator = $validator;
        $this->security = $security;
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

        $borrowerTrancheShares = $data['borrowerShares'] ?? [];

        foreach ($borrowerTrancheShares as $borrowerTrancheShare) {
            if (!is_array($borrowerTrancheShare)) {
                continue;
            }

            if (isset($borrowerTrancheShare['@id'])) {
                $borrowerTrancheShare = $this->updateBorrowerTrancheShare($borrowerTrancheShare);
            } else {
                $borrowerTrancheShare = $this->createBorrowerTrancheShare($borrowerTrancheShare, $denormalized);
            }

            if ($borrowerTrancheShare) {
                $denormalized->addBorrowerTrancheShare($borrowerTrancheShare);
            }
        }

        return $denormalized;
    }

    /**
     * @param array   $data
     * @param Tranche $tranche
     *
     * @return BorrowerTrancheShare
     *
     * @throws ExceptionInterface
     */
    private function createBorrowerTrancheShare(array $data, Tranche $tranche): BorrowerTrancheShare
    {
        unset($data['tranche']);

        /** @var BorrowerTrancheShare $borrowerTrancheShare */
        $borrowerTrancheShare = $this->denormalizer->denormalize(
            $data,
            BorrowerTrancheShare::class,
            'array',
            [
                AbstractNormalizer::GROUPS => ['agency:borrower_tranche_share:write', 'money:write'],
                AbstractNormalizer::DEFAULT_CONSTRUCTOR_ARGUMENTS => [
                    BorrowerTrancheShare::class => [
                        'tranche' => $tranche,
                    ],
                ],
            ]
        );

        $this->validator->validate($borrowerTrancheShare);

        return $borrowerTrancheShare;
    }

    /**
     * @param array $data
     *
     * @return BorrowerTrancheShare
     *
     * @throws ExceptionInterface
     */
    private function updateBorrowerTrancheShare(array $data)
    {
        unset($data['tranche']);

        /** @var BorrowerTrancheShare $borrowerTrancheShare */
        $borrowerTrancheShare = $this->iriConverter->getItemFromIri($data['@id'], [AbstractNormalizer::GROUPS => []]);

        /** @var BorrowerTrancheShare $updatedBorrowerTrancheShare */
        $updatedBorrowerTrancheShare = $this->denormalizer->denormalize(
            $data,
            BorrowerTrancheShare::class,
            'array',
            [
                AbstractNormalizer::OBJECT_TO_POPULATE => $borrowerTrancheShare,
                AbstractNormalizer::GROUPS => ['agency:borrower_tranche_share:write', 'money:write'],
            ]
        );

        $this->validator->validate($updatedBorrowerTrancheShare);

        return $updatedBorrowerTrancheShare;
    }
}
