<?php

declare(strict_types=1);

namespace KLS\CreditGuaranty\FEI\Controller;

use KLS\CreditGuaranty\FEI\Entity\FinancingObject;
use KLS\CreditGuaranty\FEI\Entity\Reservation;
use KLS\CreditGuaranty\FEI\Repository\ProgramEligibilityConditionRepository;
use KLS\CreditGuaranty\FEI\Service\Eligibility\EligibilityConditionChecker;
use KLS\CreditGuaranty\FEI\Service\ReservationAccessor;
use LogicException;
use Symfony\Component\HttpFoundation\Request;

class ProgramEligibilityConditions
{
    private const ALLOWED_BOOLEAN_VALUES = [
        '0', '1', 'true', 'false',
    ];

    private ProgramEligibilityConditionRepository $programEligibilityConditionRepository;
    private ReservationAccessor $reservationAccessor;
    private EligibilityConditionChecker $eligibilityConditionChecker;

    public function __construct(
        ProgramEligibilityConditionRepository $programEligibilityConditionRepository,
        ReservationAccessor $reservationAccessor,
        EligibilityConditionChecker $eligibilityConditionChecker
    ) {
        $this->programEligibilityConditionRepository = $programEligibilityConditionRepository;
        $this->reservationAccessor                   = $reservationAccessor;
        $this->eligibilityConditionChecker           = $eligibilityConditionChecker;
    }

    /**
     * @param Reservation|FinancingObject $data
     */
    public function __invoke($data, Request $request): array
    {
        if (false === $data instanceof Reservation && false === $data instanceof FinancingObject) {
            throw new LogicException(
                'This controller does no support data type other than Reservation or FinancingObject'
            );
        }

        $eligible = false;

        if ((\in_array($request->query->get('eligible'), self::ALLOWED_BOOLEAN_VALUES, true))) {
            $eligible = \filter_var($request->query->get('eligible'), FILTER_VALIDATE_BOOLEAN);
        }

        // we return empty array if eligible because we do not need it for now
        if ($eligible) {
            return [];
        }

        $ids = [];

        foreach ($data->getProgram()->getProgramEligibilities() as $programEligibility) {
            $field = $programEligibility->getField();

            if (
                ($data instanceof Reservation && 'loan' === $field->getCategory())
                || ($data instanceof FinancingObject && 'loan' !== $field->getCategory())
            ) {
                continue;
            }

            $object = ($data instanceof Reservation) ? $this->reservationAccessor->getEntity($data, $field) : $data;

            if (null === $object) {
                // this code should not be reached because borrower and project are initialized in reservation creation
                throw new LogicException(\sprintf(
                    'Impossible to get ProgramEligibilityConditions, object not found from data for field %s',
                    $field->getId()
                ));
            }

            foreach ($programEligibility->getProgramEligibilityConfigurations() as $programEligibilityConfiguration) {
                $ineligibleIds = $this->eligibilityConditionChecker->getIneligibleIdsByConfiguration(
                    $object,
                    $programEligibilityConfiguration,
                    false
                );

                if (false === empty($ineligibleIds)) {
                    $ids = \array_merge($ids, $ineligibleIds);
                }
            }
        }

        return $this->programEligibilityConditionRepository->findBy(['id' => $ids]);
    }
}
