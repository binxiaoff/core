<?php

declare(strict_types=1);

namespace KLS\CreditGuaranty\FEI\Serializer\Normalizer;

use Doctrine\ORM\NonUniqueResultException;
use Doctrine\ORM\NoResultException;
use KLS\Core\Service\MoneyCalculator;
use KLS\CreditGuaranty\FEI\Entity\Program;
use KLS\CreditGuaranty\FEI\Entity\ReservationStatus;
use KLS\CreditGuaranty\FEI\Repository\ProgramRepository;
use Symfony\Component\Serializer\Exception\ExceptionInterface;
use Symfony\Component\Serializer\Normalizer\ContextAwareNormalizerInterface;
use Symfony\Component\Serializer\Normalizer\NormalizerAwareInterface;
use Symfony\Component\Serializer\Normalizer\NormalizerAwareTrait;

class ProgramNormalizer implements ContextAwareNormalizerInterface, NormalizerAwareInterface
{
    use NormalizerAwareTrait;

    private const ALREADY_CALLED = __CLASS__ . '_ALREADY_CALLED';

    private ProgramRepository $programRepository;

    public function __construct(ProgramRepository $programRepository)
    {
        $this->programRepository = $programRepository;
    }

    public function supportsNormalization($data, string $format = null, array $context = []): bool
    {
        return $data instanceof Program && !isset($context[static::ALREADY_CALLED]);
    }

    /**
     * @param Program $object
     *
     * @throws ExceptionInterface
     * @throws NoResultException
     * @throws NonUniqueResultException
     */
    public function normalize($object, string $format = null, array $context = []): array
    {
        $context[static::ALREADY_CALLED] = true;
        /** @var array $data */
        $data = $this->normalizer->normalize($object, $format, $context);

        $data['reservationCount'] = $this->programRepository->countReservations(
            $object,
            [
                ReservationStatus::STATUS_SENT,
                ReservationStatus::STATUS_WAITING_FOR_FEI,
                ReservationStatus::STATUS_REQUEST_FOR_ADDITIONAL_INFORMATION,
                ReservationStatus::STATUS_ACCEPTED_BY_MANAGING_COMPANY,
                ReservationStatus::STATUS_CONTRACT_FORMALIZED,
            ]
        );
        $data['acceptedReservationCount'] = $this->programRepository->countReservations(
            $object,
            [ReservationStatus::STATUS_ACCEPTED_BY_MANAGING_COMPANY]
        );
        $data['contractualizedReservationCount'] = $this->programRepository->countReservations(
            $object,
            [ReservationStatus::STATUS_CONTRACT_FORMALIZED]
        );

        $data['contractualizedAmountsSum'] = $this->normalizer->normalize(
            $this->programRepository->sumProjectsAmounts($object, [ReservationStatus::STATUS_CONTRACT_FORMALIZED]),
            $format
        );
        $data['reservedAmountsSum'] = $this->normalizer->normalize(
            $this->programRepository->sumProjectsAmounts($object, [
                ReservationStatus::STATUS_SENT,
                ReservationStatus::STATUS_WAITING_FOR_FEI,
                ReservationStatus::STATUS_REQUEST_FOR_ADDITIONAL_INFORMATION,
                ReservationStatus::STATUS_ACCEPTED_BY_MANAGING_COMPANY,
            ]),
            $format
        );
        $data['amountAvailable'] = $this->normalizer->normalize(
            MoneyCalculator::subtract(
                $object->getFunds(),
                $this->programRepository->sumProjectsAmounts($object, [
                    ReservationStatus::STATUS_SENT,
                    ReservationStatus::STATUS_WAITING_FOR_FEI,
                    ReservationStatus::STATUS_REQUEST_FOR_ADDITIONAL_INFORMATION,
                    ReservationStatus::STATUS_ACCEPTED_BY_MANAGING_COMPANY,
                    ReservationStatus::STATUS_CONTRACT_FORMALIZED,
                ])
            ),
            $format
        );

        return $data;
    }
}
