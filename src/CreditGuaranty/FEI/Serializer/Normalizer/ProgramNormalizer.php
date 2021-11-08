<?php

declare(strict_types=1);

namespace KLS\CreditGuaranty\FEI\Serializer\Normalizer;

use KLS\Core\Entity\Staff;
use KLS\CreditGuaranty\FEI\Entity\Program;
use KLS\CreditGuaranty\FEI\Entity\ReservationStatus;
use KLS\CreditGuaranty\FEI\Repository\ReservationRepository;
use Symfony\Component\Security\Core\Security;
use Symfony\Component\Serializer\Exception\ExceptionInterface;
use Symfony\Component\Serializer\Normalizer\ContextAwareNormalizerInterface;
use Symfony\Component\Serializer\Normalizer\NormalizerAwareInterface;
use Symfony\Component\Serializer\Normalizer\NormalizerAwareTrait;

class ProgramNormalizer implements ContextAwareNormalizerInterface, NormalizerAwareInterface
{
    use NormalizerAwareTrait;

    private const ALREADY_CALLED = __CLASS__ . '_ALREADY_CALLED';

    private Security $security;
    private ReservationRepository $reservationRepository;

    public function __construct(Security $security, ReservationRepository $reservationRepository)
    {
        $this->security              = $security;
        $this->reservationRepository = $reservationRepository;
    }

    public function supportsNormalization($data, string $format = null, array $context = []): bool
    {
        return $data instanceof Program && !isset($context[static::ALREADY_CALLED]);
    }

    /**
     * @param Program $object
     *
     * @throws ExceptionInterface
     */
    public function normalize($object, string $format = null, array $context = []): array
    {
        $context[static::ALREADY_CALLED] = true;

        $token = $this->security->getToken();
        /** @var Staff|null $staff */
        $staff = ($token && $token->hasAttribute('staff')) ? $token->getAttribute('staff') : null;

        /** @var array $data */
        $data = $this->normalizer->normalize($object, $format, $context);

        $data['reservationCount'] = $this->reservationRepository->countByStaffAndProgramAndStatuses(
            $staff,
            $object,
            [
                ReservationStatus::STATUS_SENT,
                ReservationStatus::STATUS_WAITING_FOR_FEI,
                ReservationStatus::STATUS_REQUEST_FOR_ADDITIONAL_INFORMATION,
                ReservationStatus::STATUS_ACCEPTED_BY_MANAGING_COMPANY,
                ReservationStatus::STATUS_CONTRACT_FORMALIZED,
            ]
        );
        $data['acceptedReservationCount'] = $this->reservationRepository->countByStaffAndProgramAndStatuses(
            $staff,
            $object,
            [ReservationStatus::STATUS_ACCEPTED_BY_MANAGING_COMPANY]
        );
        $data['contractualizedReservationCount'] = $this->reservationRepository->countByStaffAndProgramAndStatuses(
            $staff,
            $object,
            [ReservationStatus::STATUS_CONTRACT_FORMALIZED]
        );

        return $data;
    }
}
